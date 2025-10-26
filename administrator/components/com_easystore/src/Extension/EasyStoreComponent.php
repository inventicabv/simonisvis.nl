<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Http\Http;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Psr\Container\ContainerInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Component\Router\RouterServiceInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Component class for com_easystore
 *
 * @since  1.0.0
 */
class EasyStoreComponent extends MVCComponent implements
    BootableExtensionInterface,
    RouterServiceInterface
{
    use HTMLRegistryAwareTrait;
    use RouterServiceTrait;

    /**
     * Booting the extension. This is the function to set up the environment of the extension like
     * registering new class loaders, etc.
     *
     * If required, some initial set up can be done from services of the container, eg.
     * registering HTML services.
     *
     * @param   ContainerInterface  $container  The container
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function boot(ContainerInterface $container)
    {
        if (Factory::getApplication()->isClient('administrator')) {
            static::initiateLicenseValidation();
        }

        PluginHelper::importPlugin('easystore-xtd');
        
        $this->loadMediaConfig();

        $this->loadTranslationKeys();
    }

    private static function initiateLicenseValidation()
    {
        /** @var CMSApplication */
        $app    = Factory::getApplication();
        $params = ComponentHelper::getParams('com_easystore');

        $installedDate = new Date($params->get('installed_on', ''));
        $currentDate   = new Date();

        // Calculate the difference in days
        $daysSinceInstallation = $currentDate->diff($installedDate)->days;

        $lastChecked = $app->input->cookie->get('easystore_last_checked', 0);

        // Check if it's been at least five minutes since the last check
        $dayInSeconds = 24 * 60 * 60;

        if ($daysSinceInstallation >= 15 && time() - $lastChecked >= $dayInSeconds) {
            $validLicense = static::checkValidLicense();

            // Update the last checked time in the cookie
            $app->input->cookie->set('easystore_last_checked', time());

            if (!$validLicense) {
                $app->enqueueMessage('Please enter a valid license key and email address to use EasyStore.', 'warning');
                $app->redirect('index.php?option=com_config&view=component&component=com_easystore');
                return false;
            }
        }
    }

    private static function checkValidLicense()
    {
        // Check if the host is localhost
        $isLocalhost = ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1');

        // If localhost, you can return true or perform any other action you like
        if ($isLocalhost) {
            return true;  // Or any other appropriate action
        }

        $params     = ComponentHelper::getParams('com_easystore');
        $email      = $params->get('joomshaper_email');
        $licenseKey = $params->get('joomshaper_license_key');

        if (empty($email) || empty($licenseKey)) {
            return false;
        }

        $request  = new Http();
        $response = $request->get('https://www.joomshaper.com/index.php?option=com_product&task=validateLicense&joomshaper_email=' . $email . '&joomshaper_license_key=' . $licenseKey . '&product=easystore');

        if ($response->code == 200) {
            return true;
        }

        return false;
    }

    protected function loadTranslationKeys()
    {
        $path     = JPATH_ROOT . '/administrator/components/com_easystore/assets/language/translation-keys.json';
        $language = Factory::getApplication()->getLanguage();

        $language->load('com_easystore', JPATH_ADMINISTRATOR, null, true);

        if (file_exists($path)) {
            $languageKeys = file_get_contents($path);
            $languageKeys = !empty($languageKeys) && is_string($languageKeys) ? json_decode($languageKeys) : [];

            foreach ($languageKeys as $key) {
                if (Factory::getApplication()->isClient('administrator')) {
                    Text::script($key);
                }
            }
        }
    }

    /**
     * Loads the media configuration settings for the EasyStore component.
     *
     * This method retrieves the maximum upload file size from the media component
     * parameters and sets it as an inline script for use in the Joomla CMS.
     *
     * @return void
     *
     * @since 1.4.3
     */
    public function loadMediaConfig()
    {
        $params = ComponentHelper::getParams('com_media');
        $maxFileSize = $params->get('upload_maxsize', 5) * 1024 * 1024;
        $document = Factory::getDocument();
        $wa = $document->getWebAssetManager();
        $wa->addInlineScript("Joomla.easyStoreMediaMaxFileSize = " . $maxFileSize);
    }
}
