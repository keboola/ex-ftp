<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ConfigDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $parametersNode
            ->children()
                ->scalarNode('host')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('username')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('password')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('path')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('port')
                    ->validate()
                        ->ifTrue(function ($value) {
                            return !is_numeric($value) || $value < 0 ||$value > 65536;
                        })
                        ->thenInvalid('Port must be positive integer between 1-65535')
                ->end()
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
