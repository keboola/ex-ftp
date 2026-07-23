<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

use Keboola\Component\UserException;
use Keboola\FtpExtractor\AdapterFactory;
use Keboola\FtpExtractor\Config;
use Keboola\FtpExtractor\ConfigDefinition;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Sftp\SftpAdapter;
use phpseclib\Net\SFTP;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class AdapterFactoryTest extends TestCase
{
    /**
     * @dataProvider adapterConfigProvider
     * @psalm-param class-string<object> $expectedClass
     */
    public function testGetFtpsImplicitAdapter(Config $config, string $expectedClass): void
    {
        $this->assertInstanceOf(
            $expectedClass,
            AdapterFactory::getAdapter($config, new NullLogger())
        );
    }

    public function adapterConfigProvider(): array
    {
        return [
            [$this->provideTestConfig(ConfigDefinition::CONNECTION_TYPE_FTP), Ftp::class],
            [$this->provideTestConfig(ConfigDefinition::CONNECTION_TYPE_SFTP), SftpAdapter::class],
            [$this->provideTestConfig(ConfigDefinition::CONNECTION_TYPE_SSL_EXPLICIT), Ftp::class],
        ];
    }

    public function testWrongConnectionType(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->provideTestConfig("Blanka");
    }

    public function testInvalidSftpAdapterWithRelativePath(): void
    {
        $config = new Config(
            [
                'parameters' => [
                    'host' => 'ftp',
                    'username' => 'ftpuser',
                    '#password' => 'userpass',
                    'port' => 21,
                    'path' => 'rel',
                    'connectionType' => 'SFTP',
                    'timeout' => 1,
                ],
            ],
            new ConfigDefinition()
        );
        $this->expectException(UserException::class);
        $this->expectExceptionMessageMatches('/Could not login/');
        AdapterFactory::getAdapter($config, new NullLogger());
    }

    public function testSftpRootRaisesUserExceptionWhenWorkingDirectoryUnavailable(): void
    {
        // phpseclib's SFTP::pwd() returns false (not a string) when the working directory
        // cannot be resolved. The closure in setSftpRoot is typed ": string", so a false
        // return previously escaped as an uncaught TypeError (opaque internal error, exit 2).
        // It must now surface as a UserException (user error, exit 1) with a clear message.
        $connection = $this->createMock(SFTP::class);
        $connection->method('pwd')->willReturn(false);

        $adapter = $this->createMock(SftpAdapter::class);
        $adapter->method('getConnection')->willReturn($connection);

        $setSftpRoot = new \ReflectionMethod(AdapterFactory::class, 'setSftpRoot');
        $setSftpRoot->setAccessible(true);

        $this->expectException(UserException::class);
        $this->expectExceptionMessageMatches('/working directory/');

        // A relative source path forces the pwd() resolution branch (an absolute path would
        // take the early setRoot('/') return and never call pwd()).
        $setSftpRoot->invoke(null, $adapter, 'relative/path/*', new NullLogger());
    }

    private function provideTestConfig(string $connectionType): Config
    {
        return new Config(
            [
                'parameters' => [
                    'host' => 'ftp',
                    'username' => 'ftpuser',
                    '#password' => 'userpass',
                    'port' => 21,
                    'path' => '/absolute/path/*',
                    'connectionType' => $connectionType,
                ],
            ],
            new ConfigDefinition()
        );
    }
}
