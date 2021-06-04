<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use Psr\Log\LoggerInterface;
use Retry\RetryProxy;
use Retry\Policy\SimpleRetryPolicy;
use Retry\BackOff\ExponentialBackOffPolicy;

class RetryProxyFactory
{
    private const CONNECTION_RETRIES = 3;
    private const RETRY_BACKOFF = 300;

    public static function createRetryProxy(LoggerInterface $logger): RetryProxy
    {
        return new RetryProxy(
            new SimpleRetryPolicy(self::CONNECTION_RETRIES),
            new ExponentialBackOffPolicy(self::RETRY_BACKOFF),
            $logger
        );
    }
}
