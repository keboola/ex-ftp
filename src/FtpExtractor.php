<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

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
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(bool $onlyNewFiles, FtpFilesystem $ftpFs, LoggerInterface $logger)
    {
        $this->ftpFilesystem = $ftpFs;
        $this->onlyNewFiles = $onlyNewFiles;
        $this->filesToDownload = [];
        $this->logger = $logger;
    }

    public function copyFiles(string $sourcePath, string $destinationPath, FileStateRegistry $registry): int
    {
        try {
            /** @var AbstractFtpAdapter $adapter */
            $adapter = $this->ftpFilesystem->getAdapter();
            $this->logger->info('Connecting to host ...');

            (new RetryProxy(
                new SimpleRetryPolicy(self::CONNECTION_RETRIES, [
                    \LogicException::class,
                    \RuntimeException::class,
                    \ErrorException::class,
                ]),
                new ExponentialBackOffPolicy(self::RETRY_BACKOFF),
                $this->logger
            ))->call(static function () use ($adapter): void {
                $adapter->getConnection();
            });

            $this->logger->info('Connection successful');
        } catch (\Throwable $e) {
            ExceptionConverter::handleCopyFilesException($e);
        }

        $this->prepareToDownloadFolder($sourcePath, $destinationPath);
        return $this->download($registry);
    }

    private function prepareToDownloadFolder(string $sourcePath, string $destinationPath): void
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
            ExceptionConverter::handlePrepareToDownloaException($e);
        }

        $this->logger->info(sprintf("Base path contains %s files(s)", count($items)));
        $i = 0;
        foreach ($items as $item) {
            if ($i % self::LOGGER_INFO_LOOP === 0) {
                $this->logger->info(
                    sprintf(
                        "Prepared %d/%d items for download",
                        $i,
                        count($items)
                    )
                );
            }
            $i++;

            if (!GlobValidator::validatePathAgainstGlob($item['path'], $sourcePath)) {
                continue;
            }

            $this->prepareToDownloadSingleFile($item['path'], $destinationPath);
        }

        $this->logger->info(sprintf("Prepared %d/%d items for download", count($items), count($items)));
    }

    private function prepareToDownloadSingleFile(string $sourcePath, string $destinationPath): void
    {
        $destination = $destinationPath . '/' . strtr($sourcePath, ['/' => '-']);
        $timestamp = 0;
        if ($this->onlyNewFiles) {
            try {
                $timestamp = (int) $this->ftpFilesystem->getTimestamp($sourcePath);
            } catch (\Throwable $e) {
                ExceptionConverter::handlePrepareToDownloaException($e);
            }
        }

        $this->filesToDownload[] = [
            self::FILE_DESTINATION_KEY => $destination,
            self::FILE_SOURCE_KEY => $sourcePath,
            self::FILE_TIMESTAMP_KEY => $timestamp,
        ];
    }

    private function download(FileStateRegistry $registry): int
    {
        $cbTimestampSort = function (array $a, array $b) {
            return intval($a[self::FILE_TIMESTAMP_KEY]) <=> intval($b[self::FILE_TIMESTAMP_KEY]);
        };
        uasort($this->filesToDownload, $cbTimestampSort);

        $fs = new Filesystem();
        $downloadedFiles = 0;
        foreach ($this->filesToDownload as $file) {
            $file[self::FILE_DESTINATION_KEY] = ColumnNameSanitizer::toAscii($file[self::FILE_DESTINATION_KEY]);
            if ($this->onlyNewFiles
                && !$registry->shouldBeFileUpdated(
                    $file[self::FILE_SOURCE_KEY],
                    $file[self::FILE_TIMESTAMP_KEY]
                )
            ) {
                continue;
            }

            $this->logger->info(sprintf("Downloading file %s", $file[self::FILE_SOURCE_KEY]));

            try {
                $fs->dumpFile(
                    $file[self::FILE_DESTINATION_KEY],
                    $this->ftpFilesystem->read($file[self::FILE_SOURCE_KEY])
                );
            } catch (\Throwable $e) {
                ExceptionConverter::handleDownload($e);
            }
            $downloadedFiles++;
        }
        return $downloadedFiles;
    }
}
