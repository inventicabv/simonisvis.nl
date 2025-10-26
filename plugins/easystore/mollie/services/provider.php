<?php

/**
 * @package     EasyStore.Site
 * @subpackage  EasyStore.Mollie
 *
 * @copyright   Copyright (C) 2023 - 2024 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\DI\Container;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\DispatcherInterface;
use Joomla\DI\ServiceProviderInterface;
use Joomla\CMS\Extension\PluginInterface;
use JoomShaper\Plugin\EasyStore\Mollie\Extension\MolliePayment;


return new class () implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $dispatcher = $container->get(DispatcherInterface::class);
                $plugin = new MolliePayment(
                    $dispatcher,
                    (array) PluginHelper::getPlugin('easystore', 'mollie')
                );
                $plugin->setApplication(Factory::getApplication());

                // Initializes a logging system based on the debugging mode.
                if (\defined('JDEBUG') && JDEBUG) {
                    $logLevels = Log::ALL;

                    Log::addLogger([
                        'text_file' => "easystore_mollie.php",
                        'text_entry_format' => '{DATE} \t {TIME} \t {LEVEL} \t {CODE} \t {MESSAGE}',
                    ], $logLevels, ["mollie.easystore"]);
                }

                return $plugin;
            }
        );
    }
};
