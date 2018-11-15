<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

use Keboola\Component\UserException;
use Keboola\FtpExtractor\AdapterFactory;
use Keboola\FtpExtractor\Config;
use Keboola\FtpExtractor\ConfigDefinition;
use Keboola\FtpExtractor\FileStateRegistry;
use Keboola\FtpExtractor\FtpExtractor;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\Sftp\SftpAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ConnectionTest extends TestCase
{
    /**
     * @dataProvider falseConnectionProvider
     */
    public function testFalseConnection(AdapterInterface $adapter): void
    {
        $fs = new Filesystem($adapter);

        $extractor = new FtpExtractor(false, $fs, new NullLogger());
        $this->expectException(UserException::class);
        $extractor->copyFiles('source', 'destination', new FileStateRegistry([]));
    }

    public function testFalseSftpConnection(): void
    {
        $config = new Config(
            [
                'parameters' => [
                    'host' => 'ftp',
                    'username' => 'ftpuser',
                    '#password' => 'userpass',
                    'port' => 21,
                    'path' => 'abs',
                    'connectionType' => 'SFTP',
                    'timeout' => 1,
                ],
            ],
            new ConfigDefinition()
        );
        $this->expectException(UserException::class);
        $adapter = AdapterFactory::getAdapter($config);
        $fs = new Filesystem($adapter);
        $extractor = new FtpExtractor(false, $fs, new NullLogger());
        $extractor->copyFiles('source', 'destination', new FileStateRegistry([]));
    }


    public function falseConnectionProvider(): array
    {
        return [
            'ftp-non-existing-server' => [
                new Ftp([
                    'host' => 'localhost',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 21,
                ]),
            ],
            'ftps-non-existing-server' => [
                new Ftp([
                    'host' => 'localhost',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 21,
                    'ssl' => 1,
                ]),
            ],
            'sftp-non-existing-server' => [
                new SftpAdapter([
                    'host' => 'localhost',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 22,
                ]),
            ],
            'sftp-non-existing-host' => [
                new SftpAdapter([
                    'host' => 'non-existing-host.keboola',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 22,
                ]),
            ],
            'sftp-non-existing-server-and-port' => [
                new SftpAdapter([
                    'host' => 'non-existing-host.keboola',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 220,
                    'path' => 'non-exists',
                ]),
            ],
            'ftp-non-existing-host' => [
                new Ftp([
                    'host' => 'non-existing-host.keboola',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 21,
                ]),
            ],
            'ftp-non-existing-host-and-port' => [
                new Ftp([
                    'host' => 'non-existing-host.keboola',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 50000,
                ]),
            ],
        ];
    }
}
