<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

use Keboola\FtpExtractor\Exception\ApplicationException;
use League\Flysystem\ConnectionRuntimeException;
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
     * @psalm-param class-string<\Throwable> $expectedException
     */
    public function testHandleCopyFilesException(
        string $expectedException,
        string $expectedExceptionMessage,
        \Throwable $throwException
    ): void {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedExceptionMessage);

        try {
            throw $throwException;
        } catch (\Throwable $e) {
            ExceptionConverter::handleCopyFilesException($e);
        }
    }

    /**
     * @dataProvider exceptionMessageProvider
     * @psalm-param class-string<\Throwable> $expectedException
     */
    public function testHandlePrepareToDownloadException(
        string $expectedException,
        string $expectedExceptionMessage,
        \Throwable $throwException
    ): void {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedExceptionMessage);

        try {
            throw $throwException;
        } catch (\Throwable $e) {
            ExceptionConverter::handlePrepareToDownloadException($e);
        }
    }

    /**
     * @dataProvider downloadExceptionMessageProvider
     * @psalm-param class-string<\Throwable> $expectedException
     */
    public function testHandleDownloadException(
        string $expectedException,
        string $expectedExceptionMessage,
        \Throwable $throwException
    ): void {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedExceptionMessage);

        try {
            throw $throwException;
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
                new InvalidRootException('Foo bar'),
            ],
            [
                UserException::class,
                'Foo bar',
                new ConnectionErrorException('Foo bar'),
            ],
            [
                UserException::class,
                'Foo bar',
                new FileNotFoundException('Foo bar'),
            ],
            [
                UserException::class,
                'Could not login with username: foo bar',
                new ConnectionErrorException('Could not login with username: foo bar'),
            ],
            [
                UserException::class,
                'php_network_getaddresses: getaddrinfo failed: nodename nor servname provided, or not known',
                new \RuntimeException(
                    'php_network_getaddresses: getaddrinfo failed: nodename nor servname provided, or not known'
                ),
            ],
            [
                UserException::class,
                'Could not connect to server to verify public key.',
                new ConnectionRuntimeException('Could not connect to server to verify public key.'),
            ],
            [
                UserException::class,
                'The authenticity of host foo can\'t be established.',
                new \RuntimeException('The authenticity of host foo can\'t be established.'),
            ],
            [
                UserException::class,
                'Cannot connect to foo bar',
                new \RuntimeException('Cannot connect to foo bar'),
            ],
            [
                UserException::class,
                'Root is invalid or does not exist: /foo/bar',
                new InvalidRootException('Root is invalid or does not exist: /foo/bar'),
            ],
            [
                UserException::class,
                'Foo bar',
                new ConnectionErrorException('Foo bar'),
            ],
            [
                ApplicationException::class,
                'Foo bar',
                new \RuntimeException('Foo bar'),
            ],
            [
                UserException::class,
                sprintf(
                    'Connection was terminated. Check that the connection is not blocked by Firewall ' .
                    'or set ignore passive address: Operation now in progress (115)'
                ),
                new \ErrorException('Operation now in progress (115)'),
            ],
        ];
    }

    public function downloadExceptionMessageProvider(): array
    {
        $filePath = '/foo/bar.jpg';
        $progressMessage = 'Operation now in progress (115)';

        return [
            [
                UserException::class,
                sprintf('Error while trying to download file: File not found at path: %s', $filePath),
                new FileNotFoundException($filePath),
            ],
            [
                UserException::class,
                sprintf(
                    'Connection was terminated. Check that the connection is not blocked by Firewall ' .
                    'or set ignore passive address: %s',
                    $progressMessage
                ),
                new \ErrorException($progressMessage),
            ],
            [
                ApplicationException::class,
                'Foo Bar',
                new \ErrorException('Foo Bar'),
            ],
            [
                ApplicationException::class,
                'Foo Bar',
                new \RuntimeException('Foo Bar'),
            ],
            [
                UserException::class,
                'Connection timed out. Check your timeout configuration, server health and try again.',
                new \ErrorException('ftp_rawlist(): Connection timed out'),
            ],
            [
                UserException::class,
                'Connection timed out. Check your timeout configuration, server health and try again.',
                new \ErrorException('ftp_mdtm(): Connection timed out'),
            ],
            [
                UserException::class,
                'SSL/TLS handshake failed. Check your credentials, SSL/TLS configuration and make sure the ' .
                'certificate is valid and is not expired.',
                new \ErrorException('ftp_rawlist(): data_accept: SSL/TLS handshake failed'),
            ],
            [
                UserException::class,
                'Expected SSH_FXP_ATTRS or SSH_FXP_STATUS',
                new \ErrorException('Expected SSH_FXP_ATTRS or SSH_FXP_STATUS'),
            ],
        ];
    }
}
