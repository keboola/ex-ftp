<?php

namespace Keboola\FtpExtractor\Tests;

use Keboola\FtpExtractor\GlobValidator;
use PHPUnit\Framework\TestCase;

class GlobValidatorTest extends TestCase
{
    /**
     * @group Glob
     * @dataProvider positiveDataProvider
     */
    public function testPositiveGlobMatchingPatterns(string $path, string $glob)
    {
        $this->assertTrue(GlobValidator::validatePathAgainstGlob($path, $glob));
    }

    public function positiveDataProvider()
    {
        return [
            ['/files/data/test.txt', '/*/*/*.txt'],
            ['files/data/test.txt', '*/*/*.txt'],
            ['files/data/test.txt', '/*/data/test.*'],
        ];
    }

    /**
     * @group Glob
     * @dataProvider negativeDataProvider
     */
    public function testNegativeGlobMatchingPatterns(string $path, string $glob)
    {
        $this->assertFalse(GlobValidator::validatePathAgainstGlob($path, $glob));
    }

    public function negativeDataProvider()
    {
        return [
            ['files/data/func1.txt', 'file/*/*.txt'],
            ['files/data/func1.ptx', 'files/*/*.txt'],
            ['/files/data/func1.bin', '*/*/*/*/*.bin']
        ];
    }
}