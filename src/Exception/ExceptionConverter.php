<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Exception;

use Keboola\Component\UserException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemException;
use League\Flysystem\Sftp\SftpAdapterException;

final class ExceptionConverter
{
    public static function handleCopyFilesException(\Throwable $e): void
    {
        if ($e instanceof SftpAdapterException) {
            self::toUserException($e);
        }

        if ($e instanceof FilesystemException) {
            self::toUserException($e);
        }

        // Catch user_error from phpseclib
        // phpcs:disable
        if (preg_match_all('/(getaddrinfo failed)|(Could not connect to)|(Cannot connect to)|(Root is invalid)|(The authenticity of)|(Connection closed prematurely)/', $e->getMessage())) {
            self::toUserException($e);
        }
        // phpcs:enable

        self::toApplicationException($e);
    }

    public static function handlePrepareToDownloadException(\Throwable $e): void
    {
        self::handleCopyFilesException($e);
    }

    public static function handleDownloadException(\Throwable $e): void
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

    private static function toUserException(\Throwable $e, ?string $customMessage = null): void
    {
        throw new UserException($customMessage ?: $e->getMessage(), $e->getCode(), $e);
    }

    private static function toApplicationException(\Throwable $e): void
    {
        throw new ApplicationException($e->getMessage(), $e->getCode(), $e);
    }
}
