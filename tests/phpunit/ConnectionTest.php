<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

use Keboola\Component\UserException;
use Keboola\FtpExtractor\FileStateRegistry;
use Keboola\FtpExtractor\FtpExtractor;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\PhpseclibV3\ConnectionProvider;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Throwable;

class ConnectionTest extends TestCase
{
    /**
     * @dataProvider invalidConnectionProvider
     */
    public function testFalseConnection(FilesystemAdapter $adapter): void
    {
        $handler = new TestHandler();
        $extractor = new FtpExtractor(
            false,
            new Filesystem($adapter),
            new FileStateRegistry([]),
            (new Logger('ftpExtractorTest'))->pushHandler($handler),
        );

        try {
            $extractor->copyFiles('source', 'destination');
        } catch (Throwable $e) {
            $this->assertInstanceOf(UserException::class, $e);
            $this->assertCount(2, $handler->getRecords());
            $this->assertMatchesRegularExpression(
                '/(getaddrinfo failed)|(Unable to connect to)/',
                $e->getMessage(),
            );
        }

        foreach ($handler->getRecords() as $count => $record) {
            $this->assertMatchesRegularExpression(
                '/(Could not login)|(getaddrinfo for)|(Could not connect to)|(Unable to connect)/',
                $record['message'],
            );
            $this->assertMatchesRegularExpression(sprintf('/Retrying\.\.\. \[%dx\]$/', $count+1), $record['message']);
        }
    }


    public function invalidConnectionProvider(): array
    {
        return [
            'ftp-non-existing-server' => [
                new FtpAdapter(FtpConnectionOptions::fromArray([
                    'host' => 'localhost',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 21,
                ])),
            ],
            'ftps-non-existing-server' => [
                new FtpAdapter(FtpConnectionOptions::fromArray([
                    'host' => 'localhost',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 21,
                    'ssl' => true,
                ])),
            ],
            'sftp-non-existing-server' => [
                new SftpAdapter(SftpConnectionProvider::fromArray([
                    'host' => 'localhost',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 22,
                ]), '/'),
            ],
            'sftp-non-existing-host' => [
                new SftpAdapter(SftpConnectionProvider::fromArray([
                    'host' => 'non-existing-host.keboola',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 22,
                ]), '/'),
            ],
            'sftp-non-existing-server-and-port' => [
                new SftpAdapter(SftpConnectionProvider::fromArray([
                    'host' => 'non-existing-host.keboola',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 220,
                    'path' => 'non-exists',
                ]), '/'),
            ],
            'ftp-non-existing-host' => [
                new FtpAdapter(FtpConnectionOptions::fromArray([
                    'host' => 'non-existing-host.keboola',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 21,
                ])),
            ],
            'ftp-non-existing-host-and-port' => [
                new FtpAdapter(FtpConnectionOptions::fromArray([
                    'host' => 'non-existing-host.keboola',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 50000,
                ])),
            ],
        ];
    }
}
