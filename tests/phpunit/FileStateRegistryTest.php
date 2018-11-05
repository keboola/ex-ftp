<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

use Keboola\FtpExtractor\FileStateRegistry;
use Keboola\Temp\Temp;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class FileStateRegistryTest extends TestCase
{

    public function testRegistry(): void
    {
        $temp = new Temp('state-registry');
        $temp->initRunFolder();
        $dataDir = $temp->getTmpFolder();

        $this->createStateFile($dataDir . '/in/state.json');

        $registry = new FileStateRegistry($dataDir);

        $this->assertTrue($registry->shouldBeFileUpdated('file-abcd-1234.txt', 1541410003));
        $registry->saveFileTimestamp('file-abcd-1234.txt', 1541410003);
        $this->assertFalse($registry->shouldBeFileUpdated('file_defg-4567.bin', 1541410005));
        $registry->saveFileTimestamp('file_defg-4567.bin', 1541410005);
        $this->assertFalse($registry->shouldBeFileUpdated('test_4444-4567.bin', 1541410009));
        $registry->saveFileTimestamp('test_4444-4567.bin', 1541410009);

        $registry->saveState();

        $outState = file_get_contents($dataDir . '/out/state.json');

        $this->assertSame(
            [
                'file-abcd-1234.txt' => 1541410003,
                'file_defg-4567.bin' => 1541410005,
                'test_4444-4567.bin' => 1541410009,
            ],
            json_decode($outState, true),
            'state.json was not saved correctly into out folder.'
        );
    }

    private function createStateFile(string $filePath): void
    {
        $content = [
            'file-abcd-1234.txt' => 1541410001,
            'file_defg-4567.bin' => 1541410005,
            'test_4444-4567.bin' => 1541410010,
        ];

        (new Filesystem())->dumpFile($filePath, json_encode($content));
    }


    public function testFirstRegistryRun(): void
    {
        $temp = new Temp('state-registry-1');
        $temp->initRunFolder();
        $dataDir = $temp->getTmpFolder();

        $registry = new FileStateRegistry($dataDir);

        $this->assertTrue($registry->shouldBeFileUpdated('1-file-abcd-1234.txt', 1541410002));
        $registry->saveFileTimestamp('1-file-abcd-1234.txt', 1541410002);
        $this->assertTrue($registry->shouldBeFileUpdated('1-file_defg-4567.bin', 1541410002));
        $registry->saveFileTimestamp('1-file_defg-4567.bin', 1541410002);
        $this->assertTrue($registry->shouldBeFileUpdated('1-test_4444-4567.bin', 1541410002));
        $registry->saveFileTimestamp('1-test_4444-4567.bin', 1541410002);

        $registry->saveState();

        $outState = file_get_contents($dataDir . '/out/state.json');

        $this->assertSame(
            [
                '1-file-abcd-1234.txt' => 1541410002,
                '1-file_defg-4567.bin' => 1541410002,
                '1-test_4444-4567.bin' => 1541410002,
            ],
            json_decode($outState, true),
            'state.json was not saved correctly into out folder.'
        );
    }
}
