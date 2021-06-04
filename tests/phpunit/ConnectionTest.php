<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

use Keboola\Component\UserException;
use Keboola\FtpExtractor\Config;
use Keboola\FtpExtractor\ConfigDefinition;
use Keboola\FtpExtractor\FileStateRegistry;
use Keboola\FtpExtractor\FtpExtractor;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    /**
     * @dataProvider invalidConnectionProvider
     */
    public function testFalseConnection(string $connectionType, array $connectionConfig): void
    {
        $config = $this->createMock(Config::class);
        $config->method('isOnlyForNewFiles')->willReturn(false);
        $config->method('getPathToCopy')->willReturn('/foo.bar');
        $config->method('getConnectionType')->willReturn($connectionType);
        $config->method('getConnectionConfig')->willReturn($connectionConfig);

        $handler = new TestHandler();
        $extractor = new FtpExtractor(
            $config,
            new FileStateRegistry([]),
            (new Logger('ftpExtractorTest'))->pushHandler($handler)
        );

        try {
            $extractor->copyFiles('source', 'destination');
        } catch (\Throwable $e) {
            $this->assertInstanceOf(UserException::class, $e);
            $this->assertCount(3, $handler->getRecords());
            $this->assertMatchesRegularExpression(
                '/(Unable to connect to host .+ at port .+)|(Cannot connect to .+:.+)/',
                $e->getMessage()
            );

            foreach ($handler->getRecords() as $count => $record) {
                if ($count === 0) {
                    $this->assertEquals('Connecting to host ...', $record['message']);
                    continue;
                }

                $this->assertMatchesRegularExpression(
                    '/(Unable to connect to host .+ at port .+)|(Cannot connect to .+:.+)/',
                    $record['message']
                );
                $this->assertMatchesRegularExpression(sprintf('/Retrying\.\.\. \[%dx\]$/', $count), $record['message']);
            }
        }
    }


    public function invalidConnectionProvider(): array
    {
        return [
            'ftp-non-existing-server' => [
                ConfigDefinition::CONNECTION_TYPE_FTP,
                [
                    'host' => 'localhost',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 21,
                ],
            ],
            'ftps-non-existing-server' => [
                ConfigDefinition::CONNECTION_TYPE_FTP,
                [
                    'host' => 'localhost',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 21,
                    'ssl' => true,
                ],
            ],
            'sftp-non-existing-server' => [
                ConfigDefinition::CONNECTION_TYPE_SFTP,
                [
                    'host' => 'localhost',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 22,
                ],
            ],
            'sftp-non-existing-host' => [
                ConfigDefinition::CONNECTION_TYPE_SFTP,
                [
                    'host' => 'non-existing-host.keboola',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 22,
                ],
            ],
            'sftp-non-existing-server-and-port' => [
                ConfigDefinition::CONNECTION_TYPE_SFTP,
                [
                    'host' => 'non-existing-host.keboola',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 220,
                    'path' => 'non-exists',
                ],
            ],
            'ftp-non-existing-host' => [
                ConfigDefinition::CONNECTION_TYPE_FTP,
                [
                    'host' => 'non-existing-host.keboola',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 21,
                ],
            ],
            'ftp-non-existing-host-and-port' => [
                ConfigDefinition::CONNECTION_TYPE_FTP,
                [
                    'host' => 'non-existing-host.keboola',
                    'username' => 'bob',
                    'password' => 'marley',
                    'port' => 50000,
                ],
            ],
        ];
    }
}
