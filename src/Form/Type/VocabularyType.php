<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Forms;


/**
 * Class VocabularyType.
 */
class VocabularyType extends AbstractType
{
    /**
     * @var \Symfony\Component\Form\FormFactoryInterface
     */
    public $factory;
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $formBuilder->add('name', null, ['label' => 'Nom']);
    }
}
