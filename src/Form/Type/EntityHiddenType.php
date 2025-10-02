<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 25/06/14
 * Time: 16:48.
 */

namespace App\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use App\Form\DataTransformer\EntityToIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EntityHiddenType extends AbstractType
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        if ($options['class'] === null) {
            throw new MissingOptionsException('Missing required class option ');
        }
        $entityToIdTransformer = new EntityToIdTransformer($this->entityManager);
        $entityToIdTransformer->setEntityClass($options['class']);

        $formBuilder->addViewTransformer($entityToIdTransformer);
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(
            ['class' => null, 'error_bubbling' => false]
        );
    }

    public function getParent(): ?string
    {
        return HiddenType::class;
    }
}
