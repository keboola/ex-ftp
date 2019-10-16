<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

class FileStateRegistry
{
    public const STATE_FILE_KEY = 'ex-ftp-state';
    public const NEWEST_TIMESTAMP_KEY = 'newest-timestamp';
    public const FILES_WITH_NEWEST_TIMESTAMP_KEY = 'last-timestamp-files';

    /**
     * @var int
     */
    private $newestTimestamp;

    /**
     * @var array
     */
    private $filesWithNewestTimestamp;

    public function __construct(array $stateFile)
    {
        $this->newestTimestamp = 0;
        $this->filesWithNewestTimestamp = [];
        if (isset($stateFile[self::STATE_FILE_KEY])) {
            $cfg = $stateFile[self::STATE_FILE_KEY];

            if (isset($cfg[self::NEWEST_TIMESTAMP_KEY])) {
                $this->newestTimestamp = intval($cfg[self::NEWEST_TIMESTAMP_KEY]);
            }

            if (isset($cfg[self::FILES_WITH_NEWEST_TIMESTAMP_KEY])) {
                $this->filesWithNewestTimestamp = (array) $cfg[self::FILES_WITH_NEWEST_TIMESTAMP_KEY];
            }
        }
    }

    public function shouldBeFileUpdated(string $remotePath, int $timestamp): bool
    {
        if ($this->newestTimestamp <= $timestamp && !in_array($remotePath, $this->filesWithNewestTimestamp)) {
            return true;
        }
        return false;
    }

    public function updateOutputState(string $remotePath, int $timestamp): void
    {
        // if the file has a greater timestamp than our newest, then reset our values.
        if ($this->newestTimestamp < $timestamp) {
            $this->newestTimestamp = $timestamp;
            $this->filesWithNewestTimestamp = [$remotePath];
        } else if ($this->newestTimestamp = $timestamp) {
            $this->filesWithNewestTimestamp[] = $remotePath;
        }
    }

    public function getFileStates(): array
    {
        return [
            self::NEWEST_TIMESTAMP_KEY => $this->newestTimestamp,
            self::FILES_WITH_NEWEST_TIMESTAMP_KEY => $this->filesWithNewestTimestamp,
        ];
    }
}
