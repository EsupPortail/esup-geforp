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
use Symfony\Bundle\SecurityBundle\Security;

final class EmailTemplateVocabularyType extends VocabularyType
{
    public function __construct(
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
        private readonly Security $security
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
/*        $ccResolvers = $this->ccRegistry->getSupportedResolvers();
        $choices = array();
        foreach ($ccResolvers as $ccResolver) {
            $choices[] = $ccResolver['name'];
        }*/

        $builder
            ->add('subject', TextType::class, ['label' => 'Sujet'])
/*            ->add('cc', ChoiceType::class, array(
                'label' => 'CC',
                'multiple' => true,
                'expanded' => true,
                'choices' => $choices,
                'required' => false,
            ))*/
            ->add('body', TextareaType::class, ['label' => 'Corps', 'attr' => ['rows' => 10, 'ckeditor' => 'ckeditor']])
/*	        ->add('forceEmailSending', CheckboxType::class, array(
		        'label' => 'Abonnement',
		        'widget_suffix' => 'Envoi le courriel même si le stagiaire a désactivé les lettres d\'informations',
		        'required' => false,
	        ))*/
            ->add('inscriptionstatus', EntityType::class, ['label' => "Status d'inscription", 'class' => Inscriptionstatus::class, 'query_builder' => fn(EntityRepository $entityRepository): \Doctrine\ORM\QueryBuilder => $entityRepository->createQueryBuilder('i')
                ->where('i.organization = :orgId')
                ->orWhere('i.organization is null')
                ->orderBy('i.name')
                ->setParameter('orgId', $this->security->getUser()->getOrganization()->getId()), 'required' => false])
            ->add('attachmentTemplates', EntityType::class, ['label' => 'Modèles de pièces jointes', 'class' => Publiposttemplate::class, 'multiple' => 'true', 'query_builder' => static function (EntityRepository $entityRepository) use ($options) : \Doctrine\ORM\QueryBuilder {
                $data = $options['data'];
                $organization = null;
                if ($data && $data->getOrganization()) {
                    $organization = $data->getOrganization();
                }
                return $entityRepository->createQueryBuilder('d')
                    ->orWhere('d.organization = :organization')
                    ->orWhere('d.organization is null')
                    ->setParameter('organization', $organization->getId())
                    ->orderBy('d.name');
            }, 'required' => false])
            ->add('presencestatus', EntityType::class, ['label' => 'Statut de présence', 'class' => Presencestatus::class, 'query_builder' => fn(EntityRepository $entityRepository): \Doctrine\ORM\QueryBuilder => $entityRepository->createQueryBuilder('p')
                ->where('p.organization = :orgId')
                ->orWhere('p.organization is null')
                ->setParameter('orgId', $this->security->getUser()->getOrganization()->getId()), 'required' => false])
            ->add('cc', ChoiceType::class, [
                'label' => 'Envoyer une copie au N+1 et correspondant formation ',
                'choices' => [
                    'NON' => 0,
                    'OUI' => 1,
                ],
                'expanded' => true,
                'multiple' => true,
                'required' => false,
            ])
            ->add('private', CheckboxType::class, ['label' => 'Lien calendrier', 'required' => false])
            ->add('position', ChoiceType::class, ['label' => 'Format HTML', 'choices'  => [
                'NON' => 0,
                'OUI' => 1,
            ], 'placeholder' => false, 'required' => false]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Emailtemplate::class]);
    }
}
