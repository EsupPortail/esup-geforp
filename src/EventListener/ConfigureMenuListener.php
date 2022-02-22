<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 20/05/14
 * Time: 11:54.
 */

namespace App\EventListener;

use App\Entity\Core\AbstractOrganization;
use App\Entity\Core\AbstractInscription;
use App\Entity\Core\AbstractTrainee;
use App\Entity\Core\AbstractTrainer;
use App\Entity\Core\Term\AbstractTerm;
use App\Entity\Core\AbstractTraining;
use App\Entity\Core\User;
use App\Event\ConfigureMenuEvent;
use App\Entity\Core\Term\VocabularyInterface;
use Symfony\Component\Routing\Router;
use App\Utils\TrainingTypeRegistry;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Class ConfigureMenuListener.
 */
class ConfigureMenuListener
{
    /**
     * @var \Symfony\Component\Security\Core\SecurityContext
     */
    private $securityContext;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var TrainingTypeRegistry
     */
    private $registry;

    /**
     * Construct.
     */
    public function __construct(SecurityContext $securityContext, Router $router, TrainingTypeRegistry $registry)
    {
        $this->securityContext = $securityContext;
        $this->router = $router;
        $this->registry = $registry;
    }

    /**
     * @param $event
     */
    public function onMenuConfigure(ConfigureMenuEvent $event)
    {
        $menu = $event->getMenu();
        $adminMenu = $menu->getChild('administration');
        try {
            if ($this->securityContext->isGranted('VIEW', AbstractTraining::class)) {
                $item = $menu->addChild('trainings', array(
                    'label' => 'Événements',
                    'icon' => 'calendar',
                    'uri' => $this->router->generate('core.index').'#/training',
                ));
                foreach ($this->registry->getTypes() as $key => $type) {
                    $item->addChild('trainings.'.$key, array(
                        'label' => $type['label'],
                        'uri' => $this->router->generate('core.index').'#/training?type='.$key,
                    ));
                }
                $item->addChild('sessions', array(
                    'label' => 'Toutes les sessions',
                    'uri' => $this->router->generate('core.index').'#/training/session',
                ))->setAttribute('divider_prepend', true);
            }

            if ($this->securityContext->isGranted('VIEW', AbstractTrainee::class)) {
                $menu->addChild('trainees', array(
                    'label' => 'Publics',
                    'icon' => 'group',
                    'uri' => $this->router->generate('core.index').'#/trainee',
                ));
            }

            if ($this->securityContext->isGranted('VIEW', AbstractInscription::class)) {
                $menu->addChild('inscriptions', array(
                    'label' => 'Inscriptions',
                    'icon' => 'graduation-cap',
                    'uri' => $this->router->generate('core.index').'#/inscription',
                ));
            }

            if ($this->securityContext->isGranted('VIEW', AbstractTrainer::class)) {
                $menu->addChild('trainers', array(
                    'label' => 'Intervenants',
                    'icon' => 'user',
                    'uri' => $this->router->generate('core.index').'#/trainer',
                ));
            }

            if ($this->securityContext->isGranted('VIEW', AbstractOrganization::class)) {
                $adminMenu->addChild('organizations', array(
                        'label' => 'Centres',
                        'route' => 'organization.index',
                    )
                );
            }

            if ($this->securityContext->isGranted('VIEW', AbstractTerm::class) || $this->securityContext->isGranted('VIEW', VocabularyInterface::class)) {
                $adminMenu->addChild('taxonomy', array(
                        'label' => 'Vocabulaires',
                        'route' => 'taxonomy.index',
                    )
                );
            }

            if ($this->securityContext->isGranted('VIEW', User::class)) {
                $adminMenu->addChild('users', array(
                    'label' => 'Utilisateurs',
                    'route' => 'user.index',
                ));
            }
        } catch (AuthenticationCredentialsNotFoundException $e) {
        }
    }
}
