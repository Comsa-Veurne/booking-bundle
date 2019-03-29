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
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ComsaBookingBundleExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__. '/../Resources/config')
        );
        $loader->load('services.yaml');
    }
}