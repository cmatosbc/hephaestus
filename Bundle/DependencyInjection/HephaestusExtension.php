<?php

namespace Hephaestus\Bundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class HephaestusExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yaml');

        $container->setParameter('hephaestus.exception_handling.max_retries', $config['exception_handling']['max_retries']);
        $container->setParameter('hephaestus.exception_handling.retry_delay', $config['exception_handling']['retry_delay']);
        $container->setParameter('hephaestus.logging.enabled', $config['logging']['enabled']);
        $container->setParameter('hephaestus.logging.channel', $config['logging']['channel']);
    }
}
