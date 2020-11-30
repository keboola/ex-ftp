<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
    public function getConnectionConfig(): array
    {
        return [
            'host' => $this->getValue(['parameters', 'host']),
            'username' => $this->getValue(['parameters', 'username']),
            'password' => $this->getValue(['parameters', '#password']),
            'port' => $this->getValue(['parameters', 'port']),
            'timeout' => $this->getValue(['parameters', 'timeout']),
            'recurseManually' => $this->shouldUseManualRecursion(),
            'ignorePassiveAddress' => $this->ignorePassiveAddress(),
        ];
    }

    public function getConnectionType(): string
    {
        return $this->getValue(['parameters', 'connectionType']);
    }

    public function getPathToCopy(): string
    {
        return $this->getValue(['parameters', 'path']);
    }

    public function isOnlyForNewFiles(): bool
    {
        return $this->getValue(['parameters', 'onlyNewFiles']);
    }

    public function getPrivateKey(): string
    {
        return $this->getValue(['parameters', '#privateKey']);
    }

    private function shouldUseManualRecursion(): bool
    {
        return $this->getValue(['parameters', 'listing']) === ConfigDefinition::LISTING_MANUAL;
    }

    public function ignorePassiveAddress(): bool
    {
        return $this->getValue(['parameters', 'ignorePassiveAddress']);
    }
}
