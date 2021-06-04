<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

use Keboola\FtpExtractor\Exception\ApplicationException;
use League\Flysystem\Ftp\UnableToConnectToFtpHost;
use League\Flysystem\Ftp\UnableToSetFtpOption;
use League\Flysystem\PhpseclibV2\UnableToConnectToSftpHost;
use League\Flysystem\UnableToReadFile;
use PHPUnit\Framework\TestCase;
use Keboola\FtpExtractor\Exception\ExceptionConverter;
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
            throw ExceptionConverter::handleCopyFilesException($e);
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
            throw ExceptionConverter::handlePrepareToDownloadException($e);
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
            throw ExceptionConverter::handleDownloadException($e, '/foo/bar.jpg');
        }
    }

    public function exceptionMessageProvider(): array
    {
        return [
            [
                UserException::class,
                'Foo bar',
                new UnableToSetFtpOption('Foo bar'),
            ],
            [
                UserException::class,
                'Foo bar',
                new UnableToConnectToSftpHost('Foo bar'),
            ],
            [
                UserException::class,
                'Foo bar',
                new UnableToReadFile('Foo bar'),
            ],
            [
                UserException::class,
                'Could not login with username: foo bar',
                new UnableToConnectToFtpHost('Could not login with username: foo bar'),
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
                'The authenticity of host foo can\'t be established',
                new \RuntimeException('The authenticity of host foo can\'t be established.'),
            ],
            [
                UserException::class,
                'Cannot connect to foo bar',
                new \RuntimeException('Cannot connect to foo bar'),
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
                sprintf('Error while trying to download file "%s": Some error.', $filePath),
                new UnableToReadFile('Some error.'),
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
        ];
    }
}
