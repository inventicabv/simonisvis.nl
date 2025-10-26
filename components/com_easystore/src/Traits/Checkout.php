<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Traits;

use Joomla\CMS\Extension\ExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Filesystem\Path;
use JoomShaper\Component\EasyStore\Site\Helper\ArrayHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

trait Checkout
{
    public function getPaymentMethodList()
    {
        $plugins = PluginHelper::getPlugin('easystore');

        foreach ($plugins as &$plugin) {
            if (!empty($plugin->params) && is_string($plugin->params)) {
                $params                    = json_decode($plugin->params);
                $plugin->title             = $params->title ?? $plugin->name;
                $plugin->instruction       = $params->instruction ?? '';
                $plugin->is_manual_payment = !empty($params->payment_type) ? (bool) $params->payment_type : false;
            }

            $plugin->logo = $this->getExtensionLogo($plugin->name);

            unset($plugin->params);
        }

        return $plugins;
    }


    public function getShippingCarriers()
    {
       $plugins = PluginHelper::getPlugin('easystoreshipping');

        foreach ($plugins as &$plugin) {
            if (!empty($plugin->params) && is_string($plugin->params)) {
                $params                    = json_decode($plugin->params);
                $plugin->title             = ucwords($params->title ?? $plugin->name);
            }

            $plugin->logo = $this->getExtensionLogo($plugin->name, 'easystoreshipping');

            unset($plugin->params);
        }

        return $plugins; 
    }

    public function getActivePayments()
    {
        $settings = SettingsHelper::getSettings();
        $payments = $settings->get('payment', []);

        if (empty($payments->list)) {
            return [];
        }

        $payments = ArrayHelper::filter(function ($item) {
            return $item->enabled ?? false;
        }, $payments->list);
        
        $levels = Factory::getApplication()->getIdentity()->getAuthorisedViewLevels();
        $payments = array_filter($payments, function ($payment) use ($levels) {
            $plugin = ExtensionHelper::getExtensionRecord($payment->name, 'plugin', 0 ,'easystore');
           return in_array($plugin->access, $levels);
        });

        return array_values($payments);
    }

    private function getExtensionLogo($name, $folder = 'easystore')
    {
        $paymentPluginPath = JPATH_ROOT . '/plugins/'. $folder;
        $paymentPluginUri  = Uri::root(true) . '/plugins/'. $folder;
        $logoPath          = $paymentPluginPath . '/' . $name . '/assets/images/logo.svg';
        $logoUrl           = Path::clean($paymentPluginUri . '/' . $name . '/assets/images/logo.svg');

        if (file_exists($logoPath)) {
            return $logoUrl;
        }

        return '';
    }
}
