<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
    public const int SSH_PORT = 22;

    private string $host;

    private int $port;

    public function getConnectionConfig(): array
    {
        return [
            'host' => $this->getHost(),
            'port' => $this->getPort(),
            'username' => $this->getValue(['parameters', 'username']),
            'password' => $this->getValue(['parameters', '#password']),
            'timeout' => $this->getValue(['parameters', 'timeout']),
            'recurseManually' => $this->shouldUseManualRecursion(),
            'ignorePassiveAddress' => $this->ignorePassiveAddress(),
            'useRawListOptions' => true,
        ];
    }

    public function getConnectionType(): string
    {
        return $this->getStringValue(['parameters', 'connectionType']);
    }

    public function getPathToCopy(): string
    {
        return $this->getStringValue(['parameters', 'path']);
    }

    public function isOnlyForNewFiles(): bool
    {
        return $this->getBoolValue(['parameters', 'onlyNewFiles']);
    }

    public function skipFileNotFound(): bool
    {
        return $this->getBoolValue(['parameters', 'skipFileNotFound']);
    }

    public function getPrivateKey(): string
    {
        return $this->getStringValue(['parameters', '#privateKey']);
    }

    private function shouldUseManualRecursion(): bool
    {
        return $this->getStringValue(['parameters', 'listing']) === ConfigDefinition::LISTING_MANUAL;
    }

    public function ignorePassiveAddress(): bool
    {
        return $this->getBoolValue(['parameters', 'ignorePassiveAddress']);
    }

    public function getHost(): string
    {
        return $this->host ?? $this->getStringValue(['parameters', 'host']);
    }

    public function getPort(): int
    {
        return $this->port ?? $this->getIntValue(['parameters', 'port']);
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    public function setPort(int $port): void
    {
        $this->port = $port;
    }

    public function isSshEnabled(): bool
    {
        return $this->getBoolValue(['parameters', 'ssh', 'enabled'], false);
    }

    public function getSshConfig(int $port, ?int $localPort = null): array
    {
        $sshConfig = $this->getArrayValue(['parameters', 'ssh']);
        $sshConfig['remoteHost'] = $this->getHost();
        $sshConfig['remotePort'] = $port;
        $sshConfig['localPort'] = $localPort ?? $port;
        $sshConfig['sshPort'] = self::SSH_PORT;
        $sshConfig['privateKey'] = $sshConfig['keys']['#private'];
        return $sshConfig;
    }

    public function getFtpPassivePorts(): array
    {
        $portRange = $this->getStringValue(['parameters', 'ssh', 'passivePortRange']);

        list($rangeFrom, $rangeTo) = explode(':', $portRange);

        return range($rangeFrom, $rangeTo);
    }

    private function getBoolValue(array $keys, mixed $default = null): bool
    {
        /** @var bool $value */
        $value = $this->getValue($keys, $default);
        return $value;
    }
}
