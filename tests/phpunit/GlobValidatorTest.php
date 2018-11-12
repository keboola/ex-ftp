<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

use Keboola\FtpExtractor\GlobValidator;
use PHPUnit\Framework\TestCase;

class GlobValidatorTest extends TestCase
{
    /**
     * @group Glob
     * @dataProvider positiveDataProvider
     */
    public function testPositiveGlobMatchingPatterns(string $path, string $glob): void
    {
        $this->assertTrue(GlobValidator::validatePathAgainstGlob($path, $glob));
    }

    public function positiveDataProvider(): array
    {
        return [
            ['/files/data/test.txt', '/*/*/*.txt'],
            ['files/data/test.txt', '*/*/*.txt'],
            ['files/data/test.txt', '/*/data/test.*'],
            ['files/data/test.txt', '/**/*'],
            ['files/data/test.txt', 'files/data/test.txt'],
            ['files/data/test.txt', '/files/data/test.txt'],
        ];
    }

    /**
     * @group Glob
     * @dataProvider negativeDataProvider
     */
    public function testNegativeGlobMatchingPatterns(string $path, string $glob): void
    {
        $this->assertFalse(GlobValidator::validatePathAgainstGlob($path, $glob));
    }

    public function negativeDataProvider(): array
    {
        return [
            ['files/data/func1.txt', 'file/*/*.txt'],
            ['files/data/func1.ptx', 'files/*/*.txt'],
            ['/files/data/func1.bin', '*/*/*/*/*.bin'],
        ];
    }
}
