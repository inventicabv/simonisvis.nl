<?php

/**
 * @package     PlgEasystoreshippingPostnl
 * @subpackage  Service Provider
 *
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace PlgEasystoreshippingPostnl\Services;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $basePath = dirname(__DIR__);

                // Load required classes manually
                require_once $basePath . '/src/PostnlClient.php';
                require_once $basePath . '/src/OrderHelper.php';
                require_once $basePath . '/src/Extension/PostnlShipping.php';

                // Now we can instantiate the plugin
                $plugin = new \PlgEasystoreshippingPostnl\Extension\PostnlShipping(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('easystoreshipping', 'postnl')
                );

                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
