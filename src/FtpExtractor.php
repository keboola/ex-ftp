<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use Keboola\Component\UserException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem as FtpFilesystem;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Glob\Glob;

class FtpExtractor
{
    private const RECURSIVE_COPY = true;
    private const FTP_FILETYPE_FILE = 'file';
    private const FILE_DESTINATION_KEY = 'destination-path';
    private const FILE_TIMESTAMP_KEY = 'timestamp';
    private const FILE_SOURCE_KEY = 'source-path';
    private const LOGGER_INFO_LOOP = '10';

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
        $this->prepareToDownloadFolder($sourcePath, $destinationPath);
        return $this->download($registry);
    }

    private function prepareToDownloadFolder(string $sourcePath, string $destinationPath): void
    {
        $basePath = Glob::getBasePath(GlobValidator::convertToAbsolute($sourcePath));
        try {
            $items = $this->ftpFilesystem->listContents($basePath, self::RECURSIVE_COPY);
        } catch (\RuntimeException $e) {
            throw new UserException($e->getMessage(), $e->getCode(), $e);
        } catch (\LogicException $e) {
            throw new UserException($e->getMessage(), $e->getCode(), $e);
        } catch (\ErrorException $e) {
            throw new UserException($e->getMessage(), $e->getCode(), $e);
        }
        $this->logger->info("Connected to host");
        $this->logger->info(sprintf("Base path contains %s item(s)", count($items)));
        $i = 0;
        foreach ($items as $item) {
            if ($i % self::LOGGER_INFO_LOOP === 0) {
                $this->logger->info(
                    sprintf(
                        "Already filtered %d/%d items",
                        $i,
                        count($items)
                    )
                );
            }
            $i++;

            if (!GlobValidator::validatePathAgainstGlob($item['path'], $sourcePath)) {
                continue;
            }

            if ($item['type'] === self::FTP_FILETYPE_FILE) {
                $this->prepareToDownloadSingleFile($item['path'], $destinationPath);
            }
        }

        $this->logger->info(sprintf("Found %d file(s) to be downloaded", count($this->filesToDownload)));
    }

    private function prepareToDownloadSingleFile(string $sourcePath, string $destinationPath): void
    {
        $destination = $destinationPath . '/' . strtr($sourcePath, ['/' => '-']);
        $timestamp = 0;
        if ($this->onlyNewFiles) {
            try {
                $timestamp = (int) $this->ftpFilesystem->getTimestamp($sourcePath);
            } catch (FileNotFoundException $e) {
                throw new UserException($e->getMessage(), $e->getCode(), $e);
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
            } catch (FileNotFoundException $e) {
                throw new UserException("Error while trying to download file: " . $e->getMessage());
            }
            $downloadedFiles++;
        }
        return $downloadedFiles;
    }
}
