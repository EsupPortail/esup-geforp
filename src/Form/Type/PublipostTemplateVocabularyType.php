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
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        parent::buildForm($formBuilder, $options);

        // Construction du tableau d'options de l'entité
        $tab = array_flip($this->humanReadablePropertyAccessorFactory->getKnownEntities(false));

        // Ajout du champ 'entity'
        $formBuilder->add('entity', ChoiceType::class, ['label' => 'Entité associée', 'choices' => $tab]);

        // Ajout du champ 'file'
        $formBuilder->add('file', FileType::class, ['label' => 'Fichier du modèle', 'block_name' => 'updatable_file']);
    }

    /**
     * Configure les options du formulaire.
     *
     */
    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(['data_class' => Publiposttemplate::class]);
    }
}
