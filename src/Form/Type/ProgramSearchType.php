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

final class ProgramSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        // Mise en forme des établissements visibles par le stagiaire -> visibilité des centres
        $institutions = [];
        $institution = $options['institution'];
        // Récupération des établissements liés
        $visuInstitutions = $institution->getVisuinstitutions();
        // creer le tableau des établissements visibles
        $institutions[0] = $institution;
        foreach($visuInstitutions as $visuInstitution) {
            $institutions[] = $visuInstitution;
        }

        $organizations = $options['organizations'];

        $formBuilder
            ->add('centre', EntityType::class, ['label' => 'Centre organisateur', 'choice_label' => 'name', 'class' => Organization::class, 'query_builder' => static function (EntityRepository $entityRepository) use ($institutions) : \Doctrine\ORM\QueryBuilder {
                $queryBuilder = $entityRepository->createQueryBuilder('o');
                $queryBuilder->where('o.institution in (:institution)')
                    ->setParameter('institution', $institutions)
                    ->orWhere('o.institution is null');
                return $queryBuilder;
            }])
            ->add('theme', EntityType::class, ['label' => 'Domaine de formation', 'choice_label' => 'name', 'class' => Theme::class, 'query_builder' => static function (EntityRepository $entityRepository) use ($organizations) : \Doctrine\ORM\QueryBuilder {
                $queryBuilder = $entityRepository->createQueryBuilder('th');
                $queryBuilder->where('th.organization in (:organization)')
                    ->setParameter('organization', $organizations)
                    ->orWhere('th.organization is null');
                return $queryBuilder;
            }])
            ->add('texte', null, ['label' => 'Recherche par mot clé', 'required' => false, 'attr' => ['placeholder' => 'Tapez un mot clé']]);

    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(['data_class' => null, 'institution' => null, 'organizations' => null, 'id' => 'search']);
    }

    public function getName(): string
    {
        return 'search';
    }
}