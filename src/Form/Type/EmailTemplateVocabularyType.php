<?php

namespace App\Form\Type;

use Doctrine\ORM\EntityRepository;
use App\Entity\Term\Emailtemplate;
use App\Entity\Term\Inscriptionstatus;
use App\Entity\Term\Presencestatus;
use App\Entity\Term\Publiposttemplate;
use App\Utils\Email\CCRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class EmailTemplateVocabularyType extends VocabularyType
{
    /** @var CCRegistry */
//    protected $ccRegistry;

    /**
     * @param CCRegistry
     */
 /*   public function setCCRegistry($ccRegistry)
    {
        $this->ccRegistry = $ccRegistry;
    }
 */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
/*        $ccResolvers = $this->ccRegistry->getSupportedResolvers();
        $choices = array();
        foreach ($ccResolvers as $ccResolver) {
            $choices[] = $ccResolver['name'];
        }*/

        $builder
            ->add('subject', TextType::class, array(
                'label' => 'Sujet',
            ))
/*            ->add('cc', ChoiceType::class, array(
                'label' => 'CC',
                'multiple' => true,
                'expanded' => true,
                'choices' => $choices,
                'required' => false,
            ))*/
            ->add('body', TextareaType::class, array(
                'label' => 'Corps',
                'attr' => array(
                    'rows' => 10,
                    'ckeditor' => 'ckeditor',
                ),
            ))
/*	        ->add('forceEmailSending', CheckboxType::class, array(
		        'label' => 'Abonnement',
		        'widget_suffix' => 'Envoi le courriel même si le stagiaire a désactivé les lettres d\'informations',
		        'required' => false,
	        ))*/
            ->add('inscriptionstatus', EntityType::class, array(
                'label' => "Status d'inscription",
                'class' => Inscriptionstatus::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('i')
                        ->where('i.organization = :orgId')
                        ->orWhere('i.organization is null')
                        ->orderBy('i.name')
                        ->setParameter('orgId', $this->security->getUser()->getOrganization()->getId());
                },
                'required' => false,
            ))
            ->add('attachmentTemplates', EntityType::class, array(
                'label' => 'Modèles de pièces jointes',
                'class' => Publiposttemplate::class,
                'multiple' => 'true',
                'query_builder' => function (EntityRepository $er) use ($options) {
                    $data = $options['data'];
                    $organization = null;
                    if ($data && $data->getOrganization()) {
                        $organization = $data->getOrganization();
                    }

                    return $er->createQueryBuilder('d')
                        ->orWhere('d.organization = :organization')
                        ->orWhere('d.organization is null')
                        ->setParameter('organization', $organization->getId())
                        ->orderBy('d.name');
                },
                'required' => false,
            ))
            ->add('presencestatus', EntityType::class, array(
                'label' => 'Statut de présence',
                'class' => Presencestatus::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->where('p.organization = :orgId')
                        ->orWhere('p.organization is null')
                        ->setParameter('orgId', $this->security->getUser()->getOrganization()->getId());
                },
                'required' => false,
            ))
            ->add('private', CheckboxType::class, array(
                'label' => 'Lien calendrier',
                'required' => false
            ))
            ->add('position', ChoiceType::class, array(
                'label' => 'Format HTML',
                'choices'  => [
                    'NON' => 0,
                    'OUI' => 1,
                ],
                'placeholder' => false,
                'required' => false
            ));

    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Emailtemplate::class,
        ));
    }
}
