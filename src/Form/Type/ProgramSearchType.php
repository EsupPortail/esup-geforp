<?php

namespace App\Form\Type;


use App\Entity\Back\Inscription;
use App\Entity\Core\AbstractInstitution;
use App\Entity\Term\ActionType;
use App\Entity\Core\AbstractTrainee;
use App\Entity\Core\AbstractSession;
use App\Entity\Term\Theme;
use App\Entity\Back\Organization;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;

class ProgramSearchType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Mise en forme des établissements visibles par le stagiaire -> visibilité des centres
        $institutions = array();
        $institution = $options['institution'];
        // Récupération des établissements liés
        $visuInstitutions = $institution->getVisuinstitutions();
        // creer le tableau des établissements visibles
        $institutions[0] = $institution;
        foreach($visuInstitutions as $visuInst) {
            $institutions[] = $visuInst;
        }

        $organizations = $options['organizations'];

        $builder
            ->add('centre', EntityType::class, array(
                'label' => 'Centre organisateur',
                'choice_label' => 'name',
                'class' => Organization::class,
                'query_builder' => function (EntityRepository $repository) use ($institutions) {
                    $qb = $repository->createQueryBuilder('o');
                    $qb->where('o.institution in (:institution)')
                        ->setParameter('institution', $institutions)
                        ->orWhere('o.institution is null');

                    return $qb;
                },
            ))
            ->add('theme', EntityType::class, array(
                'label' => 'Domaine de formation',
                'choice_label' => 'name',
                'class' => Theme::class,
                'query_builder' => function (EntityRepository $repository) use ($organizations) {
                    $qb = $repository->createQueryBuilder('th');
                    $qb->where('th.organization in (:organization)')
                        ->setParameter('organization', $organizations)
                        ->orWhere('th.organization is null');

                    return $qb;
                },
            ))
            ->add('texte', null, array(
                'label' => 'Recherche par mot clé',
                'required' => false,
                'attr' => array('placeholder' => 'Tapez un mot clé')
            ));

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
            'institution' => null,
            'organizations' => null,
            'id' => 'search'
        ));
    }

    public function getName()
    {
        return 'search';
    }
}