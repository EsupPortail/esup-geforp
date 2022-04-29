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
use App\Entity\Core\Term\Publiposttemplate;

class PublipostTemplateVocabularyType extends VocabularyType
{
    /**
     * @var HumanReadablePropertyAccessorFactory
     */
    protected $HRPAFactory;

    public function __construct(ContainerInterface $container, HumanReadablePropertyAccessorFactory $HRPAfactory)
    {
        // Recup de la conf batch mailing
        $this->HRPAFactory = $HRPAfactory;
        $conf = $container->getParameter('batch');
        $this->HRPAFactory->setTermCatalog($conf['mailing']);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws MissingOptionsException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $tab = array_flip($this->HRPAFactory->getKnownEntities(false));
        $builder->add('entity', ChoiceType::class, array(
            'label' => 'Entité associée',
            'choices' => $tab,
        ));

        $builder->add('file', FileType::class, array(
            'label' => 'Fichier du modèle',
            'block_name' => 'updatable_file',
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Publiposttemplate::class,
        ));
    }
}
