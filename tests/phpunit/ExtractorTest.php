<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

use Keboola\FtpExtractor\FtpExtractorComponent;
use Keboola\Temp\Temp;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

class ExtractorTest extends TestCase
{
    public function testConnection(): void
    {
        $temp = new Temp('ex-storage');
        $temp->initRunFolder();
        $fs = new Filesystem();

        $configFile = [
            'action' => 'run',
            'parameters' => [
                'host' => 'ftp',
                'username' => 'ftpuser',
                'password' => 'userpass',
                'port' => 21,
                'path' => '/',
            ],
        ];

        $baseDir = $temp->getTmpFolder();
        $fs->dumpFile($baseDir . '/config.json', json_encode($configFile));
        putenv('KBC_DATADIR=' . $baseDir);
        $fs->mkdir($baseDir . '/out/files/');

        $app = new FtpExtractorComponent(new NullLogger());
        $app->run();
    }
}
