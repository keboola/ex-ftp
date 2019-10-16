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
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    /**
     * @dataProvider invalidConnectionProvider
     */
    public function testFalseConnection(AdapterInterface $adapter): void
    {
        $handler = new TestHandler();
        $extractor = new FtpExtractor(
            false,
            new Filesystem($adapter),
            new FileStateRegistry([]),
            (new Logger('ftpExtractorTest'))->pushHandler($handler)
        );

        try {
            $extractor->copyFiles('source', 'destination');
        } catch (\Throwable $e) {
            $this->assertInstanceOf(UserException::class, $e);
            $this->assertCount(3, $handler->getRecords());
            $this->assertRegExp(
                '/(Could not login)|(getaddrinfo failed)|(Could not connect to)|(Cannot connect to)/',
                $e->getMessage()
            );

            foreach ($handler->getRecords() as $count => $record) {
                if ($count === 0) {
                    $this->assertEquals('Connecting to host ...', $record['message']);
                    continue;
                }

                $this->assertRegExp(
                    '/(Could not login)|(getaddrinfo failed)|(Could not connect to)|(Cannot connect to)/',
                    $record['message']
                );
                $this->assertRegExp(sprintf('/Retrying\.\.\. \[%dx\]$/', $count), $record['message']);
            }
        }
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
