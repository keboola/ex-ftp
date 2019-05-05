<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\FunctionalTests;

use Keboola\Component\JsonHelper;
use Keboola\DatadirTests\DatadirTestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class DatadirTest extends DatadirTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $files = (new Finder())->files()->in(__DIR__ . '/../ftpInitContent/');
        $timestamps = [];
        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            $timestamps[$file->getRelativePathname()] = $file->getMTime();
        }

        // --- normal-donwload test ----
        $state = [
            "ex-ftp-state" => [
                "newest-timestamp" => 0,
                "last-timestamp-files" => [],
            ],
        ];
        JsonHelper::writeFile(__DIR__ . '/normal-download/expected/data/out/state.json', $state);

        // --- special-chars test ---
        $state = [
            "ex-ftp-state" => [
                "newest-timestamp" => 0,
                "last-timestamp-files" => [],
            ],
        ];
        JsonHelper::writeFile(__DIR__ . '/special-chars/expected/data/out/state.json', $state);

        // --- nothing-to-update tests ---
        $state = [
            "ex-ftp-state" => [
                "newest-timestamp" => $timestamps["dir1/recursive.bin"],
                "last-timestamp-files" => ["dir1/recursive.bin"],
            ],
        ];
        JsonHelper::writeFile(__DIR__ . '/nothing-to-update/expected/data/out/state.json', $state);
        JsonHelper::writeFile(__DIR__ . '/nothing-to-update/source/data/in/state.json', $state);

        // --- specific-directory test ----
        $state = [
            "ex-ftp-state" => [
                "newest-timestamp" => 0,
                "last-timestamp-files" => [],
            ],
        ];
        JsonHelper::writeFile(__DIR__ . '/specific-directory/expected/data/out/state.json', $state);

        // --- recurse-manually test ----
        $state = [
            "ex-ftp-state" => [
                "newest-timestamp" => 0,
                "last-timestamp-files" => [],
            ],
        ];
        JsonHelper::writeFile(__DIR__ . '/recurse-manually/expected/data/out/state.json', $state);
    }
}
