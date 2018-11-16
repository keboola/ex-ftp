<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

class ItemFilter
{
    public const FTP_FILETYPE_FILE = 'file';

    public static function getOnlyFiles(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            if ((isset($item['type'])) && $item['type'] === static::FTP_FILETYPE_FILE) {
                $result[] = $item;
            }
        }
        return $result;
    }
}
