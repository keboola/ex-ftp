<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use Keboola\Component\UserException;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Sftp\SftpAdapter;
use Psr\Log\LoggerInterface;
use Retry\BackOff\ExponentialBackOffPolicy;
use Retry\Policy\SimpleRetryPolicy;
use Retry\RetryProxy;

class AdapterFactory
{
    public static function getAdapter(Config $config, LoggerInterface $logger): AbstractAdapter
    {
        switch ($config->getConnectionType()) {
            case ConfigDefinition::CONNECTION_TYPE_FTP:
                return static::createFtpAdapter($config);
            case ConfigDefinition::CONNECTION_TYPE_SSL_EXPLICIT:
                return static::createSslFtpImplicitAdapter($config);
            case ConfigDefinition::CONNECTION_TYPE_SFTP:
                return static::createSftpAdapter($config, $logger);
            default:
                throw new \InvalidArgumentException("Specified adapter not found");
        }
    }

    private static function createFtpAdapter(Config $config): AbstractAdapter
    {
        return new Ftp(
            $config->getConnectionConfig()
        );
    }

    private static function createSslFtpImplicitAdapter(Config $config): AbstractAdapter
    {
        return new Ftp(
            array_merge($config->getConnectionConfig(), ['ssl' => true])
        );
    }

    private static function createSftpAdapter(Config $config, LoggerInterface $logger): AbstractAdapter
    {
        if ($config->getPrivateKey() === '') {
            $adapter = new SftpAdapter($config->getConnectionConfig());
        } else {
            $adapter = new  SftpAdapter(
                array_merge($config->getConnectionConfig(), ['privateKey' => $config->getPrivateKey()])
            );
        }
        static::setSftpRoot($adapter, $config->getPathToCopy(), $logger);
        return $adapter;
    }

    private static function setSftpRoot(SftpAdapter $adapter, string $sourcePath, LoggerInterface $logger): void
    {
        if (substr($sourcePath, 0, 1) === '/') {
            $adapter->setRoot('/');
            return;
        }

        $retryProxy = new RetryProxy(
            new SimpleRetryPolicy(FtpExtractor::CONNECTION_RETRIES),
            new ExponentialBackOffPolicy(FtpExtractor::RETRY_BACKOFF),
            $logger
        );

        try {
            $pwd = $retryProxy->call(function () use ($adapter): string {
                return $adapter->getConnection()->pwd();
            });
            $adapter->setRoot($pwd);
        } catch (\RuntimeException $e) {
            throw new UserException($e->getMessage(), $e->getCode(), $e);
        } catch (\LogicException $e) {
            throw new UserException($e->getMessage(), $e->getCode(), $e);
        } catch (\ErrorException $e) {
            throw new UserException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
