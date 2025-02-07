<?php

namespace App\EventListener\Handler;

use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormErrorIterator;

/**
 * Class FormErrorIteratorHandler.
 */
final class FormErrorIteratorHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods(): array
    {
        return [['direction' => GraphNavigator::DIRECTION_SERIALIZATION, 'format' => 'json', 'type' => \Symfony\Component\Form\FormErrorIterator::class, 'method' => 'serializeToJson']];
    }

    /**
     *
     * @return mixed
     */
    public function serializeToJson(JsonSerializationVisitor $jsonSerializationVisitor, FormErrorIterator $formErrorIterator, array $type, SerializationContext $serializationContext)
    {
        return $serializationContext->getNavigator()->accept($this->getErrors($formErrorIterator->getForm()), ['name' => 'array'], $serializationContext);
    }

    /**
     *
     * @return string[]|mixed[][]
     */
    private function getErrors(Form $form): array
    {
        $errors = [];

        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }

        foreach ($form->all() as $key => $child) {
            if ($err = $this->getErrors($child)) {
                $errors[$key] = $err;
            }
        }

        return $errors;
    }
}
