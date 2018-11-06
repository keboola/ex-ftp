<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\FunctionalTests;

use Keboola\DatadirTests\DatadirTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class DatadirTest extends DatadirTestCase
{
    protected function setUp() :void
    {
        parent::setUp();

        $files = (new Finder())->files()->in(__DIR__ . '/../ftpInitContent/');
        $json = [];
        foreach ($files as $file) {
            /** @var SplFileInfo $file*/
            $json[$file->getRelativePathname()] = $file->getMTime();
        }

        $fs = new Filesystem();
        //dumping state where's nothing to update
        $state = [
            "dir1/recursive.bin" => $json["dir1/recursive.bin"],
        ];
        $fs->dumpFile(__DIR__ . '/nothing-to-update/source/data/in/state.json', json_encode($state));
        $fs->dumpFile(__DIR__ . '/nothing-to-update/expected/data/out/state.json', json_encode($state));
        $fs->dumpFile(__DIR__ . '/normal-download/expected/data/out/state.json', json_encode($state));
    }
}
