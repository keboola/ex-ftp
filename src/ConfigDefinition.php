<?php

declare(strict_types=1);

namespace Keboola\FtpExtractor;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ConfigDefinition extends BaseConfigDefinition
{
    public const CONNECTION_TYPE_FTP = 'FTP';
    public const CONNECTION_TYPE_SSL_EXPLICIT = 'FTPS';
    public const CONNECTION_TYPE_SFTP = 'SFTP';

    public const LISTING_RECURSION = 'recursion';
    public const LISTING_MANUAL = 'manual';

    protected function getRootDefinition(TreeBuilder $treeBuilder): ArrayNodeDefinition
    {
        $rootNode = parent::getRootDefinition($treeBuilder);

        $rootNode->validate()->always(function ($v) {
            if (isset($v['image_parameters']['approvedHostnames'])) {
                foreach ($v['image_parameters']['approvedHostnames'] as $approvedHostname) {
                    if ($v['parameters']['host'] === $approvedHostname['host'] &&
                        $v['parameters']['port'] === $approvedHostname['port']
                    ) {
                        return $v;
                    }
                }
                throw new InvalidConfigurationException(sprintf(
                    'Hostname "%s" with port "%s" is not approved.',
                    $v['parameters']['host'],
                    $v['parameters']['port']
                ));
            }
            return $v;
        })->end();

        return $rootNode;
    }

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
                    ->defaultValue('')
                ->end()
                ->scalarNode('path')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->booleanNode('onlyNewFiles')
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
                    ->defaultValue(60)
                ->end()
                ->enumNode('listing')
                    ->values([self::LISTING_MANUAL, self::LISTING_RECURSION])
                    ->defaultValue(self::LISTING_RECURSION)
                ->end()
                ->booleanNode('ignorePassiveAddress')
                    ->defaultFalse()
                ->end()
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
