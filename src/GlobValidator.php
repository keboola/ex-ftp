<?php

namespace Keboola\FtpExtractor;

use Webmozart\Glob\Glob;

/**
 * Wrapper class around Webmozart\Glob. Matching does not require absolute path.
 */
class GlobValidator
{
    public static function validatePathAgainstGlob(string $path, string $glob)
    {
        $path = static::convertToAbsolute($path);
        $glob = static::convertToAbsolute($glob);
        return Glob::match($path, $glob);
    }

    private static function convertToAbsolute(string $path)
    {
        if(substr($path, 0, 1) !== '/') {
            return '/' . $path;
        }

        return $path;
    }
}