<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

use Generator;
use Keboola\FtpExtractor\Config;
use Keboola\FtpExtractor\ConfigDefinition;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ConfigTest extends TestCase
{
    public function listingProvider(): array
    {
        return [
            [false, ConfigDefinition::LISTING_RECURSION],
            [true, ConfigDefinition::LISTING_MANUAL],
            [false, null],
        ];
    }

    /** @dataProvider listingProvider */
    public function testListingRecursion(bool $recurseManually, ?string $listing): void
    {
        $configArray = [
            'parameters' => [
                'host' => 'ftp',
                'username' => 'ftpuser',
                '#password' => 'userpass',
                'port' => 21,
                'path' => 'rel',
                'connectionType' => 'SFTP',
            ],
        ];
        if ($listing) {
            $configArray['parameters']['listing'] = $listing;
        }
        $config = new Config(
            $configArray,
            new ConfigDefinition()
        );
        $this->assertSame($recurseManually, $config->getConnectionConfig()['recurseManually']);
    }

    public function testInvalidListingOption(): void
    {
        $configArray = [
            'parameters' => [
                'host' => 'ftp',
                'username' => 'ftpuser',
                '#password' => 'userpass',
                'port' => 21,
                'path' => 'rel',
                'connectionType' => 'SFTP',
                'listing' => 'non-existing',
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);

        new Config(
            $configArray,
            new ConfigDefinition()
        );
    }

    /** @dataProvider invalidSSHDataProvider */
    public function testInvalidSSHConfig(array $sshConfig, string $expectedMessage): void
    {
        $configArray = [
            'parameters' => [
                'host' => 'hostName',
                'username' => 'ftpuser',
                '#password' => 'userpass',
                'port' => 21,
                'path' => 'rel',
                'connectionType' => 'FTP',
                'ssh' => $sshConfig,
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedMessage);
        new Config($configArray, new ConfigDefinition());
    }

    /** @dataProvider validSSHDataProvider */
    public function testValidSSHConfig(array $sshConfig): void
    {
        $configArray = [
            'parameters' => [
                'host' => 'hostName',
                'username' => 'ftpuser',
                '#password' => 'userpass',
                'port' => 21,
                'path' => 'rel',
                'connectionType' => 'FTP',
                'onlyNewFiles' => false,
                '#privateKey' => '',
                'timeout' => 60,
                'listing' => 'recursion',
                'ignorePassiveAddress' => false,
                'ssh' => $sshConfig,
            ],
        ];

        $config = new Config($configArray, new ConfigDefinition());

        Assert::assertEquals($configArray, $config->getData());
    }
    
    /**
     * @dataProvider invalidApprovedHostnameDataProvider
     */
    public function testInvalidApprovedHostname(array $approvedHostnameConfig): void
    {
        $configArray = [
            'image_parameters' => [
                'approvedHostnames' => [$approvedHostnameConfig],
            ],
            'parameters' => [
                'host' => 'hostName',
                'username' => 'ftpuser',
                '#password' => 'userpass',
                'port' => 21,
                'path' => 'rel',
                'connectionType' => 'SFTP',
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Hostname "hostName" with port "21" is not approved.');
        new Config(
            $configArray,
            new ConfigDefinition()
        );
    }

    public function testValidApprovedHostname(): void
    {
        $configArray = [
            'image_parameters' => [
                'approvedHostnames' => [
                    [
                        'host' => 'ftpHost',
                        'port' => 21,
                    ],
                ],
            ],
            'parameters' => [
                'host' => 'ftpHost',
                'username' => 'ftpuser',
                '#password' => 'userpass',
                'port' => 21,
                'path' => 'rel',
                'connectionType' => 'SFTP',
            ],
        ];

        $config = new Config(
            $configArray,
            new ConfigDefinition()
        );

        $this->assertEquals(Config::class, get_class($config));
    }

    public function invalidApprovedHostnameDataProvider(): Generator
    {
        yield "invalid-host" => [
            [
                'host' => 'invalidHost',
                'port' => 21,
            ],
        ];

        yield "invalid-port" => [
            [
                'host' => 'hostName',
                'port' => 22,
            ],
        ];

        yield "invalid-both" => [
            [
                'host' => 'invalidHost',
                'port' => 22,
            ],
        ];
    }

    public function invalidSSHDataProvider(): Generator
    {
        yield 'missing-keys' => [
            [
                'enabled' => true,
                'sshHost' => 'localhost',
                'user' => 'user',
                'passivePortRange' => '10000:10001',
            ],
            'The child config "keys" under "root.parameters.ssh" must be configured.',
        ];
        yield 'missing-private-key' => [
            [
                'enabled' => true,
                'keys' => [
                    'public' => 'publicKey',
                ],
                'sshHost' => 'localhost',
                'user' => 'user',
                'passivePortRange' => '10000:10001',
            ],
            'The child config "#private" under "root.parameters.ssh.keys" must be configured.',
        ];
        yield 'missing-public-key' => [
            [
                'enabled' => true,
                'keys' => [
                    '#private' => 'privateKey',
                ],
                'sshHost' => 'localhost',
                'user' => 'user',
                'passivePortRange' => '10000:10001',
            ],
            'The child config "public" under "root.parameters.ssh.keys" must be configured.',
        ];
        yield 'missing-ssh-host' => [
            [
                'enabled' => true,
                'keys' => [
                    '#private' => 'privateKey',
                    'public' => 'publicKey',
                ],
                'user' => 'user',
                'passivePortRange' => '10000:10001',
            ],
            'The child config "sshHost" under "root.parameters.ssh" must be configured.',
        ];
        yield 'missing-user' => [
            [
                'enabled' => true,
                'keys' => [
                    '#private' => 'privateKey',
                    'public' => 'publicKey',
                ],
                'sshHost' => 'localhost',
                'passivePortRange' => '10000:10001',
            ],
            'The child config "user" under "root.parameters.ssh" must be configured.',
        ];
        yield 'missing-passivePortRange' => [
            [
                'enabled' => true,
                'keys' => [
                    '#private' => 'privateKey',
                    'public' => 'publicKey',
                ],
                'sshHost' => 'localhost',
                'user' => 'user',
            ],
            'The child config "passivePortRange" under "root.parameters.ssh" must be configured.',
        ];
        yield 'wrong-port-range' => [
            [
                'enabled' => true,
                'keys' => [
                    '#private' => 'privateKey',
                    'public' => 'publicKey',
                ],
                'sshHost' => 'localhost',
                'user' => 'user',
                'passivePortRange' => '10000:9000',
            ],
            'The Range From must be less than Range To.',
        ];
    }

    public function validSSHDataProvider(): Generator
    {
        yield 'stringPort' => [
            [
                'enabled' => true,
                'keys' => [
                    '#private' => 'privateKey',
                    'public' => 'publicKey',
                ],
                'sshHost' => 'localhost',
                'sshPort' => '12345',
                'user' => 'user',
                'passivePortRange' => '10000:11000',
            ],
        ];

        yield 'intPort' => [
            [
                'enabled' => true,
                'keys' => [
                    '#private' => 'privateKey',
                    'public' => 'publicKey',
                ],
                'sshHost' => 'localhost',
                'sshPort' => 12345,
                'user' => 'user',
                'passivePortRange' => '10000:11000',
            ],
        ];
    }
}
