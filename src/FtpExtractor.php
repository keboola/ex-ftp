<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use League\Flysystem\Filesystem as FtpFilesystem;
use League\Flysystem\Adapter\Ftp as Adapter;
use Webmozart\Glob\Glob;

class FtpExtractor
{
    private const RECURSIVE_COPY = true;
    private const IS_FILE = 'file';

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

    public function __construct(Config $config)
    {
        $this->ftpFilesystem = new FtpFilesystem(new Adapter($config->getConnectionConfig()));
        $this->onlyNewFiles = $config->isOnlyForNewFiles();
        $this->isWildcard = $config->isWildcard();
    }

    public function copyFiles(string $sourcePath, string $destionationPath, FileStateRegistry $registry): int
    {
        if ($this->isWildcard) {
            $cnt = $this->downloadFolder($sourcePath, $destionationPath, $registry);
        } else {
            $cnt = $this->downloadSingleFile($sourcePath, $destionationPath, $registry);
        }

        $registry->saveState();
        return $cnt;
    }

    private function downloadFolder(string $sourcePath, string $destinationPath, FileStateRegistry $registry): int
    {
        $basePath = Glob::getBasePath(GlobValidator::convertToAbsolute($sourcePath));
        $items = $this->ftpFilesystem->listContents($basePath, self::RECURSIVE_COPY);
        $downloadedCount = 0;
        foreach ($items as $item) {
            if ($this->isWildcard && !GlobValidator::validatePathAgainstGlob($item['path'], $sourcePath)) {
                continue;
            }

            if ($item['type'] === self::IS_FILE) {
                $downloadedCount += $this->downloadSingleFile($item['path'], $destinationPath, $registry);
            }
        }
        return $downloadedCount;
    }

    private function downloadSingleFile(string $sourcePath, string $destinationPath, FileStateRegistry $registry): int
    {
        $destination = $destinationPath . '/' . strtr($sourcePath, ['/' => '-']);
        $timestamp = (int) $this->ftpFilesystem->getTimestamp($sourcePath);
        if ($this->onlyNewFiles && !$registry->shouldBeFileUpdated($sourcePath, $timestamp)) {
            return 0;
        }

        $registry->saveFileTimestamp($sourcePath, $timestamp);
        file_put_contents($destination, $this->ftpFilesystem->read($sourcePath));
        return 1;
    }
}
