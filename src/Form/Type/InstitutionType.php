<?php

namespace App\Form\Type;

use App\Entity\Term\Domain;
use App\Entity\Core\AbstractInstitution;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;

/**
 * Class InstitutionType
 */
class InstitutionType extends BaseInstitutionType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $object = $builder->getData();
        $builder
            ->add('idp', TextType::class, array(
                'label' => 'URL IDP',
                'required' => false,
            ))
            ->add('domains', EntityType::class, array(
                'label' => 'Noms de domaines',
                'class' => Domain::class,
                'choice_label' => 'name',
                'multiple' => true,
                'required' => false,
            ))
            ->add('visuinstitutions', EntityType::class, array(
                'label' => 'Autres établissements visibles',
                'class' => AbstractInstitution::class,
                'choice_label' => 'name',
                'multiple' => true,
                'required' => false,
                'query_builder' => function (EntityRepository $er) use ($object) {
                    return $er->createQueryBuilder('i')
                        ->where('i != :institution')
                        ->setParameter('institution', $object)
                        ->orderBy('i.name', 'ASC');
                },
            ));



        parent::buildForm($builder, $options);
    }
}
