<?php

namespace App\Utils;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Util\MenuManipulator;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class MenuBuilder.
 */
final class MenuBuilder
{
    public $factory;
    /**
     * Constructor
     *
     */
    public function __construct(private readonly LoggerInterface $logger, private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    public function createMainMenu(Request $request): ItemInterface
    {
        $menu = $this->logger->createItem('root');

// Add some children to the menu
        $menu->addChild('administration', [
            'label' => 'Administration',
            'icon' => 'gear',
        ]);

// Dispatch the 'configure' and 'alter' events with the menu
        $this->eventDispatcher->dispatch(new ConfigureMenuEvent($this->factory, $menu), ConfigureMenuEvent::CONFIGURE);
        $this->eventDispatcher->dispatch(new ConfigureMenuEvent($this->factory, $menu), ConfigureMenuEvent::ALTER);

// Manipulate the menu (move the 'administration' item to the last position if it has children)
        if ($menu->getChild('administration')->count() === 0) {
            $menu->removeChild('administration');
        } else {
            $menuManipulator = new MenuManipulator();
            $item = $menu->getChild('administration');
            $menuManipulator->moveToLastPosition($item);
        }

        return $menu;
    }
}
