<?php
namespace App\Form\Type;

use App\Utils\HumanReadable\HumanReadablePropertyAccessorFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Term\Publiposttemplate;

final class PublipostTemplateVocabularyType extends AbstractType
{
    private readonly bool|string|int|float|\UnitEnum|array|null $mailingConfig;

    /**
     * PublipostTemplateVocabularyType constructor.
     */
    public function __construct(protected HumanReadablePropertyAccessorFactory $humanReadablePropertyAccessorFactory, ParameterBagInterface $parameterBag, ContainerInterface $service_Container)
    {
        // Récupération de la configuration "batch" depuis le conteneur de services
        $this->mailingConfig = $parameterBag->get('batch');
        $this->humanReadablePropertyAccessorFactory->setTermCatalog($this->mailingConfig['mailing']);
    }

    /**
     * Construit le formulaire.
     *
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', null, [
            'label' => 'Nom',
            'required' => true,
        ]);
        // Construction du tableau d'options de l'entité
        $tab = array_flip($this->humanReadablePropertyAccessorFactory->getKnownEntities(false));

        // Ajout du champ 'entity'
        $builder->add('entity', ChoiceType::class, ['label' => 'Entité associée', 'choices' => $tab]);

        // Ajout du champ 'file'
        $builder->add('file', FileType::class, ['label' => 'Fichier du modèle', 'block_name' => 'updatable_file', 'required' => true]);
    }

    /**
     * Configure les options du formulaire.
     *
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Publiposttemplate::class]);
    }
}
