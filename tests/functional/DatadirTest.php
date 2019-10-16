<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\FunctionalTests;

use Keboola\Csv\CsvWriter;
use Keboola\Component\JsonHelper;
use Keboola\DatadirTests\AbstractDatadirTestCase;
use Keboola\DatadirTests\DatadirTestSpecificationInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class DatadirTest extends AbstractDatadirTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $files = (new Finder())->files()->in(__DIR__ . '/../ftpInitContent/');
        $timestamps = [];
        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            if ($file->getFilename() === 'a_brand_new_file.csv') {
                unlink(__DIR__ . '/../ftpInitContent/a_brand_new_file.csv');
                continue;
            }
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

        // --- manual-recursion test ----
        $state = [
            "ex-ftp-state" => [
                "newest-timestamp" => 0,
                "last-timestamp-files" => [],
            ],
        ];
        JsonHelper::writeFile(__DIR__ . '/manual-recursion/expected/data/out/state.json', $state);

        // --- only-new-files tests ---
        $inputState = [
            "ex-ftp-state" => [
                "newest-timestamp" => 0,
                "last-timestamp-files" => [],
            ],
        ];
        $outputState = [
            "ex-ftp-state" => [
                "newest-timestamp" => $timestamps["file_1.txt"],
                "last-timestamp-files" => ["file_1.txt", "Zvlášť zákeřný učeň s ďolíčky běží podél zóny úlů.csv"],
            ],
        ];
        JsonHelper::writeFile(__DIR__ . '/only-new-files/expected/data/out/state.json', $outputState);
        JsonHelper::writeFile(__DIR__ . '/only-new-files/source/data/in/state.json', $inputState);

        // -- new-files-from-old-state test --
        $inputState = [
            "ex-ftp-state" => [
                "newest-timestamp" => $timestamps["file_1.txt"],
                "last-timestamp-files" => ["file_1.txt", "Zvlášť zákeřný učeň s ďolíčky běží podél zóny úlů.csv"],
            ],
        ];
        JsonHelper::writeFile(__DIR__ . '/new-files-from-old-state/source/data/in/state.json', $inputState);
    }

    /**
     * @dataProvider provideDatadirSpecifications
     */
    public function testDatadir(DatadirTestSpecificationInterface $specification): void
    {
        $tempDatadir = $this->getTempDatadir($specification);

        $sourceDatadir = $specification->getSourceDatadirDirectory();

        if ($this->doesNameMatchDatadir('new-files-from-old-state', $sourceDatadir)) {
            // -- new-files-from-old-state test --
            $newCsvFile = __DIR__ . '/../ftpInitContent/a_brand_new_file.csv';
            $expectingCsvFile = __DIR__ . '/new-files-from-old-state/expected/data/out/files/a_brand_new_file.csv';

            $csvWriter = new CsvWriter($newCsvFile);
            $csvWriter->writeRow(['a', 'csv', 'file']);
            $fs = new Filesystem();
            $fs->copy($newCsvFile, $expectingCsvFile);
            $freshTimestamp = (new SplFileInfo($newCsvFile, "", ""))->getMTime();
            $outputState = [
                "ex-ftp-state" => [
                    "newest-timestamp" => $freshTimestamp,
                    "last-timestamp-files" => ["a_brand_new_file.csv"],
                ],
            ];
            JsonHelper::writeFile(__DIR__ . '/new-files-from-old-state/expected/data/out/state.json', $outputState);
        }

        $process = $this->runScript($tempDatadir->getTmpFolder());

        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    private function doesNameMatchDatadir(string $testName, string $datadir): bool
    {
        return in_array($testName, explode('/', $datadir));
    }
}
