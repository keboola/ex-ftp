<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Exception;

use Keboola\Component\UserException;
use League\Flysystem\FileNotFoundException;

final class ExceptionConverter
{
    public static function handleCopyFilesException(\Throwable $e): void
    {
        // phpcs:disable
        if (preg_match_all('/(Could not login)|(getaddrinfo failed)|(Could not connect to)|(Cannot connect to)|(Root is invalid)|(The authenticity of)/', $e->getMessage())) {
            self::toUserException($e);
        }
        // phpcs:enable

        if ($e instanceof FileNotFoundException) {
            self::toUserException($e);
        }

        self::toApplicationException($e);
    }

    public static function handlePrepareToDownloaException(\Throwable $e): void
    {
        self::handleCopyFilesException($e);
    }

    public static function handleDownload(\Throwable $e): void
    {
        if ($e instanceof FileNotFoundException) {
            self::toUserException($e, sprintf(
                'Error while trying to download file: %s',
                $e->getMessage()
            ));
        }

        if ($e instanceof \ErrorException
            && preg_match_all('/Operation now in progress \(115\)/', $e->getMessage())) {
            self::toUserException($e, sprintf(
                'Connection was terminated. Check that the connection is not blocked by Firewall: %s',
                $e->getMessage()
            ));
        }

        self::toApplicationException($e);
    }

    public static function toUserException(\Throwable $e, ?string $customMessage = null): void
    {
        throw new UserException($customMessage ?: $e->getMessage(), $e->getCode(), $e);
    }

    private static function toApplicationException(\Throwable $e): void
    {
        throw new ApplicationException($e->getMessage(), $e->getCode(), $e);
    }
}
