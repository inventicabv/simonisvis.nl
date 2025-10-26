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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Installer\Installer;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Application\CMSApplication;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Site\Helper\ArrayHelper;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;

trait Languages
{
    public function languages()
    {
        $requestMethod = $this->getInputMethod();

        $this->checkNotAllowedMethods(['PUT', 'DELETE', 'PATCH'], $requestMethod);

        if ($requestMethod === 'GET') {
            $this->getLanguages();
        } elseif ($requestMethod === 'POST') {
            $this->install();
        }
    }

    public function getLanguages()
    {
        $url = 'https://www.joomshaper.com/resources/easystore/languages.json';

        $content = SettingsHelper::fetchContent($url);

        if (empty($content)) {
            $this->sendResponse(['message' => 'Languages not found!'], 404);
        }

        $content = json_decode($content);

        if (empty($content->languagePacks)) {
            $this->sendResponse(['message' => 'Invalid language data'], 500);
        }

        $installed = $this->getInstalledLanguages();

        $languages = array_map(function ($language) use ($installed) {
            $item                        = $this->installedLanguage($installed, $language->code);
            $language->is_installed      = !empty($item) && $item->enabled;
            $language->installed_version = !empty($item) ? $item->version : null;
            $language->has_update        = $this->hasUpdate($language);
            $language->file              = $language->file;

            return $language;
        }, $content->languagePacks);

        $this->sendResponse($languages);
    }

    protected function hasUpdate($language)
    {
        $currentVersion   = $language->version;
        $installedVersion = $language->installed_version;
        $isInstalled      = $language->is_installed;

        if (!$isInstalled || is_null($installedVersion)) {
            return false;
        }

        if (version_compare($currentVersion, $installedVersion, '>')) {
            return true;
        }

        return false;
    }

    protected function installedLanguage($installedLanguages, $code)
    {
        return ArrayHelper::find(function ($language) use ($code) {
            return $language->code === $code;
        }, $installedLanguages);
    }

    protected function getInstalledLanguages()
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('manifest_cache, enabled, element')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('file'))
            ->where($db->quoteName('element') . ' LIKE ' . $db->quote('%.com_easystore'));

        $db->setQuery($query);

        $installedLanguages = $db->loadObjectList();

        if (empty($installedLanguages)) {
            return [];
        }

        return array_map(function ($language) {
            $version = '';
            $code    = '';

            if (!empty($language->manifest_cache)) {
                $manifest = json_decode($language->manifest_cache);
                $version  = $manifest->version ?? '';
            }

            if (str_contains($language->element, '.')) {
                $parts = explode('.', $language->element);
                $code  = count($parts) > 1 ? $parts[0] : '';
            }

            return (object) [
                'version' => $version,
                'enabled' => $language->enabled,
                'code'    => $code,
            ];
        }, $installedLanguages);
    }

    public function install()
    {
        /** @var CMSApplication */
        $app  = Factory::getApplication();
        $user = $app->getIdentity();
        $acl  = AccessControl::create();

        $languageCode = $this->getInput('code', null, 'STRING');
        $downloadUrl  = $this->getInput('download_url', null, 'STRING');

        if (empty($languageCode) || empty($downloadUrl)) {
            $this->sendResponse(['message' => 'Invalid language to install'], 400);
        }

        $downloadUrl = $downloadUrl;
        $authorised  = $acl->isAdmin() || $acl->canManage();

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
            $this->sendResponse(['message' => 'Failed to install the language'], 500);
        }

        $this->sendResponse(true, 200);
    }
}
