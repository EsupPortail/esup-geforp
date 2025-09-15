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
final class TrainingTypeRegistryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $containerBuilder): void
    {
        // changing class for semestered training publipost service
        if ($containerBuilder->hasDefinition('sygefor_core.batch.publipost.semestered_training')) {
            $serviceDef = $containerBuilder->getDefinition('sygefor_core.batch.publipost.semestered_training');
            $serviceDef->setClass(SemesteredTrainingMailingBatchOperation::class);
        }

        // changing class for semestered training publipost service
        if ($containerBuilder->hasDefinition('sygefor_core.batch.csv.semestered_training')) {
            $serviceDef = $containerBuilder->getDefinition('sygefor_core.batch.csv.semestered_training');
            $serviceDef->setClass(SemesteredTrainingCSVBatchOperation::class);
        }

        if (!$containerBuilder->hasDefinition('sygefor_core.registry.training_type')) {
            return;
        }

        $definition = $containerBuilder->getDefinition('sygefor_core.registry.training_type');

        // Builds an array with service IDs as keys and tag aliases as values
        $types = [];
        foreach ($containerBuilder->findTaggedServiceIds('sygefor_core.training_type') as $serviceId => $tag) {
            $def = $containerBuilder->getDefinition($serviceId);
            $class = $def->getClass();
            $type = $tag[0]['alias'] ?? $class::getType();
            $types[$type] = ['class' => $class, 'label' => $alias = $tag[0]['label'] ?? $class::getTypeLabel()];
        }

        $definition->replaceArgument(0, $types);
    }
}
