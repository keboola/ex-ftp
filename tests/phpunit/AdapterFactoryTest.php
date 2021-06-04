<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

use Keboola\Component\UserException;
use Keboola\FtpExtractor\AdapterFactory;
use Keboola\FtpExtractor\Config;
use Keboola\FtpExtractor\ConfigDefinition;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\PhpseclibV2\SftpAdapter;
use PHPUnit\Framework\TestCase;
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
            AdapterFactory::getAdapter($config)
        );
    }

    public function adapterConfigProvider(): iterable
    {
        yield 'ftp' =>
            [$this->provideTestConfig(ConfigDefinition::CONNECTION_TYPE_FTP), FtpAdapter::class];
        yield 'sftp' =>
            [$this->provideTestConfig(ConfigDefinition::CONNECTION_TYPE_SFTP), SftpAdapter::class];
         yield 'ftp-ssl' =>
            [$this->provideTestConfig(ConfigDefinition::CONNECTION_TYPE_SSL_EXPLICIT), FtpAdapter::class];
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
        $this->expectExceptionMessageMatches('/Unable to authenticate using a password/');
        AdapterFactory::getAdapter($config);
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
