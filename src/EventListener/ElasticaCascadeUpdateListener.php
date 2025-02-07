<?php

namespace App\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use App\Utils\ElasticaMappingProvider;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Class ElasticaCascadeUpdateListener.
 */
final class ElasticaCascadeUpdateListener implements EventSubscriber
{
    private array $postFlushArguments = [];

    public function __construct(private readonly Kernel $kernel, private readonly ElasticaMappingProvider $elasticaMappingProvider)
    {
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     */
    public function getSubscribedEvents(): array
    {
        return [Events::preRemove, Events::postPersist, Events::preUpdate, Events::postFlush];
    }

    public function preRemove(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $commands = $this->elasticaMappingProvider->getPostDeletionCommandLines($lifecycleEventArgs->getEntity());

        foreach ($commands as $command) {
            $this->postFlushArguments[] = $command;
        }
    }

    public function postPersist(LifecycleEventArgs $lifecycleEventArgs): void
    {
        if (method_exists($lifecycleEventArgs->getEntity(), 'getId')) {
            $this->postFlushArguments[] = [$lifecycleEventArgs->getEntity()->getId(), $lifecycleEventArgs->getEntity()::class, $lifecycleEventArgs->getEntity()->getId()];
        }
    }

    public function preUpdate(\Doctrine\ORM\Event\LifecycleEventArgs|\Doctrine\ORM\Event\PreUpdateEventArgs $eventArgs): void
    {
        if (method_exists($eventArgs->getEntity(), 'getId')) {
            // @todo : list modified properties
            $this->postFlushArguments[] = [$eventArgs->getEntity()->getId(), $eventArgs->getEntity()::class, 'id'];
        }
    }

    public function postFlush(PostFlushEventArgs $postFlushEventArgs)
    {
        $idsByClass = [];
        $propertiesById = [];

        // refactor arguments to send them to console command
        foreach ($this->postFlushArguments as $postFlushArgument) {
            $idsByClass[$postFlushArgument[1]][] = $postFlushArgument[0];
            $propertiesById[$postFlushArgument[0]] = $postFlushArgument[2];
        }

        $appDir = $this->kernel->getRootDir();
        $env = $this->kernel->getEnvironment();
        foreach ($idsByClass as $class => $ids) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $process = new Process(
                    (array)'php '
                );
                $process->run();
                if (!$process->isSuccessful()) {
                    throw new \RuntimeException($process->getErrorOutput());
                }
            } else {
                // prepare the process
                $env = $this->kernel->getEnvironment();
                $args = ['nohup', 'php', $appDir.'/console', '--env='.$env, 'sygeforelasticascade:cascade', $class, json_encode($ids, JSON_THROW_ON_ERROR), json_encode($propertiesById, JSON_THROW_ON_ERROR)];

                $pb = new ProcessBuilder($args);
                $process = $pb->getProcess();
                $process->setCommandLine($process->getCommandLine().' &'); // add ampersand

                // run process
                $process->start();
                //                if (!$process->isSuccessful()) {
//                    throw new \RuntimeException($process->getErrorOutput());
//                }
            }
        }
    }
}
