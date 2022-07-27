<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use Keboola\Component\BaseComponent;
use Keboola\SSHTunnel\SSH;
use League\Flysystem\Filesystem;

class FtpExtractorComponent extends BaseComponent
{
    protected function run(): void
    {
        $config = $this->getConfig();
        if ($config->isSshEnabled()) {
            $ssh = new SSH();
            $ssh->openTunnel($config->getSshConfig($config->getPort(), 21));

            // open tunnels to all FTP ports
            foreach ($this->getConfig()->getFtpPassivePorts() as $port) {
                $ssh->openTunnel($config->getSshConfig($port));
            }

            $config->setHost('localhost');
            $config->setPort(21);
        }
        $registry = new FileStateRegistry($this->getInputState());
        $ftpFs = new Filesystem(AdapterFactory::getAdapter($config));
        $ftpExtractor = new FtpExtractor(
            $config->isOnlyForNewFiles(),
            $ftpFs,
            $registry,
            $this->getLogger()
        );
        $count = $ftpExtractor->copyFiles(
            $config->getPathToCopy(),
            $this->getOutputDirectory()
        );
        $this->writeOutputStateToFile(
            array_merge(
                $this->getInputState(),
                [FileStateRegistry::STATE_FILE_KEY => $registry->getFileStates()]
            )
        );
        $this->getLogger()->info(sprintf("%d file(s) downloaded", $count));
    }

    private function getOutputDirectory(): string
    {
        return $this->getDataDir() . '/out/files/';
    }

    public function getConfig(): Config
    {
        /** @var Config $config */
        $config = parent::getConfig();
        return $config;
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
