<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

use Keboola\FtpExtractor\Exception\ApplicationException;
use League\Flysystem\Sftp\ConnectionErrorException;
use League\Flysystem\Sftp\InvalidRootException;
use PHPUnit\Framework\TestCase;
use Keboola\FtpExtractor\Exception\ExceptionConverter;
use League\Flysystem\FileNotFoundException;
use Keboola\Component\UserException;

class ExceptionConverterTest extends TestCase
{
    /**
     * @dataProvider exceptionMessageProvider
     */
    public function testHandleCopyFilesException(
        string $expectedException,
        string $message,
        string $throwException
    ): void {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($message);

        try {
            throw new $throwException($message);
        } catch (\Throwable $e) {
            ExceptionConverter::handleCopyFilesException($e);
        }
    }

    /**
     * @dataProvider exceptionMessageProvider
     */
    public function testHandlePrepareToDownloadException(
        string $expectedException,
        string $message,
        string $throwException
    ): void {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($message);

        try {
            throw new $throwException($message);
        } catch (\Throwable $e) {
            ExceptionConverter::handlePrepareToDownloadException($e);
        }
    }

    /**
     * @dataProvider downloadExceptionMessageProvider
     */
    public function testHandleDownloadException(
        string $expectedException,
        string $expectedMessage,
        string $throwException,
        string $throwMessage
    ): void {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);

        try {
            throw new $throwException($throwMessage);
        } catch (\Throwable $e) {
            ExceptionConverter::handleDownloadException($e);
        }
    }

    public function exceptionMessageProvider(): array
    {
        return [
            [
                UserException::class,
                'Foo bar',
                InvalidRootException::class,
            ],
            [
                UserException::class,
                'Foo bar',
                ConnectionErrorException::class,
            ],
            [
                UserException::class,
                'Foo bar',
                FileNotFoundException::class,
            ],
            [
                UserException::class,
                'Could not login with username: foo bar',
                \RuntimeException::class,
            ],
            [
                UserException::class,
                'php_network_getaddresses: getaddrinfo failed: nodename nor servname provided, or not known',
                \RuntimeException::class,
            ],
            [
                UserException::class,
                'Could not connect to server to verify public key.',
                \RuntimeException::class,
            ],
            [
                UserException::class,
                'The authenticity of host foo can\'t be established.',
                \RuntimeException::class,
            ],
            [
                UserException::class,
                'Cannot connect to foo bar',
                \RuntimeException::class,
            ],
            [
                UserException::class,
                'Root is invalid or does not exist: /foo/bar',
                \RuntimeException::class,
            ],
            [
                UserException::class,
                'Foo bar',
                ConnectionErrorException::class,
            ],
            [
                ApplicationException::class,
                'Foo bar',
                \RuntimeException::class,
            ],
        ];
    }

    public function downloadExceptionMessageProvider(): array
    {
        $filePtah = '/foo/bar.jpg';
        $progressMessage = 'Operation now in progress (115)';

        return [
            [
                UserException::class,
                sprintf('Error while trying to download file: File not found at path: %s', $filePtah),
                FileNotFoundException::class,
                $filePtah,
            ],
            [
                UserException::class,
                sprintf(
                    'Connection was terminated. Check that the connection is not blocked by Firewall: %s',
                    $progressMessage
                ),
                \ErrorException::class,
                $progressMessage,
            ],
            [
                ApplicationException::class,
                'Foo Bar',
                \ErrorException::class,
                'Foo Bar',
            ],
            [
                ApplicationException::class,
                'Foo Bar',
                \RuntimeException::class,
                'Foo Bar',
            ],
        ];
    }
}
