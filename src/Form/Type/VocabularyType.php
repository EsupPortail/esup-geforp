<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Security\Core\Security;

/**
 * Class VocabularyType.
 */
class VocabularyType extends AbstractType
{
    /**
     * @var Security;
     */
    protected $security;

    /**
     * @param Security
     */
    public function __construct(Security $security)
    {
        $this->$security = $security;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', null, array(
            'label' => 'Nom',
        ));
    }

    protected function setUp()
    {
        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->getFormFactory();
    }
}
