<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Exception;

use League\Flysystem\UnableToReadFile;
use Throwable;
use Keboola\Component\UserException;
use League\Flysystem\FilesystemException;

final class ExceptionConverter
{
    public static function handleCopyFilesException(Throwable $e): Throwable
    {
        return self::handleCommonException($e);
    }

    public static function handlePrepareToDownloadException(Throwable $e): Throwable
    {
        return self::handleCommonException($e);
    }

    public static function handleDownloadException(Throwable $e): Throwable
    {
        if ($e instanceof FilesystemException) {
            return self::toUserException($e, sprintf(
                'Error while trying to download file: %s',
                $e->getMessage()
            ));
        }

        return self::handleCommonException($e);
    }

    public static function handleCommonException(Throwable $e): Throwable
    {
        if ($e instanceof UserException) {
            return $e;
        }

        if ($e instanceof UnableToReadFile) {
            return self::toUserException(
                $e,
                $e->getMessage() . 'Operation: ' .  $e->operation(). 'Reason: ' . $e->reason()
            );
        }

        if ($e instanceof FilesystemException) {
            return self::toUserException($e);
        }

        // Make the message clear for user (ftp_rawlist(): php_connect_nonb() failed: Operation now in progress)
        if ($e instanceof \ErrorException
            && preg_match_all('/Operation now in progress \(115\)/', $e->getMessage())) {
            return self::toUserException($e, sprintf(
                'Connection was terminated. Check that the connection is not blocked by Firewall ' .
                'or set ignore passive address: %s',
                $e->getMessage()
            ));
        }

        // Catch user_error from phpseclib
        // phpcs:disable
        if (preg_match_all('/(getaddrinfo failed)|(Cannot connect to)|(The authenticity of)|(Connection closed prematurely)/', $e->getMessage())) {
            return self::toUserException($e);
        }
        // phpcs:enable

        return self::toApplicationException($e);
    }

    private static function toUserException(Throwable $e, ?string $customMessage = null): Throwable
    {
        return new UserException(rtrim($customMessage ?: $e->getMessage(), '.'), $e->getCode(), $e);
    }

    private static function toApplicationException(Throwable $e): Throwable
    {
        return new ApplicationException($e->getMessage(), $e->getCode(), $e);
    }
}
