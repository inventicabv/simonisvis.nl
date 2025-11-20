<?php

/**
 * @package     EasyStore.Plugin
 * @subpackage  System.EasyStoreAdminMail
 *
 * @copyright   (C) 2024. All rights reserved.
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
use JoomShaper\Plugin\System\EasyStoreAdminMail\Extension\EasyStoreAdminMail;

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
                $plugin     = new EasyStoreAdminMail(
                    $dispatcher,
                    (array) PluginHelper::getPlugin('system', 'easystoreadminmail')
                );
                $plugin->setApplication(Factory::getApplication());

                if (\defined('JDEBUG') && JDEBUG) {
                    $logLevels = Log::ALL;
                    Log::addLogger([
                        'text_file' => "easystore_adminmail.php",
                        'text_entry_format' => '{DATE} \t {TIME} \t {LEVEL} \t {CODE} \t {MESSAGE}',
                    ], $logLevels, ["email.easystore.adminmail"]);
                }

                return $plugin;
            }
        );
    }
};


