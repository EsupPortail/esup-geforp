<?php

namespace App\DependencyInjection\Core\Compiler;

use App\BatchOperations\SemesteredTraining\SemesteredTrainingCSVBatchOperation;
use App\BatchOperations\SemesteredTraining\SemesteredTrainingMailingBatchOperation;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds all services with the tags "sygefor_training.type" as
 * arguments of the "sygefor_core.registry.training_type" service.
 */
class TrainingTypeRegistryPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        // changing class for semestered training publipost service
        if ($container->hasDefinition('sygefor_core.batch.publipost.semestered_training')) {
            $serviceDef = $container->getDefinition('sygefor_core.batch.publipost.semestered_training');
            $serviceDef->setClass(SemesteredTrainingMailingBatchOperation::class);
        }

        // changing class for semestered training publipost service
        if ($container->hasDefinition('sygefor_core.batch.csv.semestered_training')) {
            $serviceDef = $container->getDefinition('sygefor_core.batch.csv.semestered_training');
            $serviceDef->setClass(SemesteredTrainingCSVBatchOperation::class);
        }

        if (!$container->hasDefinition('sygefor_core.registry.training_type')) {
            return;
        }

        $definition = $container->getDefinition('sygefor_core.registry.training_type');

        // Builds an array with service IDs as keys and tag aliases as values
        $types = array();
        foreach ($container->findTaggedServiceIds('sygefor_core.training_type') as $serviceId => $tag) {
            $def = $container->getDefinition($serviceId);
            $class = $def->getClass();
            $type = isset($tag[0]['alias']) ? $tag[0]['alias'] : $class::getType();
            $types[$type] = array(
                'class' => $class,
                'label' => $alias = isset($tag[0]['label']) ? $tag[0]['label'] : $class::getTypeLabel(),
            );
        }
        $definition->replaceArgument(0, $types);
    }
}
