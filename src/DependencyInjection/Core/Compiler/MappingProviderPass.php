<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 01/09/14
 * Time: 10:23.
 */

namespace App\DependencyInjection\Core\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class MappingProviderPass.
 */
class MappingProviderPass implements CompilerPassInterface
{
    /**
     * Process the compiler pass.
     *
     */
    public function process(ContainerBuilder $container): void
    {
        // extract current config source
        $sourceConfigs = $container->getDefinition('fos_elastica.config_source.container')->getArgument(0);
        $container->getDefinition('sygefor_core.elastica_mapping_provider')->replaceArgument(0, $sourceConfigs);
    }
}
