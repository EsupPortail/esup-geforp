<?php


namespace App\Utils;

use Knp\Menu\FactoryInterface;
use Knp\Menu\Util\MenuManipulator;
use App\Event\ConfigureMenuEvent;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MenuBuilder.
 */
class MenuBuilder implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    /**
     * @param FactoryInterface $factory
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param Request $request
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function createMainMenu(Request $request)
    {
        $menu = $this->factory->createItem('root');
        /*$menu->addChild('home', array(
            'label' => 'Accueil',
            'route' => 'core.index'
        ));*/

        $menu->addChild('administration', array(
            'label' => 'Administration',
            'icon' => 'gear',
        ));

        $this->container->get('event_dispatcher')->dispatch(ConfigureMenuEvent::CONFIGURE, new ConfigureMenuEvent($this->factory, $menu));
        $this->container->get('event_dispatcher')->dispatch(ConfigureMenuEvent::ALTER, new ConfigureMenuEvent($this->factory, $menu));

        if ($menu->getChild('administration')->count() === 0) {
            $menu->removeChild('administration');
        } else {
            $manipulator = new MenuManipulator();
            $item = $menu->getChild('administration');
            $manipulator->moveToLastPosition($item);
        }

        return $menu;
    }
}
