<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 07/04/14
 * Time: 11:22.
 */

namespace App\DependencyInjection\Core\Compiler;

use App\BatchOperations\BatchOperationInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class BatchOperationRegistrationPass implements CompilerPassInterface
{
    /**
     * Process the compiler pass.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sygefor_core.batch_registry')) {
            return;
        }
        $definition = $container->getDefinition('sygefor_core.batch_registry');
        $batchOpServices = $container->findTaggedServiceIds('sygefor_core.batch_operation_provider');

        foreach ($batchOpServices as $id => $tagAttributes) {
            //checking class
            $class = $container->getDefinition($id)->getClass();
            if (!$class || !$this->isBatchOperationImplementation($class)) {
                throw new \InvalidArgumentException(sprintf('Batch Operation Registration : %s must implement BatchOperationInterface', $class));
            }

            if (!$container->getDefinition($id)->isAbstract()) {
                $definition->addMethodCall('addBatchOperation', array(new Reference($id), $id));
                $container->getDefinition($id)->addMethodCall('setEm', array(new Reference('doctrine.orm.entity_manager')));
            }
        }
    }

    private function isBatchOperationImplementation($class)
    {
        $refl = new \ReflectionClass($class);

        return $refl->implementsInterface(BatchOperationInterface::class);
    }
}
