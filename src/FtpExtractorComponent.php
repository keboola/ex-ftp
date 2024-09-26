<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use Keboola\Component\BaseComponent;
use Keboola\Component\UserException;
use Keboola\FtpExtractor\Exception\ExceptionConverter;
use Keboola\SSHTunnel\SSH;
use Keboola\SSHTunnel\SSHException;
use League\Flysystem\Filesystem;
use Throwable;

class FtpExtractorComponent extends BaseComponent
{
    /**
     * @throws UserException
     */
    protected function run(): void
    {
        $config = $this->getConfig();
        if ($config->isSshEnabled()) {
            $config = $this->openSshTunnel($config);
        }

        try {
            $this->getLogger()->info(sprintf(
                'Connecting to host "%s" on port "%s".',
                $config->getHost(),
                $config->getPort(),
            ));
            AdapterFactory::checkConnectivity($config);

            $this->getLogger()->info('Connection successful');
        } catch (Throwable $e) {
            ExceptionConverter::handleCopyFilesException($e);
        }

        $registry = new FileStateRegistry($this->getInputState());
        $ftpFs = new Filesystem(AdapterFactory::getAdapter($config));
        $ftpExtractor = new FtpExtractor(
            $config->isOnlyForNewFiles(),
            $ftpFs,
            $registry,
            $this->getLogger(),
            $this->getConfig()->skipFileNotFound(),
        );
        $count = $ftpExtractor->copyFiles(
            $config->getPathToCopy(),
            $this->getOutputDirectory(),
        );
        $this->writeOutputStateToFile(
            array_merge(
                $this->getInputState(),
                [FileStateRegistry::STATE_FILE_KEY => $registry->getFileStates()],
            ),
        );
        $this->getLogger()->info(sprintf('%d file(s) downloaded', $count));
    }

    /**
     * @throws UserException
     */
    public function testConnectionAction(): array
    {
        $config = $this->getConfig();
        if ($config->isSshEnabled()) {
            $config = $this->openSshTunnel($config);
        }

        try {
            AdapterFactory::checkConnectivity($config);
        } catch (Throwable $e) {
            throw new UserException(sprintf("Connection failed: '%s'", $e->getMessage()), 0, $e);
        }

        return [
            'status' => 'success',
        ];
    }

    protected function getSyncActions(): array
    {
        return [
            'testConnection' => 'testConnectionAction',
        ];
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

    /**
     * @throws UserException
     */
    private function openSshTunnel(Config $config): Config
    {
        try {
            $ssh = new SSH();
            $ssh->openTunnel($config->getSshConfig($config->getPort(), 21));

            // open tunnels to all FTP ports
            foreach ($this->getConfig()->getFtpPassivePorts() as $port) {
                $ssh->openTunnel($config->getSshConfig($port));
            }
        } catch (SSHException $e) {
            throw new UserException($e->getMessage());
        }

        $config->setHost('localhost');
        $config->setPort(21);

        return $config;
    }
}
