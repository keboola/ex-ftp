<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Exception;

use Keboola\Component\UserException;
use League\Flysystem\FileNotFoundException;

final class ExceptionConverter
{
    public static function resolve(\Throwable $e): void
    {
        if (self::isUserException($e)) {
            self::toUser($e);
        }

        throw new ApplicationException($e->getMessage(), $e->getCode(), $e);
    }

    public static function toUser(\Throwable $e, ?string $customMessage = null): void
    {
        throw new UserException($customMessage ?: $e->getMessage(), $e->getCode(), $e);
    }

    private static function isUserException(\Throwable $e): bool
    {
        return $e instanceof \RuntimeException
            || $e instanceof \LogicException
            || $e instanceof \ErrorException
            || $e instanceof FileNotFoundException;
    }
}
