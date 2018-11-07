<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Sftp\SftpAdapter;

class AdapterFactory
{
    public static function getAdapter(Config $config): AbstractAdapter
    {
        switch ($config->getConnectionType()) {
            case ConfigDefinition::CONNECTION_TYPE_FTP:
                return static::createFtpAdapter($config);
                break;
            case ConfigDefinition::CONNECTION_TYPE_SSL_IMPLICIT:
                return static::createSllFtpImplicitAdapter($config);
                break;
            case ConfigDefinition::CONNECTION_TYPE_SFTP:
                return static::createSftpAdapter($config);
                break;
            default:
                throw new \InvalidArgumentException("Specified adapter not found");
                break;
        }
    }

    private static function createFtpAdapter(Config $config): AbstractAdapter
    {
        return new Ftp(
            $config->getConnectionConfig()
        );
    }

    private static function createSllFtpImplicitAdapter(Config $config): AbstractAdapter
    {
        return new Ftp(
            array_merge($config->getConnectionConfig(), ['ssl' => true])
        );
    }

    private static function createSftpAdapter(Config $config): AbstractAdapter
    {
        if ($config->getPrivateKey() === '') {
            return new SftpAdapter($config->getConnectionConfig());
        } else {
            return new SftpAdapter(
                array_merge($config->getConnectionConfig(), ['privateKey' => $config->getPrivateKey()])
            );
        }
    }
}
