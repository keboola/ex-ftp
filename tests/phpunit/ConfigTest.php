<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

use Generator;
use Keboola\FtpExtractor\Config;
use Keboola\FtpExtractor\ConfigDefinition;
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
}
