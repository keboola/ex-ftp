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
     * @dataProvider baseUserExceptionProvider
     */
    public function testHandleCopyFilesExpectedUserException(string $exception): void
    {
        $this->expectException(UserException::class);

        try {
            throw new $exception('foo');
        } catch (\Throwable $e) {
            ExceptionConverter::handleCopyFilesException($e);
        }
    }

    public function testHandleCopyFilesExpectedApplicationException(): void
    {
        $this->expectException(ApplicationException::class);

        try {
            throw new \Exception('foo');
        } catch (\Throwable $e) {
            ExceptionConverter::handleCopyFilesException($e);
        }
    }

    /**
     * @dataProvider baseUserExceptionProvider
     */
    public function testHandlePrepareToDownloaExpectedUserException(string $exception): void
    {
        $this->expectException(UserException::class);

        try {
            throw new $exception('foo');
        } catch (\Throwable $e) {
            ExceptionConverter::handlePrepareToDownloaException($e);
        }
    }

    public function testHandlePrepareToDownloaExpectedApplicationException(): void
    {
        $this->expectException(ApplicationException::class);

        try {
            throw new \Exception('foo');
        } catch (\Throwable $e) {
            ExceptionConverter::handlePrepareToDownloaException($e);
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

    /**
     * @dataProvider aplicationExceptionForDownloadProvider
     */
    public function testHandleDownloadExpectedApplicationException(string $exception): void
    {
        $this->expectException(ApplicationException::class);

        try {
            throw new $exception('foo');
        } catch (\Throwable $e) {
            ExceptionConverter::handleDownload($e);
        }
    }

    public function baseUserExceptionProvider(): array
    {
        return [
            [\RuntimeException::class],
            [\LogicException::class],
            [\ErrorException::class],
            [FileNotFoundException::class],
        ];
    }

    public function aplicationExceptionForDownloadProvider(): array
    {
        return [
            [\RuntimeException::class],
            [\LogicException::class],
            [\ErrorException::class],
            [\Throwable::class],
        ];
    }
}
