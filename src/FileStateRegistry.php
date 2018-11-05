<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use Symfony\Component\Filesystem\Filesystem;

class FileStateRegistry
{
    private const IN_STATE_FILE = '/in/state.json';
    private const OUT_STATE_FILE = '/out/state.json';

    /**
     * @var array
     */
    private $fileStates;

    /**
     * @var string
     */
    private $dataDir;

    public function __construct(string $dataDir)
    {
        $this->dataDir = $dataDir;
        $this->fileStates = $this->parseInputState();
    }

    private function parseInputState(): array
    {
        $inFile = $this->dataDir . self::IN_STATE_FILE;
        if (file_exists($inFile)) {
            return json_decode(file_get_contents($inFile), true);
        }
        return [];
    }

    public function shouldBeFileUpdated(string $remotePath, int $timestamp): bool
    {
        if (!key_exists($remotePath, $this->fileStates)
            || $this->fileStates[$remotePath] < $timestamp
        ) {
            return true;
        }

        return false;
    }

    public function saveFileTimestamp(string $remotePath, int $timestamp): void
    {
        $this->fileStates[$remotePath] = $timestamp;
    }

    public function saveState(): void
    {
        (new Filesystem())->dumpFile($this->dataDir . self::OUT_STATE_FILE, json_encode($this->fileStates));
    }
}
