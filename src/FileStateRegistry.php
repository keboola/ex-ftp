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
                $this->newestTimestamp = $cfg[self::NEWEST_TIMESTAMP_KEY];
            }

            if (isset($cfg[self::FILES_WITH_NEWEST_TIMESTAMP_KEY])) {
                $this->filesWithNewestTimestamp = $cfg[self::FILES_WITH_NEWEST_TIMESTAMP_KEY];
            }
        }
    }

    public function shouldBeFileUpdated(string $remotePath, int $timestamp): bool
    {
        if ($this->newestTimestamp < $timestamp) {
            $this->newestTimestamp = $timestamp;
            $this->filesWithNewestTimestamp = [];
        }

        if ($timestamp < $this->newestTimestamp) {
            return false;
        }

        if (in_array($remotePath, $this->filesWithNewestTimestamp)) {
            return false;
        }

        $this->filesWithNewestTimestamp[] = $remotePath;

        return true;
    }

    public function getFileStates(): array
    {
        return [
            self::NEWEST_TIMESTAMP_KEY => $this->newestTimestamp,
            self::FILES_WITH_NEWEST_TIMESTAMP_KEY => $this->filesWithNewestTimestamp,
        ];
    }
}
