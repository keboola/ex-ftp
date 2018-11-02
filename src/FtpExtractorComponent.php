<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use Keboola\Component\BaseComponent;

class FtpExtractorComponent extends BaseComponent
{
    public function run(): void
    {
        // @TODO implement
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
