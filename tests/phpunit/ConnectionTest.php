<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

use Keboola\Component\UserException;
use Keboola\FtpExtractor\FileStateRegistry;
use Keboola\FtpExtractor\FtpExtractor;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ConnectionTest extends TestCase
{
    public function testFalseConnection(): void
    {

        $fs = new Filesystem(new Ftp([
            'host' => 'localhost',
            'username' => 'bob',
            'password' => 'marley',
            'port' => 21,
        ]));

        $extractor = new FtpExtractor(false, $fs, new NullLogger());
        $this->expectException(UserException::class);
        $extractor->copyFiles('jennet', 'joplin', new FileStateRegistry([]));
    }
}
