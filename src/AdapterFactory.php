<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use InvalidArgumentException;
use Keboola\FtpExtractor\Exception\ExceptionConverter;
use League\Flysystem\Ftp\FtpConnectionProvider;
use League\Flysystem\Ftp\RawListFtpConnectivityChecker;
use Throwable;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\PhpseclibV2\SftpAdapter;
use League\Flysystem\PhpseclibV2\SftpConnectionProvider;

class AdapterFactory
{
    public static function getAdapter(Config $config): FilesystemAdapter
    {
        $options = $config->getConnectionConfig();
        switch ($config->getConnectionType()) {
            case ConfigDefinition::CONNECTION_TYPE_FTP:
                return static::createFtpAdapter($options);
            case ConfigDefinition::CONNECTION_TYPE_SSL_EXPLICIT:
                $options['ssl'] = true;
                return static::createFtpAdapter($options);
            case ConfigDefinition::CONNECTION_TYPE_SFTP:
                if ($config->getPrivateKey() !== '') {
                    $options['privateKey'] = $config->getPrivateKey();
                }
                return static::createSftpAdapter($options, $config->getPathToCopy());
            default:
                throw new InvalidArgumentException("Specified adapter not found");
        }
    }

    public static function createFtpAdapter(array $options): FilesystemAdapter
    {
        $connectionProvider = new FtpConnectionProvider();
        $options['root'] = self::getFtpRoot(
            $connectionProvider,
            FtpConnectionOptions::fromArray($options)
        );
        return new FtpAdapter(
            FtpConnectionOptions::fromArray($options),
            $connectionProvider,
            new RawListFtpConnectivityChecker(),
        );
    }

    public static function createSftpAdapter(array $options, string $pathToCopy): FilesystemAdapter
    {
        $connectionProvider = SftpConnectionProvider::fromArray($options);
        $root = self::getSftpRoot($connectionProvider, $pathToCopy);
        return new SftpAdapter(
            $connectionProvider,
            $root,
        );
    }

    private static function getFtpRoot(
        FtpConnectionProvider $connectionProvider,
        FtpConnectionOptions $options
    ): string {
        try {
            $connection = $connectionProvider->createConnection($options);
            $pwd = (string) ftp_pwd($connection);
            return $pwd ?: '/';
        } catch (Throwable $e) {
            throw ExceptionConverter::handleCommonException($e);
        }
    }

    private static function getSftpRoot(SftpConnectionProvider $connectionProvider, string $sourcePath): string
    {
        if (substr($sourcePath, 0, 1) === '/') {
            return '/';
        }

        try {
            $connection = $connectionProvider->provideConnection();
            return $connection->pwd();
        } catch (Throwable $e) {
            throw ExceptionConverter::handleCommonException($e);
        }
    }
}
