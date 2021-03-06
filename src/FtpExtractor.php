<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use http\Header\Parser;
use Keboola\Component\UserException;
use Keboola\FtpExtractor\Exception\ApplicationException;
use Keboola\FtpExtractor\Exception\ExceptionConverter;
use Keboola\Utils\Sanitizer\ColumnNameSanitizer;
use League\Flysystem\Adapter\AbstractFtpAdapter;
use League\Flysystem\Filesystem as FtpFilesystem;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Glob\Glob;
use Retry\RetryProxy;
use Retry\Policy\SimpleRetryPolicy;
use Retry\BackOff\ExponentialBackOffPolicy;

class FtpExtractor
{
    private const RECURSIVE_COPY = true;
    private const FILE_DESTINATION_KEY = 'destination-path';
    private const FILE_TIMESTAMP_KEY = 'timestamp';
    private const FILE_SOURCE_KEY = 'source-path';
    private const LOGGER_INFO_LOOP = '10';
    private const CONNECTION_RETRIES = 3;
    private const RETRY_BACKOFF = 300;

    /**
     * @var FtpFilesystem
     */
    private $ftpFilesystem;

    /**
     * @var bool
     */
    private $onlyNewFiles;

    /**
     * @var array
     */
    private $filesToDownload;

    /**
     * @var FileStateRegistry
     */
    private $registry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Filesystem
     */
    private $fs;

    public function __construct(
        bool $onlyNewFiles,
        FtpFilesystem $ftpFs,
        FileStateRegistry $registry,
        LoggerInterface $logger
    ) {
        $this->ftpFilesystem = $ftpFs;
        $this->onlyNewFiles = $onlyNewFiles;
        $this->filesToDownload = [];
        $this->registry = $registry;
        $this->logger = $logger;
        $this->fs = new Filesystem();
    }

    public function copyFiles(string $sourcePath, string $destinationPath): int
    {
        try {
            /** @var AbstractFtpAdapter $adapter */
            $adapter = $this->ftpFilesystem->getAdapter();
            $this->logger->info('Connecting to host ...');

            $this->createRetryProxy()->call(static function () use ($adapter): void {
                $adapter->getConnection();
            });

            $this->logger->info('Connection successful');
        } catch (\Throwable $e) {
            ExceptionConverter::handleCopyFilesException($e);
        }

        $this->prepareToDownloadFolder($sourcePath, $destinationPath);
        return $this->download();
    }

    private function prepareToDownloadFolder(string $sourcePath, string $destinationPath): void
    {
        $items = $this->getPotentialFiles($sourcePath);
        $i = 0;
        foreach ($items as $item) {
            if ($i % self::LOGGER_INFO_LOOP === 0) {
                $this->logger->info(
                    sprintf(
                        "Checked %d of a possible %d files and found %d to download so far",
                        $i,
                        count($items),
                        count($this->filesToDownload)
                    )
                );
            }
            $i++;
            if (!GlobValidator::validatePathAgainstGlob($item['path'], $sourcePath)) {
                continue;
            }
            $timestamp = 0;
            if ($this->onlyNewFiles) {
                try {
                    $timestamp = (int) $this->ftpFilesystem->getTimestamp($item['path']);
                    if (!$this->registry->shouldBeFileUpdated($item['path'], $timestamp)) {
                        continue;
                    }
                } catch (\Throwable $e) {
                    ExceptionConverter::handlePrepareToDownloadException($e);
                }
            }
            $destination = $destinationPath . '/' . strtr($item['path'], ['/' => '-']);
            $this->filesToDownload[] = [
                self::FILE_DESTINATION_KEY => $destination,
                self::FILE_SOURCE_KEY => $item['path'],
                self::FILE_TIMESTAMP_KEY => $timestamp,
            ];
        }
        $this->logger->info(sprintf("%d files are ready for download", count($this->filesToDownload)));
    }

    private function getPotentialFiles(string $sourcePath): array
    {
        $absSourcePath = GlobValidator::convertToAbsolute($sourcePath); //because Glob work with absolute paths

        $items = [];
        try {
            if (Glob::getStaticPrefix($absSourcePath) === $absSourcePath) { //means is file
                $file = $this->ftpFilesystem->get($absSourcePath);
                $items[] = [
                    'path' => $file->getPath(),
                    'type' => ($file->isFile()) ? ItemFilter::FTP_FILETYPE_FILE : '',
                ];
            } else { //means is glob based path
                $this->logger->info("Fetching list of files in base path");
                $basePath = Glob::getBasePath($absSourcePath);
                $items = $this->ftpFilesystem->listContents($basePath, self::RECURSIVE_COPY);
            }
            $countBeforeFilter = count($items);
            $this->logger->info(
                sprintf(
                    "Base path listing contains %s item(s) including directories",
                    $countBeforeFilter
                )
            );
            $items = ItemFilter::getOnlyFiles($items);
            $this->logger->info(
                sprintf(
                    "%s item(s) filtered out",
                    $countBeforeFilter - count($items)
                )
            );
        } catch (\Throwable $e) {
            ExceptionConverter::handlePrepareToDownloadException($e);
        }
        $this->logger->info(sprintf("Base path contains %s files(s)", count($items)));
        return $items;
    }

    private function download(): int
    {
        $cbTimestampSort = function (array $a, array $b) {
            return intval($a[self::FILE_TIMESTAMP_KEY]) <=> intval($b[self::FILE_TIMESTAMP_KEY]);
        };
        uasort($this->filesToDownload, $cbTimestampSort);

        $downloadedFiles = 0;
        foreach ($this->filesToDownload as $file) {
            $this->downloadFile($file);
            $downloadedFiles++;
        }
        return $downloadedFiles;
    }

    private function downloadFile(array $file): void
    {
        $file[self::FILE_DESTINATION_KEY] = ColumnNameSanitizer::toAscii($file[self::FILE_DESTINATION_KEY]);

        $this->logger->info(sprintf("Downloading file %s", $file[self::FILE_SOURCE_KEY]));

        $localPath = $file[self::FILE_DESTINATION_KEY];
        $ftpPath = $file[self::FILE_SOURCE_KEY];

        try {
            $this->createRetryProxy()->call(function () use ($localPath, $ftpPath): void {
                $ftpSize = $this->ftpFilesystem->getSize($ftpPath);
                $this->fs->dumpFile($localPath, (string) $this->ftpFilesystem->read($ftpPath));
                $localSize = filesize($localPath);
                $this->checkFileSize($localPath, $ftpPath, $localSize, $ftpSize);
            });
        } catch (\Throwable $e) {
            ExceptionConverter::handleDownloadException($e);
        }
        $this->registry->updateOutputState($file[self::FILE_SOURCE_KEY], $file[self::FILE_TIMESTAMP_KEY]);
    }

    private function createRetryProxy(): RetryProxy
    {
        return new RetryProxy(
            new SimpleRetryPolicy(self::CONNECTION_RETRIES),
            new ExponentialBackOffPolicy(self::RETRY_BACKOFF),
            $this->logger
        );
    }

    /**
     * @param string $localPath
     * @param string $ftpPath
     * @param int|false $localSize
     * @param int|false $ftpSize
     * @throws ApplicationException
     */
    private function checkFileSize(string $localPath, string $ftpPath, $localSize, $ftpSize): void
    {
        if (!is_int($localSize)) {
            throw new ApplicationException(
                sprintf('Cannot get size of the local file "%s".', $localPath)
            );
        }

        if (!is_int($ftpSize)) {
            throw new ApplicationException(
                sprintf('Cannot get size of the FTP file "%s".', $ftpPath)
            );
        }

        if ($ftpSize !== $localSize) {
            throw new UserException(sprintf(
                'The size of the downloaded file "%s" does not match the size reported from the FTP server. ' .
                'FTP size: %s, local size: %s.',
                $ftpPath,
                self::humanReadableFileSize($ftpSize),
                self::humanReadableFileSize($localSize)
            ));
        }
    }

    private static function humanReadableFileSize(int $size, int $precision = 2): string
    {
        // https://gist.github.com/liunian/9338301
        for ($i = 0; ($size / 1024) > 0.9; $i++) {
            $size /= 1024;
        }
        return round($size, $precision).['B','kB','MB','GB','TB','PB','EB','ZB','YB'][$i];
    }
}
