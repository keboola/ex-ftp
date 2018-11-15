<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

use Keboola\Component\UserException;
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
     * @dataProvider invalidConnectionProvider
     */
    public function testFalseConnection(AdapterInterface $adapter): void
    {
        $fs = new Filesystem($adapter);

        $extractor = new FtpExtractor(false, $fs, new NullLogger());
        $this->expectException(UserException::class);
        $this->expectExceptionMessageRegExp(
            '/(Could not login)|(getaddrinfo failed)|(Could not connect to)|(Cannot connect to)/'
        );
        $extractor->copyFiles('source', 'destination', new FileStateRegistry([]));
    }


    public function invalidConnectionProvider(): array
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
