<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Helper;

use Throwable;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Http\Http;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Site\Helper\OrderHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Get settings data
 *
 * @since 1.0.0
 */
final class SettingsHelper
{
    /**
     * Cached settings
     *
     * @var Registry|null
     */
    private static $cachedSettings = null;

    /**
     * Get settings value by using key and value
     *
     * $settings = SettingsHelper::getSettings();
     *
     * $currency = $settings->get('general.currency', 'USD:$');
     *
     * @return Registry
     *
     * @since 1.0.0 Initial version
     * @since 1.4.7 Add cache settings
     */
    public static function getSettings()
    {
        // Check if settings are already cached
        if (self::$cachedSettings !== null) {
            return self::$cachedSettings;
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select($db->quoteName(['key', 'value']))->from($db->quoteName('#__easystore_settings'));
        $db->setQuery($query);

        try {
            $settings = $db->loadObjectList('key') ?? [];

            foreach ($settings as &$setting) {
                $setting = json_decode($setting->value ?? '') ?? null;
            }

            unset($setting);

            $settings = (object) $settings;

            // Cache the settings
            self::$cachedSettings = new Registry($settings);

            return self::$cachedSettings;
        } catch (Throwable $error) {
            // Cache the empty settings in case of error
            self::$cachedSettings = new Registry([]);

            return self::$cachedSettings;
        }
    }

    /**
     * Function to set Settings data by key value pair
     *
     * @param string $key   The key of the settings to update ie 'general.storeName'
     * @param mixed $value  Value of the key
     * @return object
     */
    public static function setSettings(string $key, mixed $value)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select($db->quoteName(['key', 'value']))->from($db->quoteName('#__easystore_settings'));
        $db->setQuery($query);

        try {
            $response = new \stdClass();
            $settings = $db->loadObjectList('key') ?? [];

            foreach ($settings as &$setting) {
                $setting = json_decode($setting->value ?? '') ?? null;
            }

            unset($setting);

            $settings = (object) $settings;
            $keys     = explode('.', $key);
            $temp     = &$settings;

            try {
                foreach ($keys as $key) {
                    if (!isset($temp->$key)) {
                        $temp->$key = new \stdClass();
                    }
                    $temp = &$temp->$key;
                }

                $temp = $value;
            } catch (\Exception $error) {
                $response->status  = false;
                $response->message = $error->getMessage();

                return $response;
            }

            // Update the settings data
            foreach ($settings as $key => $value) {
                if ($keys[0] == $key) {
                    $settingsData        = new \stdClass();
                    $settingsData->key   = $key;
                    $settingsData->value = json_encode($value ?? '');

                    EasyStoreDatabaseOrm::updateOrCreate('#__easystore_settings', $settingsData, 'key');
                }
            }

            $response->status  = true;
            $response->message = 'settings updated';

            return $response;
        } catch (\Exception $error) {
            $response->status  = false;
            $response->message = $error->getMessage();

            return $response;
        }
    }

    /**
     * Get the store address details.
     *
     * @return array
     *
     * @since 1.0.2
     * @since 1.2.0 add email key
     */
    public static function getAddress()
    {
        $settings = self::getSettings();

        $countryId = $settings->get('general.country', '');
        $stateId   = $settings->get('general.state', '');

        $CountryCityNames = EasyStoreHelper::getCountryStateFromJson($countryId, $stateId);

        return [
            'address_1' => $settings->get('general.addressLineOne', ''),
            'address_2' => $settings->get('general.addressLineTwo', ''),
            'city'      => $settings->get('general.city', ''),
            'state'     => $CountryCityNames->state,
            'zip_code'  => $settings->get('general.postcode', ''),
            'country'   => $CountryCityNames->country,
            'name'      => $settings->get('general.storeName', ''),
            'email'     => $settings->get('general.storeEmail', ''),
            'phone'     => $settings->get('general.storePhone', ''),
        ];
    }

    /**
     * Get store tax id
     *
     * @return string
     */
    public static function getSellerTaxId()
    {
        $settings = self::getSettings();
        $countryCode = $settings->get('general.country', '');
        $taxId = OrderHelper::getSellerTaxID($countryCode ?? '');

        return $taxId ? Text::sprintf('COM_EASYSTORE_SELLER_TAX_ID', $taxId) : '';
    }

    /**
     * Get Payment plugin list
     *
     * @return mixed
     */
    public static function getPluginSchema($plugin = 'payments')
    {
        $cachePath = JPATH_CACHE . '/easystore';
        $cacheFile = $cachePath . '/' . $plugin . '.json';

        $url     = 'https://www.joomshaper.com/products/easystore/' . $plugin . '.json';
        $content = '';

        // Ensure the cache directory exists
        if (!file_exists($cachePath)) {
            Folder::create($cachePath, 0755);
        }

        // Check if cached file exists and is still valid (within 24 hours)
        if (file_exists($cacheFile) && (filemtime($cacheFile) > (time() - (24 * 60 * 60)))) {
            $content = self::readFile($cacheFile);
        } else {
            // Fetch from URL using Http
            $content = self::fetchContent($url);

            // If content was fetched successfully, cache it
            if (!empty($content)) {
                File::write($cacheFile, $content);
            }
        }

        // Return an empty array if no content was fetched
        if (empty($content)) {
            return [];
        }

        return json_decode($content);
    }

    /**
     * Fetches the content from a given URL using Http if available.
     *
     * @param string $url The URL to fetch the content from.
     * @return string The content fetched from the URL, or an empty string if fetching fails.
     */
    public static function fetchContent($url)
    {
        $content = '';

        $request  = new Http();
        $response = $request->get($url);

        if ($response->code == 200) {
            $content = $response->body;
        }

        return $content;
    }

    /**
     * Reads the content of a file from the local filesystem.
     *
     * @param string $file The path to the file to read.
     * @return string The file content, or an empty string if reading fails.
     */
    private static function readFile($file)
    {
        $content = '';

         // Use fopen/fread
        $handle = @fopen($file, "rb");

        if ($handle) {
            // On Windows system we can not use file_get_contents on the file locked by yourself
            $content = stream_get_contents($handle);
            @fclose($handle);
        }

        return $content;
    }


    /**
     * Function to get weight unit with weight value
     *
     * @param mixed $weight  
     * @param mixed $unit
     * @return string
     *
     * @since 1.2.0
     * @since 1.5.0 Add unit parameter
     */
    public static function getWeightWithUnit($weight, $unit = '')
    {
        $settings      = self::getSettings();
        $standardUnits = $settings->get('products.standardUnits.weight', 'kg');
        // Check if the unit is empty
        if (!empty($unit)) {
            $standardUnits = $unit;
        }


        $unitLanguageString = [
            'g'  => 'COM_EASYSTORE_UNIT_VALUE_FOR_GRAM',
            'kg' => 'COM_EASYSTORE_UNIT_VALUE_FOR_KILOGRAM',
            'lb' => 'COM_EASYSTORE_UNIT_VALUE_FOR_POUND',
            'oz' => 'COM_EASYSTORE_UNIT_VALUE_FOR_OUNCE',
        ];

        $weightUnit = Text::_($unitLanguageString[$standardUnits]);

        return $weight . $weightUnit;
    }

    /**
     * Checks if a specific email template type is enabled.
     *
     * This function retrieves the settings and checks if the specified email template
     * type (e.g., "order_confirmation") is enabled.
     *
     * @param string $templateType The type of email template to check (e.g., "order_confirmation").
     * @return bool Returns true if the email template type is enabled, false otherwise.
     *
     * @since 1.2.0
     */
    public static function isEmailTemplateEnabled($templateType)
    {
        $settings       = self::getSettings();
        $emailTemplates = $settings->get('email_templates', '');
        $isEmailEnabled = false;

        $key = explode('_', $templateType)[0];

        // Check if email templates are enabled
        if (!empty($emailTemplates) && is_array($emailTemplates->$key->templates)) {
            foreach ($emailTemplates->$key->templates as $template) {
                if (!empty($template->type) && $template->type === $templateType) {
                    $isEmailEnabled = $template->is_enabled;
                    break; // Exit loop once the relevant template is found
                }
            }
        }

        return $isEmailEnabled;
    }

    /**
     * Checks if guest checkout is enabled.
     *
     * This function retrieves the application settings and user identity to determine
     * if guest checkout is allowed. It returns true if the current user is a guest
     * and guest checkout is enabled in the settings, false otherwise.
     *
     * @return bool Returns true if guest checkout is enabled for the current user, false otherwise.
     *
     * @since 1.2.0
     */
    public static function isGuestCheckoutEnable()
    {
        $app                = Factory::getApplication();
        $user               = $app->getIdentity();

        $settings           = self::getSettings();
        $allowGuestCheckout = $settings->get('checkout.allow_guest_checkout', false);

        return ($user->guest && $allowGuestCheckout);
    }
}
