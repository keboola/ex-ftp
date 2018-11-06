<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ConfigDefinition extends BaseConfigDefinition
{
    public const CONNECTION_TYPE_FTP = 'ftp';
    public const CONNECTION_TYPE_IMPLICIT = 'ssl-implicit';
    public const CONNECTION_TYPE_EXPLICIT = 'ssl-explicit';
    public const CONNECTION_TYPE_SFTP = 'sftp';

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
                ->booleanNode('onlyNewFiles')
                    ->defaultFalse()
                ->end()
                ->booleanNode('wildcard')
                    ->defaultFalse()
                ->end()
                ->integerNode('port')
                    ->min(1)->max(65535)
                ->end()
                ->scalarNode('connectionType')
                    ->isRequired()
                    ->validate()->ifNotInArray([
                               self::CONNECTION_TYPE_FTP,
                               self::CONNECTION_TYPE_EXPLICIT,
                               self::CONNECTION_TYPE_IMPLICIT,
                               self::CONNECTION_TYPE_SFTP,
                            ])->thenInvalid('Connection type not recognized')
                        ->end()
                ->end()
                ->scalarNode('privateKey')
                    ->defaultNull()
                ->end()
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
