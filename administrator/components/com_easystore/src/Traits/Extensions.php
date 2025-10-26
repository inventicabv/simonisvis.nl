<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Traits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\Path;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Installer\Installer;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Extension\ExtensionHelper;
use Joomla\CMS\Installer\InstallerHelper;
use JoomShaper\Component\EasyStore\Site\Helper\ArrayHelper;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;

/**
 * Extensions trait
 *
 * @since 1.0.9
 */
trait Extensions
{
    /**
     * Extension api end point
     *
     * @return void
     *
     * @since 1.0.9
     */
    public function extensions()
    {
        $requestMethod = $this->getInputMethod();

        $this->checkNotAllowedMethods(['PUT', 'PATCH'], $requestMethod);

        if ('GET' === $requestMethod) {
            $this->getExtensions();
        } elseif ('POST' === $requestMethod) {
            $this->installExtension();
        } elseif ('DELETE' === $requestMethod) {
            $this->uninstallExtension();
        }
    }

    /**
     * Get extensions list
     *
     * @return void
     *
     * @since 1.0.9
     */
    public function getExtensions()
    {
        $this->sendResponse($this->getInstalledExtensionList());
    }

    /**
     * Get Installed plugin list
     *
     * @return array
     *
     * @since 1.0.9
     */
    public function getInstalledExtensionList()
    {
        $extensions = SettingsHelper::getPluginSchema();

        if (empty($extensions)) {
            $this->sendResponse(['message' => 'Invalid extension data'], 500);
        }

        $installedExtensions = [];
        // list of all installed plugins
        $installedExtensions = $this->getInstalledExtensions('plugin', 'easystore');

        $pluginName = array_column($installedExtensions, 'element');

        foreach ($extensions as $item) {
            $item = (array) $item;

            // Check the plugin name for identify the manual payment plugin.
            $manualPaymentList         = ['custom', 'cod', 'banktransfer', 'cheque'];
            $item['is_manual_payment'] = in_array($item['element'], $manualPaymentList);

            if (!in_array($item['element'], $pluginName)) {
                $item['installed_version'] = $item['version'];
                $item['enabled']           = 0;
                $item['is_installed']      = 0;
                $installedExtensions[]     = $item;
                $pluginName[]              = $item['element'];
            } else {
                $key                       = array_search($item['element'], $pluginName);
                $installedExtensions[$key] = array_merge((array) $installedExtensions[$key], $item);
            }
        }

        foreach ($installedExtensions as &$extension) {
            if (is_object($extension)) {
                $extension = (array) $extension;
            }
            $extension['has_update'] = $this->hasExtensionUpdate($extension);
        }

        unset($extension);

        return $installedExtensions;
    }

    /**
     * Check extension has update
     *
     * @param array $extension
     *
     * @return bool
     * @since 1.0.9
     */
    protected function hasExtensionUpdate($extension)
    {
        $currentVersion   = $extension['version'];
        $installedVersion = $extension['installed_version'];
        $isInstalled      = $extension['is_installed'];

        if (!$isInstalled || is_null($installedVersion)) {
            return false;
        }

        if (version_compare($currentVersion, $installedVersion, '>')) {
            return true;
        }

        return false;
    }

    /**
     * Installed Extensions
     *
     * @param array $installedExtensions
     * @param string $element
     * @return object
     *
     * @since 1.0.9
     */
    protected function installedExtension($installedExtensions, $element)
    {
        return ArrayHelper::find(function ($extension) use ($element) {
            return $extension->element === $element;
        }, $installedExtensions);
    }

    /**
     * Get installed extensions list
     *
     * @param string $type
     * @param string $folder
     *
     * @return array
     * @since 1.0.9
     */
    protected function getInstalledExtensions($type, $folder): array
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('manifest_cache, params, enabled, element, folder')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote($type))
            ->where($db->quoteName('folder') . ' = ' . $db->quote($folder));

        $db->setQuery($query);

        $installedExtensions = $db->loadObjectList();

        if (empty($installedExtensions)) {
            return [];
        }

        $payment = array_map(function ($extension) {
            $version = '';
            $element = '';

            if (!empty($extension->manifest_cache)) {
                $manifest = json_decode($extension->manifest_cache);
                $version  = $manifest->version ?? '';
            }

            if (!empty($extension->params)) {
                $params        = json_decode($extension->params);
                $payment_type  = $params->payment_type ?? '';
            }

            if (str_contains($extension->element, '.')) {
                $parts   = explode('.', $extension->element);
                $element = count($parts) > 1 ? $parts[0] : '';
            } else {
                $element = $extension->element;
            }

            $pluginTitle = $element === 'cod' ? Text::_('COM_EASYSTORE_PAYMENT_METHOD_COD') : $element;

            return (object) [
                'version'           => $version,
                'is_manual_payment' => (bool) $payment_type,
                'title'             => $pluginTitle,
                'name'              => $pluginTitle,
                'installed_version' => $version,
                'enabled'           => $extension->enabled,
                'is_installed'      => $extension->enabled,
                'element'           => $element,
                'folder'            => $extension->folder,
                'logo'              => $this->getPaymentPluginLogo($element),
            ];
        }, $installedExtensions);

        return $payment;
    }

    /**
     * Install extension function
     *
     * @return void
     *
     * @since 1.0.9
     */
    public function installExtension()
    {
        /** @var CMSApplication */
        $app   = Factory::getApplication();
        $acl   = AccessControl::create();

        $element     = $this->getInput('element', null, 'STRING');
        $downloadUrl = $this->getInput('download_url', null, 'STRING');

        if (empty($element) || empty($downloadUrl)) {
            $this->sendResponse(['message' => 'Invalid plugin to install'], 400);
        }

        $authorised = $acl->isAdmin() || $acl->canManage();

        if (!$authorised) {
            $this->sendResponse(['message' => Text::_('JERROR_ALERTNOAUTHOR')], 403);
        }

        $packageFile = InstallerHelper::downloadPackage($downloadUrl);

        if (empty($packageFile)) {
            $this->sendResponse(['message' => 'Invalid installation file'], 400);
        }

        $config  = $app->getConfig();
        $tmpPath = $config->get('tmp_path');
        $package = InstallerHelper::unpack($tmpPath . '/' . $packageFile, true);

        $installer = Installer::getInstance();

        if (!$installer->install($package['dir'])) {
            $this->sendResponse(['message' => 'Failed to install the plugin'], 500);
        }

        $installedPlugin = ExtensionHelper::getExtensionRecord($element, 'plugin', 0, 'easystore');

        $pluginParams          = json_decode($installedPlugin->params);
        $plugin                = [];
        $plugin['id']          = $installedPlugin->extension_id;
        $plugin['instruction'] = '';
        $plugin['name']        = $installedPlugin->element;
        $plugin['title']       = $pluginParams->title;
        $plugin['type']        = $installedPlugin->folder;
        $plugin['logo']        = $this->getPaymentPluginLogo($element);

        $this->sendResponse($plugin, 200);
    }

    private function getPaymentPluginLogo($name)
    {
        $paymentPluginPath = JPATH_ROOT . '/plugins/easystore';
        $paymentPluginUri  = Uri::root(true) . '/plugins/easystore';
        $logoPath          = $paymentPluginPath . '/' . $name . '/assets/images/logo.svg';
        $logoUrl           = Path::clean($paymentPluginUri . '/' . $name . '/assets/images/logo.svg');

        if (file_exists($logoPath)) {
            return $logoUrl;
        }

        return '';
    }
}
