<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

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
}
