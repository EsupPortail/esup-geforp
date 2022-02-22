<?php

namespace App\Form\Type;

use App\Form\Type\AbstractTraineeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ProfileType.
 */
class ProfileType extends AbstractType
{
    /**
     * @param $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
            'validation_groups' => array('Default', 'trainee', 'api.profile'),
            'enable_security_check' => false,
            'allow_extra_fields' => true,
        ));
    }

    /**
     * Helper : request data extractor.
     *
     * @param Request       $request
     * @param FormInterface $form
     *
     * @return array
     */
    public static function extractRequestData(Request $request, FormInterface $form)
    {
        return $request->request->all();
    }

    public function getParent()
    {
        return AbstractTraineeType::class;
    }
}
