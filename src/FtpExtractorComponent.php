<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use Keboola\Component\BaseComponent;
use League\Flysystem\Filesystem;

class FtpExtractorComponent extends BaseComponent
{
    public function run(): void
    {
        /** @var Config $config */
        $config = $this->getConfig();
        $registry = new FileStateRegistry($this->getDataDir());
        $ftpFs = new Filesystem(AdapterFactory::getAdapter($config));
        $ftpExtractor = new FtpExtractor($config, $ftpFs);
        $count = $ftpExtractor->copyFiles(
            $config->getPathToCopy(),
            $this->getOutputDirectory(),
            $registry
        );
        $this->getLogger()->info(printf("%d file(s) downloaded", $count));
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