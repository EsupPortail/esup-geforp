<?php

namespace CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EmailCCRegistryPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sygefor_core.registry.email_cc_resolver')) {
            return;
        }

        $resolvers = array();
        $definition = $container->getDefinition('sygefor_core.registry.email_cc_resolver');
        foreach ($container->findTaggedServiceIds('sygefor_core.email_resolver') as $serviceId => $tag) {
            $def = $container->getDefinition($serviceId);
            $class = $def->getClass();
            $resolvers[] = $class;
        }
        $definition->replaceArgument(0, $resolvers);
    }
}
