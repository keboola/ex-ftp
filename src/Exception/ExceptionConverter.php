<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Exception;

use Keboola\Component\UserException;
use League\Flysystem\FileNotFoundException;

final class ExceptionConverter
{
    public static function handleCopyFilesException(\Throwable $e): void
    {
        if ($e instanceof \RuntimeException
            || $e instanceof \LogicException
            || $e instanceof \ErrorException
            || $e instanceof FileNotFoundException) {
            self::toUser($e);
        }

        self::toApplication($e);
    }

    public static function handlePrepareToDownloaException(\Throwable $e): void
    {
        self::handleCopyFilesException($e);
    }

    public static function toUser(\Throwable $e, ?string $customMessage = null): void
    {
        throw new UserException($customMessage ?: $e->getMessage(), $e->getCode(), $e);
    }

    private static function toApplication(\Throwable $e): void
    {
        throw new ApplicationException($e->getMessage(), $e->getCode(), $e);
    }
}
