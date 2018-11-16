<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

use Keboola\FtpExtractor\FileStateRegistry;
use PHPUnit\Framework\TestCase;

class FileStateRegistryTest extends TestCase
{
    /**
     * @dataProvider firstRunDataProvider
     */
    public function testRegistry(array $files, FileStateRegistry $registry): void
    {
        foreach ($files as $file) {
            $this->assertSame(
                $file['expected'],
                $registry->shouldBeFileUpdated($file['name'], $file['timestamp']),
                sprintf("Bad decision for %s with timestamp %s", $file['name'], $file['timestamp'])
            );
        }
    }

    public function firstRunDataProvider(): array
    {
        $firstRunFiles = [
            ['name' => 'dir1/files/1.txt', 'timestamp' => 900, 'expected' => false],
            ['name' => 'dir1/files/2.txt', 'timestamp' => 1000, 'expected' => true],
            ['name' => 'dir1/files/3.txt', 'timestamp' => 1002, 'expected' => true],
            ['name' => 'dir1/files/4.txt', 'timestamp' => 1005, 'expected' => true],
        ];

        $sameSecondUpdateFiles = [
            ['name' => '/dir2/file2.csv', 'timestamp' => 1000, 'expected' => false],
            ['name' => '/dir2/file1.csv', 'timestamp' => 1000, 'expected' => false],
            ['name' => '/dir2/file3.csv', 'timestamp' => 1000, 'expected' => true],
            ['name' => '/dir2/file5.csv', 'timestamp' => 1000, 'expected' => true],
            ['name' => '/dir3/file1.csv', 'timestamp' => 1001, 'expected' => true],
            ['name' => '/dir3/file2.csv', 'timestamp' => 1001, 'expected' => true],
            ['name' => '/dir5/file3.csv', 'timestamp' => 1005, 'expected' => true],
        ];

        $lastFiles = [
            '/dir2/file1.csv',
            '/dir2/file2.csv',
        ];

        return [
           'firstRun' => [$firstRunFiles, $this->getRegistry(1000, [])],
           'secondRunWithLastFiles' => [$sameSecondUpdateFiles, $this->getRegistry(1000, $lastFiles)],
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
