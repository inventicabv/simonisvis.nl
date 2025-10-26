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
use Joomla\CMS\Router\Route;
use JoomShaper\Component\EasyStore\Site\Helper\ArrayHelper;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;

/**
 * Plugins trait
 *
 * @since 1.0.9
 */
trait Plugins
{
    /**
     * Extension api end point
     *
     * @return void
     *
     * @since 1.0.9
     */
    public function plugins()
    {
        $requestMethod = $this->getInputMethod();

        $this->checkNotAllowedMethods(['PUT', 'PATCH'], $requestMethod);

        if ('GET' === $requestMethod) {
            $this->getPlugins();
        } elseif ('POST' === $requestMethod) {
            $this->installPlugin();
        } elseif ('DELETE' === $requestMethod) {
            $this->uninstallPlugin();
        }
    }

    /**
     * Get plugins list
     *
     * @return void
     *
     * @since 1.0.9
     */
    public function getPlugins()
    {
        $this->sendResponse($this->getInstalledPluginList());
    }

    /**
     * Get Installed plugin list
     *
     * @return array
     *
     * @since 1.0.9
     */
    public function getInstalledPluginList()
    {
        $plugins = SettingsHelper::getPluginSchema('shipping_carriers');

        if (empty($plugins)) {
            $this->sendResponse(['message' => 'Invalid plugin data'], 500);
        }

        $installedPlugins = [];
        // list of all installed plugins
        $installedPlugins = $this->getInstalledPlugins('plugin', 'easystoreshipping');

        $pluginName = array_column($installedPlugins, 'element');

        foreach ($plugins as $item) {
            $item = (array) $item;

            if (!in_array($item['element'], $pluginName)) {
                $item['installed_version'] = $item['version'];
                $item['enabled']           = 0;
                $item['is_installed']      = 0;
                $installedPlugins[]        = $item;
                $pluginName[]              = $item['element'];
            } else {
                $key                       = array_search($item['element'], $pluginName);
                $installedPlugins[$key] = array_merge((array) $installedPlugins[$key], $item);
            }
        }

        foreach ($installedPlugins as &$plugin) {
            if (is_object($plugin)) {
                $plugin = (array) $plugin;
            }
            if (isset($plugin['extension_id'])) {
                $plugin['edit_url'] = Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $plugin['extension_id'], false);
            }

            $plugin['has_update'] = $this->hasPluginUpdate($plugin);
        }

        unset($plugin);

        return $installedPlugins;
    }

    /**
     * Check plugin has update
     *
     * @param array $plugin
     *
     * @return bool
     * @since 1.0.9
     */
    protected function hasPluginUpdate($plugin)
    {
        $currentVersion   = $plugin['version'];
        $installedVersion = $plugin['installed_version'];
        $isInstalled      = $plugin['is_installed'];

        if (!$isInstalled || is_null($installedVersion)) {
            return false;
        }

        if (version_compare($currentVersion, $installedVersion, '>')) {
            return true;
        }

        return false;
    }

    /**
     * Installed Plugins
     *
     * @param array $installedPlugins
     * @param string $element
     * @return object
     *
     * @since 1.0.9
     */
    protected function installedPlugin($installedPlugins, $element)
    {
        return ArrayHelper::find(function ($plugin) use ($element) {
            return $plugin->element === $element;
        }, $installedPlugins);
    }

    /**
     * Get installed plugins list
     *
     * @param string $type
     * @param string $folder
     *
     * @return array
     * @since 1.0.9
     */
    protected function getInstalledPlugins($type, $folder): array
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('extension_id, manifest_cache, params, enabled, element, folder')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote($type))
            ->where($db->quoteName('folder') . ' = ' . $db->quote($folder));

        $db->setQuery($query);

        $installedPlugins = $db->loadObjectList();

        if (empty($installedPlugins)) {
            return [];
        }

        $carrier = array_map(function ($plugin) {
            $version = '';
            $element = '';
            $title = '';
            $params = '';

            if (!empty($plugin->manifest_cache)) {
                $manifest = json_decode($plugin->manifest_cache);
                $version  = $manifest->version ?? '';
            }

            if (str_contains($plugin->element, '.')) {
                $parts   = explode('.', $plugin->element);
                $element = count($parts) > 1 ? $parts[0] : '';
            } else {
                $element = $plugin->element;
            }

            if (!empty($plugin->params)) {
                $params = json_decode($plugin->params);
                if (isset($params->title) && !empty($params->title)) {
                    $title = $params->title;
                } else {
                    $title = $element;
                }
            }

            return (object) [
                'id'                => $plugin->extension_id,
                'version'           => $version,
                'title'             => ucwords($title),
                'name'              => ucwords($element),
                'installed_version' => $version,
                'enabled'           => $plugin->enabled,
                'is_installed'      => $plugin->enabled,
                'element'           => $element,
                'folder'            => $plugin->folder,
                'logo'              => $this->getPluginLogo($element),
            ];
        }, $installedPlugins);

        return $carrier;
    }

    /**
     * Install plugin function
     *
     * @return void
     *
     * @since 1.0.9
     */
    public function installPlugin()
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

        $installedPlugin = ExtensionHelper::getExtensionRecord($element, 'plugin', 0, 'easystoreshipping');

        $pluginParams          = json_decode($installedPlugin->params);
        $plugin                = [];
        $plugin['id']          = $installedPlugin->extension_id;
        $plugin['name']        = $installedPlugin->element;
        $plugin['title']       = $pluginParams->title;
        $plugin['type']        = $installedPlugin->folder;
        $plugin['logo']        = $this->getPluginLogo($element);
        $plugin['edit_url']   = Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $installedPlugin->extension_id, false);

        $this->sendResponse($plugin, 200);
    }

    private function getPluginLogo($name)
    {
        $pluginPath = JPATH_ROOT . '/plugins/easystoreshipping';
        $pluginUri  = Uri::root(true) . '/plugins/easystoreshipping';
        $logoPath          = $pluginPath . '/' . $name . '/assets/images/logo.svg';
        $logoUrl           = Path::clean($pluginUri . '/' . $name . '/assets/images/logo.svg');

        if (file_exists($logoPath)) {
            return $logoUrl;
        }

        return '';
    }
}
