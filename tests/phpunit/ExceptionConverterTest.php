<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

use Keboola\FtpExtractor\Exception\ApplicationException;
use PHPUnit\Framework\TestCase;
use Keboola\FtpExtractor\Exception\ExceptionConverter;
use League\Flysystem\FileNotFoundException;
use Keboola\Component\UserException;

class ExceptionConverterTest extends TestCase
{
    /**
     * @dataProvider exceptionMessageProvider
     */
    public function testHandleCopyFilesExpectedUserException(string $message): void
    {
        $this->expectException(UserException::class);
        $this->expectExceptionMessage($message);

        try {
            throw new \RuntimeException($message);
        } catch (\Throwable $e) {
            ExceptionConverter::handleCopyFilesException($e);
        }
    }

    public function testHandleCopyFilesExpectedApplicationException(): void
    {
        $this->expectException(ApplicationException::class);

        try {
            throw new \RuntimeException('Foo bar');
        } catch (\Throwable $e) {
            ExceptionConverter::handleCopyFilesException($e);
        }
    }

    /**
     * @dataProvider exceptionMessageProvider
     */
    public function testHandlePrepareToDownloadExpectedUserException(string $message): void
    {
        $this->expectException(UserException::class);
        $this->expectExceptionMessage($message);

        try {
            throw new \RuntimeException($message);
        } catch (\Throwable $e) {
            ExceptionConverter::handlePrepareToDownloadException($e);
        }
    }

    public function testHandlePrepareToDownloadExpectedApplicationException(): void
    {
        $this->expectException(ApplicationException::class);

        try {
            throw new \Exception('Foo bar');
        } catch (\Throwable $e) {
            ExceptionConverter::handlePrepareToDownloadException($e);
        }
    }

    public function testHandleDownloadForFileNotFound(): void
    {
        $pathFile = '/foo/bar.jpg';
        $this->expectException(UserException::class);
        $this->expectExceptionMessage(
            sprintf('Error while trying to download file: File not found at path: %s', $pathFile)
        );

        try {
            throw new FileNotFoundException($pathFile);
        } catch (\Throwable $e) {
            ExceptionConverter::handleDownload($e);
        }
    }

    public function testHandleDownloadForErrorOperationInProgress(): void
    {
        $message = 'ftp_fget(): php_connect_nonb() failed: Operation now in progress (115)';
        $this->expectException(UserException::class);
        $this->expectExceptionMessage(
            sprintf('Connection was terminated. Check that the connection is not blocked by Firewall: %s', $message)
        );

        try {
            throw new \ErrorException($message);
        } catch (\Throwable $e) {
            ExceptionConverter::handleDownload($e);
        }
    }

    public function testHandleDownloadExpectedApplicationException(): void
    {
        $this->expectException(ApplicationException::class);

        try {
            throw new \RuntimeException('Foo bar');
        } catch (\Throwable $e) {
            ExceptionConverter::handleDownload($e);
        }
    }

    public function testConvertToUserException(): void
    {
        $this->expectException(UserException::class);
        $this->expectExceptionMessage('Foo bar');

        try {
            throw new \RuntimeException('Foo bar');
        } catch (\Throwable $e) {
            ExceptionConverter::toUserException($e);
        }
    }

    public function exceptionMessageProvider(): array
    {
        return [
            ['Could not login with username: foo bar'],
            ['php_network_getaddresses: getaddrinfo failed: nodename nor servname provided, or not known'],
            ['Could not connect to server to verify public key.'],
            ['The authenticity of host foo can\'t be established.'],
            ['Cannot connect to foo bar'],
            ['Root is invalid or does not exist: /foo/bar'],
        ];
    }
}
