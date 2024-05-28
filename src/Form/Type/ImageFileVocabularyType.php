<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 07/07/14
 * Time: 14:12.
 */

namespace App\Form\Type;

use App\Utils\HumanReadable\HumanReadablePropertyAccessorFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Term\ImageFile;

class ImageFileVocabularyType extends VocabularyType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws MissingOptionsException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('file', FileType::class, array(
            'label' => 'Fichier du modèle',
            'block_name' => 'updatable_file',
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ImageFile::class,
        ));
    }
}
