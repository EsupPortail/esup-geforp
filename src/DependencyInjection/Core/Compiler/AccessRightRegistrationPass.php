<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 14/03/14
 * Time: 16:31.
 */

namespace App\DependencyInjection\Core\Compiler;

use App\Security\Authorization\AccessRight\AccessRightInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AccessRightRegistrationPass implements CompilerPassInterface
{
    /**
     * Process the compiler pass.
     *
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sygefor_core.access_right_registry')) {
            return;
        }

        $definition = $container->getDefinition('sygefor_core.access_right_registry');
        $rightsRegistrants = $container->findTaggedServiceIds('sygefor_core.right_provider');
        foreach ($rightsRegistrants as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                //checking class
                $class = $container->getDefinition($id)->getClass();
                if (!$class || !$this->isAccessRightImplementation($class)) {
                    throw new \InvalidArgumentException(sprintf('Access Right Registration : %s must implement AccessRightInterface', $class));
                }
                $definition->addMethodCall(
                    'addAccessRight', [$id, new Reference($id), $attributes['group'] ?? 'Misc']
                );
            }
        }
    }

    /**
     * Returns whether the class implements AccessRightProviderInterface.
     *
     *
     */
    private function isAccessRightImplementation(string $class): bool
    {
        $refl = new \ReflectionClass($class);

        return $refl->implementsInterface(AccessRightInterface::class);
    }
}
