<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ConfigDefinition extends BaseConfigDefinition
{
    public const CONNECTION_TYPE_FTP = 'FTP';
    public const CONNECTION_TYPE_SSL_IMPLICIT = 'FTPS';
    public const CONNECTION_TYPE_SFTP = 'SFTP';

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
                    ->defaultValue(21)
                ->end()
                ->scalarNode('connectionType')
                    ->isRequired()
                    ->validate()->ifNotInArray([
                               self::CONNECTION_TYPE_FTP,
                               self::CONNECTION_TYPE_SSL_IMPLICIT,
                               self::CONNECTION_TYPE_SFTP,
                            ])->thenInvalid('Connection type not recognized')
                        ->end()
                ->end()
                ->scalarNode('privateKey')
                    ->defaultValue('')
                ->end()
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
