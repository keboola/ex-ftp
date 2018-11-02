<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use Keboola\Component\BaseComponent;

class FtpExtractorComponent extends BaseComponent
{
    public function run(): void
    {
        /** @var Config $config */
        $config = $this->getConfig();
        $ftpExtractor = new FtpExtractor($config);
        $ftpExtractor->copyFiles(
            $config->getPathToCopy(),
            $this->getOutputDirectory()
        );
    }

    private function getOutputDirectory(): string
    {
        return $this->getDataDir() . '/out/files/';
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }
}
