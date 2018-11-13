<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ConfigDefinition extends BaseConfigDefinition
{
    public const CONNECTION_TYPE_FTP = 'FTP';
    public const CONNECTION_TYPE_SSL_EXPLICIT = 'FTPS';
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
                ->scalarNode('#password')
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
                ->end()
                ->integerNode('port')
                    ->min(1)->max(65535)
                    ->defaultValue(21)
                ->end()
                ->scalarNode('connectionType')
                    ->isRequired()
                    ->validate()->ifNotInArray([
                               self::CONNECTION_TYPE_FTP,
                               self::CONNECTION_TYPE_SSL_EXPLICIT,
                               self::CONNECTION_TYPE_SFTP,
                            ])->thenInvalid(
                                sprintf(
                                    'Connection type must be one of %s.',
                                    implode(', ', [
                                        self::CONNECTION_TYPE_FTP,
                                        self::CONNECTION_TYPE_SSL_EXPLICIT,
                                        self::CONNECTION_TYPE_SFTP,
                                    ])
                                )
                            )
                        ->end()
                ->end()
                ->scalarNode('#privateKey')
                    ->defaultValue('')
                ->end()
                ->integerNode('timeout')
                    ->min(1)
                    ->defaultValue(10)
                ->end()
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
