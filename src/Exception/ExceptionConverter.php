<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Exception;

use ErrorException;
use InvalidArgumentException;
use Keboola\Component\UserException;
use League\Flysystem\FilesystemException;
use League\Flysystem\Ftp\UnableToAuthenticate;
use League\Flysystem\UnableToReadFile;
use phpseclib3\Exception\ConnectionClosedException;
use Throwable;

final class ExceptionConverter
{
    public static function handleCopyFilesException(Throwable $e): void
    {
        self::handleCommonException($e);
    }

    public static function handlePrepareToDownloadException(Throwable $e): void
    {
        self::handleCommonException($e);
    }

    public static function handleDownloadException(Throwable $e): void
    {
        if ($e instanceof UnableToReadFile) {
            self::toUserException($e, sprintf(
                'Error while trying to download file: %s',
                $e->getMessage(),
            ));
        }

        self::handleCommonException($e);
    }

    private static function handleCommonException(Throwable $e): void
    {
        if ($e instanceof UserException) {
            throw $e;
        }

        if ($e instanceof FilesystemException) {
            self::toUserException($e);
        }

        if ($e instanceof UnableToAuthenticate) {
            self::toUserException($e);
        }

        if ($e instanceof ConnectionClosedException) {
            self::toUserException($e);
        }

        if ($e instanceof InvalidArgumentException) {
            self::toUserException($e);
        }

        // Make the message clear for user (ftp_rawlist(): php_connect_nonb() failed: Operation now in progress)
        if ($e instanceof ErrorException
            && preg_match_all('/Operation now in progress \(115\)/', $e->getMessage())) {
            self::toUserException($e, sprintf(
                'Connection was terminated. Check that the connection is not blocked by Firewall ' .
                'or set ignore passive address: %s',
                $e->getMessage(),
            ));
        }

        // Make the message clear for user (ftp_rawlist()/ftp_mdtm(): Connection timed out)
        if ($e instanceof ErrorException
            && preg_match_all('/Connection timed out/', $e->getMessage())) {
            self::toUserException(
                $e,
                'Connection timed out. Check your timeout configuration, server health and try again.',
            );
        }

        // Make the message clear for user (ftp_rawlist\(\): data_accept: SSL/TLS handshake failed)
        if ($e instanceof ErrorException
            && preg_match_all('/ftp_rawlist\(\): data_accept: SSL\/TLS handshake failed/', $e->getMessage())) {
            self::toUserException(
                $e,
                'SSL/TLS handshake failed. Check your credentials, SSL/TLS configuration and make sure the ' .
                'certificate is valid and is not expired.',
            );
        }

        if ($e instanceof ErrorException
            && preg_match_all('/Expected SSH_/', $e->getMessage())) {
            self::toUserException($e);
        }

        // Catch user_error from phpseclib
        // phpcs:disable
        if (preg_match_all('/(getaddrinfo failed)|(Cannot connect to)|(The authenticity of)|(Connection closed prematurely)/', $e->getMessage())) {
            self::toUserException($e);
        }
        // phpcs:enable

        self::toApplicationException($e);
    }

    private static function toUserException(Throwable $e, ?string $customMessage = null): void
    {
        throw new UserException($customMessage ?: $e->getMessage(), $e->getCode(), $e);
    }

    private static function toApplicationException(Throwable $e): void
    {
        throw new ApplicationException($e->getMessage(), $e->getCode(), $e);
    }
}
