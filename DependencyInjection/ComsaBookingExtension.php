<?php
/**
 * Created by PhpStorm.
 * User: cirykpopeye
 * Date: 2019-03-28
 * Time: 09:38
 */

namespace Comsa\BookingBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ComsaBookingExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->getLoader($container)->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container)
    {
        $this->getLoader($container)->load('doctrine.yaml');
    }

    private function getLoader(ContainerBuilder $container)
    {
        return new YamlFileLoader(
            $container,
            new FileLocator(__DIR__. '/../Resources/config')
        );
    }
}
