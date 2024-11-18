<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use Webmozart\Glob\Glob;

/**
 * Wrapper class around Webmozart\Glob. Matching does not require absolute path.
 */
class GlobValidator
{
    public static function validatePathAgainstGlob(string $path, string $glob): bool
    {
        $path = static::convertToAbsolute($path);
        $glob = static::convertToAbsolute($glob);
        return Glob::match($path, $glob);
    }

    public static function convertToAbsolute(string $path): string
    {
        if (substr($path, 0, 1) !== '/') {
            return '/' . $path;
        }

        return $path;
    }
}
