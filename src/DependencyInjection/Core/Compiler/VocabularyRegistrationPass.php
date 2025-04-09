<?php

namespace App\DependencyInjection\Core\Compiler;

use App\Entity\Term\VocabularyInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class VocabularyRegistrationPass.
 */
final class VocabularyRegistrationPass implements CompilerPassInterface
{
    /**
     *
     * @throws \InvalidArgumentException
     */
    public function process(ContainerBuilder $containerBuilder): void
    {
        if (!$containerBuilder->hasDefinition('sygefor_core.vocabulary_registry')) {
            return;
        }

        $definition = $containerBuilder->getDefinition('sygefor_core.vocabulary_registry');
        $vocabularySevices = $containerBuilder->findTaggedServiceIds('sygefor_core.vocabulary_provider');
        foreach ($vocabularySevices as $id => $tagAttributes) {
            //checking class
            $class = $containerBuilder->getDefinition($id)->getClass();
            if (!$class || !$this->isVocabularyProviderImplementation($class)) {
                throw new \InvalidArgumentException(sprintf('Vocabulary Registration : %s must implement VocabularyInterface', $class));
            }

            foreach ($tagAttributes as $tagAttribute) {
                $definition->addMethodCall(
                    'addVocabulary', [new Reference($id), $id, $tagAttribute['group'], $tagAttribute['label'] ?? null]
                );
            }
        }
    }

    /**
     * Returns whether the class implements VocabularyInterface.
     *
     *
     */
    private function isVocabularyProviderImplementation(string $class): bool
    {
        $reflectionClass = new \ReflectionClass($class);

        return $reflectionClass->implementsInterface(VocabularyInterface::class);
    }
}
