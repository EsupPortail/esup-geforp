<?php

/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 6/16/16
 * Time: 5:12 PM.
 */
namespace App\Form\Type;

use App\Form\Type\VocabularyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

final class TrainingcategoryType extends VocabularyType
{
    /**
     * @var array<string, string>
     */
    private const CHOICES = ['Stage' => 'internship'];
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $formBuilder->add('trainingType', ChoiceType::class, ['label'    => 'Type de formation', 'choices'  => self::CHOICES, 'required' => true]);

        parent::buildForm($formBuilder, $options);
    }

    public function getParent(): ?string
    {
        return VocabularyType::class;
    }
}
