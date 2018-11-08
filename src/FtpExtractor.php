<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use League\Flysystem\Filesystem as FtpFilesystem;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Glob\Glob;

class FtpExtractor
{
    private const RECURSIVE_COPY = true;
    private const FTP_FILETYPE_FILE = 'file';
    private const FILE_DESTINATION_KEY = 'destination-path';
    private const FILE_TIMESTAMP_KEY = 'timestamp';
    private const FILE_SOURCE_KEY = 'source-path';

    /**
     * @var FtpFilesystem
     */
    private $ftpFilesystem;

    /**
     * @var bool
     */
    private $onlyNewFiles;

    /**
     * @var bool
     */
    private $isWildcard;

    /**
     * @var array
     */
    private $filesToDownload;

    public function __construct(bool $onlyNewFiles, bool $isWildcard, FtpFilesystem $ftpFs)
    {
        $this->ftpFilesystem = $ftpFs;
        $this->onlyNewFiles = $onlyNewFiles;
        $this->isWildcard = $isWildcard;
        $this->filesToDownload = [];
    }

    public function copyFiles(string $sourcePath, string $destionationPath, FileStateRegistry $registry): int
    {
        if ($this->isWildcard) {
            $this->prepareToDownloadFolder($sourcePath, $destionationPath);
        } else {
            $this->prepareToDownloadSingleFile($sourcePath, $destionationPath);
        }
        return $this->download($registry);
    }

    private function prepareToDownloadFolder(string $sourcePath, string $destinationPath): void
    {
        $basePath = Glob::getBasePath(GlobValidator::convertToAbsolute($sourcePath));
        $items = $this->ftpFilesystem->listContents($basePath, self::RECURSIVE_COPY);
        foreach ($items as $item) {
            if ($this->isWildcard && !GlobValidator::validatePathAgainstGlob($item['path'], $sourcePath)) {
                continue;
            }

            if ($item['type'] === self::FTP_FILETYPE_FILE) {
                $this->prepareToDownloadSingleFile($item['path'], $destinationPath);
            }
        }
    }

    private function prepareToDownloadSingleFile(string $sourcePath, string $destinationPath): void
    {
        $destination = $destinationPath . '/' . strtr($sourcePath, ['/' => '-']);
        $timestamp = (int) $this->ftpFilesystem->getTimestamp($sourcePath);
        $this->filesToDownload[] = [
            self::FILE_DESTINATION_KEY => $destination,
            self::FILE_SOURCE_KEY => $sourcePath,
            self::FILE_TIMESTAMP_KEY => $timestamp,
        ];
    }

    private function download(FileStateRegistry $registry): int
    {
        $cbTimestampSort = function (array $a, array $b) {
            return intval($a[self::FILE_TIMESTAMP_KEY]) < intval($b[self::FILE_TIMESTAMP_KEY]);
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
            $fs->dumpFile(
                $file[self::FILE_DESTINATION_KEY],
                $this->ftpFilesystem->read($file[self::FILE_SOURCE_KEY])
            );
            $downloadedFiles++;
        }
        return $downloadedFiles;
    }
}
