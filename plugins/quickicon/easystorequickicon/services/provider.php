<?php

/**
 * @package     EasyStore.Plugin
 * @subpackage  Quickicon.Easystoremigration
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

 // phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use JoomShaper\Plugin\Quickicon\Easystorequickicon\Extension\Easystorequickicon;

return new class () implements ServiceProviderInterface
{
    /**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   1.0.10
	 */
	public function register(Container $container)
	{
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $dispatcher = $container->get(DispatcherInterface::class);
                $plugin = new Easystorequickicon(
                    $dispatcher,
                    (array) PluginHelper::getPlugin('quickicon', 'easystorequickicon')
                );

                $plugin->setApplication(Factory::getApplication());
                return $plugin;
            }
        );
    }
};