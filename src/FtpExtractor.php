<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use League\Flysystem\Filesystem as FtpFilesystem;
use League\Flysystem\Adapter\Ftp as Adapter;

class FtpExtractor
{
    private const RECURSIVE_COPY = true;
    private const IS_FILE = 'file';

    /**
     * @var FtpFilesystem
     */
    private $ftpFilesystem;

    public function __construct(Config $config)
    {
        $this->ftpFilesystem = new FtpFilesystem(new Adapter($config->getConnectionConfig()));
    }

    /**
     * @param string $sourcePath
     * @param string $destionationPath
     * @return int Number of downloaded files
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function copyFiles(string $sourcePath, string $destionationPath): int
    {
        $items = $this->ftpFilesystem->listContents($sourcePath, self::RECURSIVE_COPY);
        $downloadedCount = 0;
        foreach ($items as $item) {
            $destination = $destionationPath . strtr($item['path'], ['/'=>'_']);
            if ($item['type'] === self::IS_FILE) {
                file_put_contents($destination, $this->ftpFilesystem->read($item['path']));
                $downloadedCount++;
            }
        }
        return $downloadedCount;
    }
}
