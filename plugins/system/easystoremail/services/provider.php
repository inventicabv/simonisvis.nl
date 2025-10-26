<?php

/**
 * @package     EasyStore.Plugin
 * @subpackage  System.easystoremail
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\DI\Container;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\DispatcherInterface;
use Joomla\DI\ServiceProviderInterface;
use Joomla\CMS\Extension\PluginInterface;
use JoomShaper\Plugin\System\EasyStoreMail\Extension\EasyStoreMail;

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
                $dispatcher = $container->get(DispatcherInterface::class);
                $plugin     = new EasyStoreMail(
                    $dispatcher,
                    (array) PluginHelper::getPlugin('system', 'easystoremail')
                );
                $plugin->setApplication(Factory::getApplication());

                // Initializes a logging system based on the debugging mode.
                if (\defined('JDEBUG') && JDEBUG) {
                    $logLevels = Log::ALL;

                    Log::addLogger([
                        'text_file' => "easystore_email.php",
                        'text_entry_format' => '{DATE} \t {TIME} \t {LEVEL} \t {CODE} \t {MESSAGE}',
                    ], $logLevels, ["email.easystore"]);
                }

                return $plugin;
            }
        );
    }
};
