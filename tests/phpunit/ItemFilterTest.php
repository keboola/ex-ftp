<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor\Tests;

use Keboola\FtpExtractor\ItemFilter;
use PHPUnit\Framework\TestCase;

class ItemFilterTest extends TestCase
{
    public function testFileFilter(): void
    {
        $items = [
            ['type' => 'directory'],
            ['type' => ItemFilter::FTP_FILETYPE_FILE],
            ['type' => ItemFilter::FTP_FILETYPE_FILE],
        ];

        $expected = [
            ['type' => ItemFilter::FTP_FILETYPE_FILE],
            ['type' => ItemFilter::FTP_FILETYPE_FILE],
        ];

        $this->assertSame($expected, ItemFilter::getOnlyFiles($items));
    }
}
