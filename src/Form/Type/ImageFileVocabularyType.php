<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 07/07/14
 * Time: 14:12.
 */

namespace App\Form\Type;

use App\Utils\HumanReadable\HumanReadablePropertyAccessorFactory;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Term\ImageFile;

final class ImageFileVocabularyType extends VocabularyType
{
    /**
     *
     * @throws MissingOptionsException
     */
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        parent::buildForm($formBuilder, $options);

        $formBuilder->add('file', FileType::class, ['label' => 'Fichier du modèle', 'block_name' => 'updatable_file']);
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(['data_class' => ImageFile::class]);
    }
}
