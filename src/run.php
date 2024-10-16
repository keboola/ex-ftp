<?php

declare(strict_types=1);

use Keboola\Component\Logger;
use Keboola\Component\UserException;
use Keboola\FtpExtractor\FtpExtractorComponent;

require __DIR__ . '/../vendor/autoload.php';

$logger = new Logger();
try {
    $app = new FtpExtractorComponent($logger);
    $app->execute();
    exit(0);
} catch (UserException $e) {
    $logger->error($e->getMessage());
    exit(1);
} catch (Throwable $e) {
    $previous = $e->getPrevious();
    $logger->critical(
        get_class($e) . ':' . $e->getMessage(),
        [
            'errFile' => $e->getFile(),
            'errLine' => $e->getLine(),
            'errCode' => $e->getCode(),
            'errTrace' => $e->getTraceAsString(),
            'errPrevious' => $previous ? get_class($previous) : '',
        ],
    );
    exit(2);
}
