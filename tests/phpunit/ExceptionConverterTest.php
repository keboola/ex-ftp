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
     * @dataProvider userExceptionProvider
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
     * @dataProvider userExceptionProvider
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

    /**
     * @dataProvider userExceptionProvider
     */
    public function testToUserException(string $exception): void
    {
        $this->expectException(UserException::class);
        $this->expectExceptionMessage('foo');

        try {
            throw new $exception('foo');
        } catch (\Throwable $e) {
            ExceptionConverter::toUserException($e);
        }
    }

    public function testToUserExceptionWithCustomMessage(): void
    {
        $this->expectException(UserException::class);
        $this->expectExceptionMessage('bar');

        try {
            throw new \Exception('foo');
        } catch (\Throwable $e) {
            ExceptionConverter::toUserException($e, 'bar');
        }
    }

    public function userExceptionProvider(): array
    {
        return [
            [\RuntimeException::class],
            [\LogicException::class],
            [\ErrorException::class],
            [FileNotFoundException::class],
        ];
    }
}
