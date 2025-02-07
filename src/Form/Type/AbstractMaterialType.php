<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 10/07/14
 * Time: 14:35.
 */

namespace App\Form\Type;

use App\Entity\Core\Material;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class AbstractMaterialType.
 */
class AbstractMaterialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(['data_class' => Material::class, 'csrf_protection' => false]
        );
    }

}
