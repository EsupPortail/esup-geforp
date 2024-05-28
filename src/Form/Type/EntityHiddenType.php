<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 25/06/14
 * Time: 16:48.
 */

namespace App\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use App\Form\DataTransformer\EntityToIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityHiddenType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['class'] === null) {
            throw new MissingOptionsException('Missing required class option ');
        } else {
            $transformer = new EntityToIdTransformer($this->em);
            $transformer->setEntityClass($options['class']);
            $builder->addViewTransformer($transformer);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'class' => null,
                'error_bubbling' => false,
            )
        );
    }

    public function getParent()
    {
        return HiddenType::class;
    }
}
