<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use InvalidArgumentException;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionException;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Ftp\FtpConnectionProvider;
use League\Flysystem\Ftp\NoopCommandConnectivityChecker;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\PhpseclibV3\SimpleConnectivityChecker;

class AdapterFactory
{
    public static function getAdapter(Config $config): FilesystemAdapter
    {
        return match ($config->getConnectionType()) {
            ConfigDefinition::CONNECTION_TYPE_FTP => new FtpAdapter(self::createFtpAdapter($config)),
            ConfigDefinition::CONNECTION_TYPE_SSL_EXPLICIT => new FtpAdapter(self::createFtpAdapter($config, true)),
            ConfigDefinition::CONNECTION_TYPE_SFTP => new SftpAdapter(
                self::createSftpAdapter($config),
                '/',
            ),
            default => throw new InvalidArgumentException('Specified adapter not found'),
        };
    }

    /**
     * @throws FtpConnectionException
     */
    public static function checkConnectivity(Config $config): bool
    {
        return match ($config->getConnectionType()) {
            ConfigDefinition::CONNECTION_TYPE_FTP => self::checkFtpConnectivity($config),
            ConfigDefinition::CONNECTION_TYPE_SSL_EXPLICIT => self::checkFtpConnectivity($config, true),
            ConfigDefinition::CONNECTION_TYPE_SFTP => self::checkSftpConnectivity($config),
            default => throw new InvalidArgumentException('Specified adapter not found'),
        };
    }

    /**
     * @throws FtpConnectionException
     */
    private static function checkFtpConnectivity(Config $config, bool $ssl = false): bool
    {
        $connectionProvider = new FtpConnectionProvider();
        $checker = new NoopCommandConnectivityChecker();

        return $checker->isConnected(
            $connectionProvider->createConnection(self::createFtpAdapter($config, $ssl)),
        );
    }

    private static function checkSftpConnectivity(Config $config): bool
    {
        $connectionProvider = self::createSftpAdapter($config);
        $checker = new SimpleConnectivityChecker(true);
        return $checker->isConnected($connectionProvider->provideConnection());
    }

    private static function createFtpAdapter(Config $config, bool $ssl = false): FtpConnectionOptions
    {
        if ($ssl) {
            return FtpConnectionOptions::fromArray(array_merge($config->getConnectionConfig(), ['ssl' => true]));
        }
        return FtpConnectionOptions::fromArray($config->getConnectionConfig());
    }

    private static function createSftpAdapter(Config $config): SftpConnectionProvider
    {
        if ($config->getPrivateKey() === '') {
            return SftpConnectionProvider::fromArray($config->getConnectionConfig());
        } else {
            return SftpConnectionProvider::fromArray(array_merge(
                $config->getConnectionConfig(),
                ['privateKey' => $config->getPrivateKey()],
            ));
        }
    }
}
