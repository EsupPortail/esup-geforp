<?php

namespace App\EventListener\Handler;

use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Form\FormView;

/**
 * Class FormViewHandler.
 */
final class FormViewHandler implements SubscribingHandlerInterface
{
    /**
     * @var string[]
     */
    private const BASE_TYPES = ['text', 'textarea', 'email', 'integer', 'money', 'number', 'password', 'percent', 'search', 'url', 'hidden', 'collection', 'choice', 'checkbox', 'radio', 'datetime', 'date', 'time'];

    public static function getSubscribingMethods(): array
    {
        return [['direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION, 'format' => 'json', 'type' => \Symfony\Component\Form\FormView::class, 'method' => 'serializeToJson']];
    }

    /**
     *
     * @return array
     */
    public function serializeToJson(JsonSerializationVisitor $jsonSerializationVisitor, FormView $formView, array $type, SerializationContext $serializationContext): array
    {
        $variables = $formView->vars;
        $element = ['id' => $variables['id'], 'name' => $variables['name'], 'full_name' => $variables['full_name'], 'label' => $variables['label'], 'errors' => $variables['errors'], 'value' => $variables['value'], 'required' => $variables['required'], 'attr' => $variables['attr'], 'valid' => $variables['valid']];

        foreach (['multiple', 'expanded', 'checked', 'allow_add', 'allow_delete'] as $optional) {
            if (isset($variables[$optional])) {
                $element[$optional] = $variables[$optional];
            }
        }

        // type
        foreach ($variables['block_prefixes'] as $blockPrefix) {
            if (in_array($blockPrefix, self::BASE_TYPES, true)) {
                $element['type'] = $blockPrefix; // We use the last found
            }
        }

        // children
        $children = [];
        foreach ($formView as $child) {
            $children[$child->vars['name']] = $this->serializeToJson($jsonSerializationVisitor, $child, $type, $serializationContext);
        }

        if ($children !== []) {
            $element['children'] = $children;
        }

        // choices
        if (isset($variables['choices'])) {
            $expanded = !empty($variables['expanded']);
            $element['choices'] = $this->buildChoices($variables['choices'], $expanded, $variables);

            if (!$variables['required'] && (!isset($variables['multiple']) || !$variables['multiple']) && $variables['value'] && !str_contains((string) $variables['id'], 'presence')) {
                array_unshift($element['choices'], ['v' => null, 'l' => isset($variables['empty_value']) && !empty($variables['empty_value']) ? $variables['empty_value'] : 'Aucun']);
            }
        }

        if (!$element['value'] && isset($variables['empty_value'])) {
            foreach ($element['choices'] as $choice) {
                if ($choice['v'] === $variables['empty_value']) {
                    $element['value'] = $variables['empty_value'];
                    break;
                }
            }
        }

        return $serializationContext->getNavigator()->accept($element, ['name' => 'array'], $serializationContext);
    }

    /**
     * Build the choices.
     *
     * @param $choices
     */
    private function buildChoices($choices, bool $expanded, $variables): array
    {
        if ($expanded) {
            $fullName = $variables['full_name'];
            $elementId = $variables['id'];

            $recursiveChoicesHandle = static function ($choices) use (&$recursiveChoicesHandle, $fullName, $elementId) : array {
                $return = [];
                foreach ($choices as $key => $choice) {
                    $return[] = ['id' => $key, 'name' => $fullName.'[]', 'v' => $choice->value, 'l' => $choice->label];
                }
                return $return;
            };

            return $recursiveChoicesHandle($choices);
        }
        $recursiveChoicesHandle = static function ($choices) use (&$recursiveChoicesHandle) : array {
            $return = [];
            foreach ($choices as $key => $choice) {
                if (is_array($choice)) {
                    $return[] = ['v' => $key, 'l' => $recursiveChoicesHandle($choice)];    // need to use a object to keep the order during JSON processing
                } else {
                    $return[] = ['v' => $choice->value, 'l' => $choice->label];
                }
            }
            return $return;
        };
        return $recursiveChoicesHandle($choices);
    }
}
