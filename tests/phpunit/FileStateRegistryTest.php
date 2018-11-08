<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

use Keboola\FtpExtractor\FileStateRegistry;
use PHPUnit\Framework\TestCase;

class FileStateRegistryTest extends TestCase
{
    /**
     * @dataProvider sameSecondUpdateDataProvider
     * @dataProvider firstRunDataProvider
     */
    public function testRegistry(string $path, int $timestamp, FileStateRegistry $registry, bool $expected): void
    {
        $this->assertSame($expected, $registry->shouldBeFileUpdated($path, $timestamp));
    }

    public function firstRunDataProvider(): array
    {
        $registry = $this->getRegistry(1000, []);

        return [
            ['dir1/files/1.txt', 900, $registry, false],
            ['dir1/files/2.txt', 1000, $registry, true],
            ['dir1/files/3.txt', 1002, $registry, true],
            ['dir1/files/4.txt', 1005, $registry, true],
        ];
    }

    public function sameSecondUpdateDataProvider(): array
    {
        $lastFiles = [
            '/dir2/file1.csv',
            '/dir2/file2.csv',
        ];
        $registry = $this->getRegistry(1000, $lastFiles);

        return [
            ['/dir2/file2.csv', 1000, $registry, false],
            ['/dir2/file1.csv', 1000, $registry, false],
            ['/dir2/file3.csv', 1000, $registry, true],
            ['/dir2/file5.csv', 1000, $registry, true],
            ['/dir3/file1.csv', 1001, $registry, true],
            ['/dir3/file2.csv', 1001, $registry, true],
            ['/dir5/file3.csv', 1005, $registry, true],
        ];
    }

    private function getRegistry(int $newestTimestamp, array $files): FileStateRegistry
    {
        $stateFile = [
            FileStateRegistry::STATE_FILE_KEY => [
                FileStateRegistry::NEWEST_TIMESTAMP_KEY => $newestTimestamp,
                FileStateRegistry::FILES_WITH_NEWEST_TIMESTAMP_KEY => $files,
            ],
        ];

        return new FileStateRegistry($stateFile);
    }
}
