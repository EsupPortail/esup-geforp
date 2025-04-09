<?php
/*
namespace App\Menu;

use App\Entity\Core\AbstractInscription;
use App\Entity\Core\AbstractOrganization;
use App\Entity\Core\AbstractTrainee;
use App\Entity\Core\AbstractTrainer;
use App\Entity\Core\AbstractTraining;
use App\Entity\Core\Term\AbstractTerm;
use App\Entity\Core\Term\VocabularyInterface;
use App\Entity\Core\User;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Util\MenuManipulator;
use App\Event\ConfigureMenuEvent;
use Symfony\Component\HttpFoundation\Request;
*/

namespace App\Menu;

use App\Vocabulary\VocabularyInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Menu\FactoryInterface;
use Knp\Menu\Util\MenuManipulator;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Routing\Router;
use App\Event\ConfigureMenuEvent;
use App\Vocabulary\VocabularyRegistry;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class MenuBuilder
{
    private readonly FactoryInterface $menuFactory;

    public function __construct(
        FactoryInterface $menuFactory,
        private AuthorizationCheckerInterface $authorizationChecker,
        private Router $router,
        private VocabularyRegistry $vocabularyRegistry,
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
        $this->menuFactory = $menuFactory;
    }

    /**
     * @throws NotSupported
     */
    public function createMainMenu(array $options): ItemInterface
    {
        $menu = $this->menuFactory->createItem('root', array(
            'childrenAttributes' => array(
                'class' => 'nav navbar-nav',
            )
        ));

        // Menu administration et sous menus
        $adminMenu = $menu->addChild('administration', ['label' => 'Administration', 'icon' => 'gear', 'uri' => $this->router->generate('core.index')]);

        $organisation = $this->entityManager->getRepository(\App\Entity\Back\Organization::class);
        if ($organisation && $this->authorizationChecker->isGranted('VIEW', $organisation)) {
            $adminMenu->addChild('organizations', ['label' => 'Centres', 'uri' => $this->router->generate('organization.index')]
            );
        }
        $term = $this->entityManager->getRepository(\App\Entity\Term\AbstractTerm::class);
        if ($term && $this->authorizationChecker->isGranted('VIEW', $term)) {
            $adminMenu->addChild('taxonomy', [
                'label' => 'Vocabulaires',
                'uri' => $this->router->generate('taxonomy.index')
            ]);
        }

        $user = $this->entityManager->getRepository(\App\Entity\Core\User::class);
        if ($user && $this->authorizationChecker->isGranted('VIEW', $user)) {
            $adminMenu->addChild('users', ['label' => 'Utilisateurs', 'uri' => $this->router->generate('user.index')]);
        }

        try {
            if($this->authorizationChecker->isGranted('VIEW', \App\Entity\Back\Internship::class)) {
                $item = $menu->addChild('trainings', ['label' => 'Événements', 'icon'  => 'calendar', 'uri'   => $this->router->generate('core.index') . '#/training', 'attributes' => ['class' => 'dropdown-toggle']]);

                $item->addChild('internships', ['label' => 'Stages', 'uri'   => $this->router->generate('core.index') . '#/training?type=internship']);

                $item->addChild('sessions', ['label' => 'Toutes les sessions', 'uri'   => $this->router->generate('core.index') . '#/training/session'])->setAttribute('divider_prepend', true);

            }

            if($this->authorizationChecker->isGranted('VIEW', \App\Entity\Back\Trainee::class)) {
                $menu->addChild('trainees', ['label' => 'Publics', 'icon'  => 'group', 'uri'   => $this->router->generate('core.index') . '#/trainee']);
            }

            if($this->authorizationChecker->isGranted('VIEW', \App\Entity\Back\Inscription::class)) {
                $menu->addChild('inscriptions', ['label' => 'Inscriptions', 'icon'  => 'graduation-cap', 'uri'   => $this->router->generate('core.index') . '#/inscription']);
            }

            if($this->authorizationChecker->isGranted('VIEW', \App\Entity\Back\Institution::class)) {
                $menu->addChild('institutions', ['label' => 'Etablissements', 'icon'  => 'university', 'uri'   => $this->router->generate('core.index') . '#/institution']);
            }

            // Vocabulary id=6 => menuitem
            $menuitemTerm = $this->vocabularyRegistry->getVocabularyById(6);

            if ($menuitemTerm instanceof VocabularyInterface) {
                // Assurez-vous que c'est un objet de type `VocabularyInterface`
                $entityRepository = $this->managerRegistry->getManager()->getRepository(get_class($menuitemTerm));

                if (($entityRepository->findAll() !== null) && (count($entityRepository->findAll()) > 0)) {
                    $item = $menu->addChild('menuitems', ['label' => 'Liens externes', 'icon' => 'external-link', 'uri' => '']);
                    foreach ($entityRepository->findAll() as $menuitem) {
                        $item->addChild($menuitem->getName(), ['label' => $menuitem->getName(), 'uri' => $menuitem->getLink()]);
                    }
                }
            } else {
                // Log ou var_dump pour examiner la valeur retournée
                  // ou $this->logger->error("Erreur : l'objet retourné n'est pas une instance de VocabularyInterface");
            }

            if (!$menuitemTerm instanceof VocabularyInterface) {
              $this->logger->info("L'objet retourné n'est pas une instance de VocabularyInterface", ['vocabulary_id' => 6]);
            }

            if($this->authorizationChecker->isGranted('VIEW', \App\Entity\Back\Trainer::class)) {
                $menu->addChild('trainers', ['label' => 'Intervenants', 'icon'  => 'user', 'uri'   => $this->router->generate('core.index') . '#/trainer']);
            }

        } catch (AuthenticationCredentialsNotFoundException) {
        }

        if (!isset($adminMenu)) {
            $menu->removeChild('administration');
        }
        else {
            $menuManipulator = new MenuManipulator();
            $item = $menu->getChild('administration');
            $menuManipulator->moveToLastPosition($item);
        }

        return $menu;
    }
}

