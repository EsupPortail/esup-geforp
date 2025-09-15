<?php

namespace App\Bundle\AdminShibbolethBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;


final class AdminShibbolethExtension extends Extension
{

    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $containerBuilder): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $containerBuilder->setParameter('shibboleth', $config);


        $yamlFileLoader = new Loader\YamlFileLoader($containerBuilder, new FileLocator(__DIR__.'/../Resources/config'));
        $yamlFileLoader->load('services.yml');
    }
}
