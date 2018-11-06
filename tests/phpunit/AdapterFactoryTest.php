<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

use Keboola\FtpExtractor\AdapterFactory;
use Keboola\FtpExtractor\Config;
use Keboola\FtpExtractor\ConfigDefinition;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Sftp\SftpAdapter;
use PHPUnit\Framework\TestCase;

class AdapterFactoryTest extends TestCase
{
    public function testGetFtpAdapter(): void
    {
        $this->assertInstanceOf(
            Ftp::class,
            AdapterFactory::getAdapter(
                $this->provideTestConfig(ConfigDefinition::CONNECTION_TYPE_FTP)
            )
        );
    }

    public function testGetSftpAdapter(): void
    {
        $this->assertInstanceOf(
            SftpAdapter::class,
            AdapterFactory::getAdapter(
                $this->provideTestConfig(ConfigDefinition::CONNECTION_TYPE_SFTP)
            )
        );
    }

    public function testGetFtpsImplicitAdapter(): void
    {
        $this->assertInstanceOf(
            Ftp::class,
            AdapterFactory::getAdapter(
                $this->provideTestConfig(ConfigDefinition::CONNECTION_TYPE_SSL_IMPLICIT)
            )
        );
    }

    private function provideTestConfig(string $connectionType): Config
    {
        return new Config(
            [
                'parameters' => [
                    'host' => 'ftp',
                    'username' => 'ftpuser',
                    'password' => 'userpass',
                    'port' => 21,
                    'path' => 'dir1/*',
                    'wildcard' => true,
                    'connectionType' => $connectionType,
                ],
            ],
            new ConfigDefinition()
        );
    }
}
