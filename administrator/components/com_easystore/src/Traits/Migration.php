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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Component\ComponentHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;

trait Migration
{
    /**
     * List of allowed components to migrate from
     *
     * @return array
     */
    protected function getAllowedComponents()
    {
        return ['com_j2store'];
    }

    /**
     * hasProduct
     *
     * @return int
     */
    protected function hasProduct()
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('COUNT(id)')
            ->from($db->quoteName('#__easystore_products'))
            ->where($db->quoteName('published') . ' = 1');
        $db->setQuery($query);

        return $db->loadResult() ?? 0;
    }

    /**
     * Formatted data for settings popup
     *
     * @return array
     */
    private function getEasyStoreSettingsForPopup()
    {
        $settings               = SettingsHelper::getSettings();
        $baseUrl                = Uri::root();
        $adminUrl               = $baseUrl . 'administrator/';
        $successIcon            = '<svg width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.997 1.664a8.333 8.333 0 1 0 0 16.667 8.333 8.333 0 0 0 0-16.667Zm4.375 6.312a.781.781 0 0 0-1.151-1.056L9 11.39l-1.65-1.657A.781.781 0 0 0 6.2 10.789l2.227 2.284a.781.781 0 0 0 1.152 0l4.794-5.097Z" fill="#079874"/></svg>';
        $isGeneralCompleted     = $this->checkRequiredFieldsOfSettingsPage(['storeName','storeEmail','storePhone','addressLineOne','city','postcode','country'], 'general');
        $isTaxCompleted         = $this->checkRequiredFieldsOfSettingsPage(['rates'], 'tax');
        $isPaymentListCompleted = $this->checkRequiredFieldsOfSettingsPage(['list'], 'payment');


        return [
            'general' => [
                'isCompleted' => $isGeneralCompleted,
                'title'       => Text::_('COM_EASYSTORE_APP_DASHBOARD_CONFIGURE_YOUR_STORE'),
                'linkText'    => Text::_('COM_EASYSTORE_SETTINGSTEPS_CONFIGURE'),
                'link'        => Route::_($adminUrl . 'index.php?option=com_easystore&view=settings', false),
                'icon'        => $isGeneralCompleted ? $successIcon : '<svg width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M17.57 10.972H2.985v6.32c0 .805.653 1.458 1.458 1.458h3.403v-4.861h4.861v4.861h3.403c.805 0 1.458-.653 1.458-1.458v-6.32Z" fill="#BABCC3"/><path d="m17.945 6.36-1.513-3.927c-.45-1.183-.45-1.183-1.488-1.183H5.611c-1.037 0-1.037 0-1.49 1.183L2.61 6.36c-.292.718.094 1.454.094 1.454l.013.025c.014.024.037.065.053.087l.009.015.17.236.172.19.013.014c.096.093.198.177.306.25v.002c.353.244.755.38 1.167.394h.08c.624.002 1.225-.266 1.682-.75.066-.07.128-.144.187-.222.059.078.121.152.187.222.457.484 1.058.752 1.682.75a2.35 2.35 0 0 0 1.867-.944l.003-.001h.002l.184.195c.457.484 1.058.752 1.682.75.755 0 1.43-.369 1.868-.946a2.35 2.35 0 0 0 1.87.947h.08a2.115 2.115 0 0 0 1.134-.396c.031-.02.06-.043.09-.065.231-.177.431-.4.592-.659l.065-.11c.03-.076.363-.75.084-1.438Z" fill="#BABCC3"/></svg>',
            ],
            'products' => [
                'isCompleted' => (bool) $settings->get('products'),
                'title'       => Text::_('COM_EASYSTORE_APP_DASHBOARD_CONFIGURE_YOUR_PRODUCT_PAGE'),
                'link'        => Route::_($adminUrl . 'index.php?option=com_easystore&view=settings#/products', false),
                'linkText'    => Text::_('COM_EASYSTORE_APP_DASHBOARD_PERSONALIZE'),
                'icon'        => (bool) $settings->get('products') ? $successIcon : '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M17.5 5c0 .46-.373.833-.833.833H9.024a2.5 2.5 0 0 1-4.714 0h-.977a.833.833 0 0 1 0-1.666h.977a2.5 2.5 0 0 1 4.714 0h7.643c.46 0 .833.373.833.833Zm0 5c0 .46-.373.833-.833.833h-.977a2.5 2.5 0 0 1-4.714 0H3.333a.833.833 0 0 1 0-1.666h7.643a2.499 2.499 0 0 1 4.714 0h.977c.46 0 .833.373.833.833Zm0 5c0 .46-.373.833-.833.833H9.024a2.5 2.5 0 0 1-4.714 0h-.977a.833.833 0 1 1 0-1.666h.977a2.5 2.5 0 0 1 4.714 0h7.643c.46 0 .833.373.833.833Zm-3.333-5a.833.833 0 1 0-1.667 0 .833.833 0 0 0 1.667 0ZM7.5 5a.833.833 0 1 0-1.667 0A.833.833 0 0 0 7.5 5Zm0 10a.833.833 0 1 0-1.667 0A.833.833 0 0 0 7.5 15Z" fill="#BABCC3"/></svg>',
            ],
            'has_products' => [
                'isCompleted' => (bool) $this->hasProduct() > 0,
                'title'       => Text::_('COM_EASYSTORE_APP_DASHBOARD_ADD_FIRST_PRODUCT'),
                'link'        => Route::_($adminUrl . 'index.php?option=com_easystore&view=product&layout=edit', false),
                'linkText'    => Text::_('COM_EASYSTORE_SETTINGSTEPS_ADD_PRODUCT'),
                'icon'        => (bool) $this->hasProduct() > 0 ? $successIcon : '<svg width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="m10 7.957 3.029-1.21-7.5-3L2.943 4.78c-.13.052-.253.122-.364.208L10 7.958v-.001ZM2.035 5.85A1.5 1.5 0 0 0 2 6.173v7.646a1.5 1.5 0 0 0 .943 1.393L8.7 17.515c.26.104.528.175.8.214V8.835L2.035 5.849ZM10.5 17.73c.274-.04.543-.111.8-.214l5.757-2.303A1.5 1.5 0 0 0 18 13.819V6.173c0-.11-.012-.22-.035-.324L10.5 8.835v8.894Zm6.921-12.74-3.046 1.219-7.5-3L8.7 2.477a3.5 3.5 0 0 1 2.6 0l5.757 2.303a1.5 1.5 0 0 1 .364.208v.002Z" fill="#BABCC3"/></svg>',
            ],
            'payment' => [
                'isCompleted' => $isPaymentListCompleted,
                'title'       => Text::_('COM_EASYSTORE_APP_DASHBOARD_SETUP_PAYMENT_METHODS'),
                'link'        => Route::_($adminUrl . 'index.php?option=com_easystore&view=settings#/payments', false),
                'linkText'    => Text::_('COM_EASYSTORE_SETTINGSTEPS_SETUP_PAYMENT'),
                'icon'        => $isPaymentListCompleted ? $successIcon : '<svg width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 4a2 2 0 0 0-2 2v1h16V6a2 2 0 0 0-2-2H4Z" fill="#BABCC3"/><path fill-rule="evenodd" clip-rule="evenodd" d="M18 9H2v5a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9ZM4 13a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2H5a1 1 0 0 1-1-1Zm5-1a1 1 0 0 0 0 2h1a1 1 0 0 0 0-2H9Z" fill="#BABCC3"/></svg>',
            ],
            'shipping' => [
                'isCompleted' => (bool) $settings->get('shipping'),
                'title'       => Text::_('COM_EASYSTORE_APP_DASHBOARD_SETUP_SHIPPING_METHODS'),
                'link'        => Route::_($adminUrl . 'index.php?option=com_easystore&view=settings#/shipping', false),
                'linkText'    => Text::_('COM_EASYSTORE_SETTINGSTEPS_SETUP_SHIPPING'),
                'icon'        => (bool) $settings->get('shipping') ? $successIcon : '<svg width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5.568 16.193a2.137 2.137 0 0 1-1.57-.646 2.137 2.137 0 0 1-.646-1.57c-.406 0-.754-.144-1.043-.433a1.423 1.423 0 0 1-.434-1.044V5.852c0-.406.145-.754.434-1.043a1.42 1.42 0 0 1 1.043-.434h8.864c.406 0 .754.145 1.044.434.289.29.433.637.433 1.043V7.33h1.847c.123 0 .234.024.332.073.099.05.185.124.259.222l1.846 2.456c.05.062.086.13.111.203a.755.755 0 0 1 .037.24v2.715c0 .209-.07.384-.213.525a.713.713 0 0 1-.526.213h-.738c0 .616-.216 1.139-.647 1.57-.43.43-.954.646-1.57.646a2.137 2.137 0 0 1-1.569-.646 2.137 2.137 0 0 1-.646-1.57H7.784c0 .616-.215 1.139-.646 1.57-.431.43-.954.646-1.57.646Zm0-1.477c.21 0 .385-.071.527-.213a.714.714 0 0 0 .212-.526c0-.209-.07-.384-.212-.526a.715.715 0 0 0-.527-.212c-.21 0-.385.07-.526.212a.714.714 0 0 0-.212.526c0 .21.07.385.212.526a.715.715 0 0 0 .526.213Zm8.864 0c.21 0 .384-.071.526-.213a.713.713 0 0 0 .213-.526.713.713 0 0 0-.213-.526.712.712 0 0 0-.526-.212c-.21 0-.385.07-.526.212a.713.713 0 0 0-.213.526c0 .21.071.385.213.526a.713.713 0 0 0 .526.213Zm-.739-3.693h3.14L15.17 8.807h-1.478v2.216Z" fill="#BABCC3"/></svg>',
            ],
            'tax' => [
                'isCompleted' => $isTaxCompleted,
                'title'       => Text::_('COM_EASYSTORE_APP_DASHBOARD_MANAGE_TAX_RATES'),
                'link'        => Route::_($adminUrl . 'index.php?option=com_easystore&view=settings#/tax', false),
                'linkText'    => Text::_('COM_EASYSTORE_SETTINGSTEPS_MANAGE_TAX'),
                'icon'        => $isTaxCompleted ? $successIcon : '<svg width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5 2a2 2 0 0 0-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 0 0-2-2H5Zm2.5 3a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Zm6.207.293a1 1 0 0 0-1.414 0l-6 6a1 1 0 1 0 1.414 1.414l6-6a1 1 0 0 0 0-1.414ZM12.5 10a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" fill="#BABCC3"/></svg>',
            ],
        ];
    }

    /**
     * Migration Popup layout
     *
     * @return void
     */
    public function popup()
    {
        $app       = Factory::getApplication();
        $input     = $app->input;
        $popupType = $input->get('type', '');

        if ($popupType == 'easystore_settingsteps') {
            $easystore_settings = $this->getEasyStoreSettingsForPopup();
            echo LayoutHelper::render('popup.settingsteps', $easystore_settings);
            exit;
        }

        echo LayoutHelper::render('popup.content');
        exit;
    }

    /**
     * Function to check settings if already migrated
     *
     * @param string $componentName Component Name
     *
     * @return object
     */
    public function checkMigrationStatus(string $componentName)
    {
        $migrationSettings = SettingsHelper::getSettings()->get('migration_status', '');
        $response          = (object) [
            'status' => false,
        ];

        if (!empty($migrationSettings)) {
            $component = str_replace('com_', '', $componentName);

            foreach ($migrationSettings as $setting) {
                if ($setting->migration === $component && $setting->status === "complete") {
                    $response = (object) [
                        'status'  => true,
                        'message' => 'Already migrated to EasyStore',
                    ];

                    break;
                }
            }
        }

        return $response;
    }

    /**
     * Check is migration is allowed
     *
     * @return object
     */
    public function allowMigration()
    {
        $self          = new self();
        $componentName = $self->setMigrateFrom();

        if (empty($componentName)) {
            $response = (object) [
                'status'  => false,
                'message' => Text::_('COM_EASYSTORE_MIGRATION_FROM_EMPTY'),
            ];

            $this->sendResponse($response);
        }

        $componentStatus =  EasyStoreHelper::isComponentInstalled($componentName);

        if ($componentStatus->status === false) {
            $response = (object) [
                'status'  => false,
                'message' => $componentStatus->message,
            ];

            $this->sendResponse($response);
        }

        $migrationSettings = SettingsHelper::getSettings()->get('migration_status', '');
        $component         = str_replace('com_', '', $componentName);

        $response          = (object) [
            'status'      => true,
            'message'     => Text::_('COM_EASYSTORE_MIGRATION_AUTHORIZED'),
            'migrateFrom' => $component,
        ];

        if (!empty($migrationSettings)) {
            foreach ($migrationSettings as $setting) {
                if ($setting->migration === $component && $setting->status === "complete") {
                    $response = (object) [
                        'status'  => false,
                        'message' => Text::_('COM_EASYSTORE_MIGRATION_ALREADY_MIGRATED'),
                    ];

                    break;
                }
            }
        }

        $this->sendResponse($response);
    }


    /**
     * Function to check component status
     *
     * @param string $componentName
     * @return bool
     */
    public function componentStatusCheck($componentName)
    {
        $componentStatus =  EasyStoreHelper::isComponentInstalled($componentName);

        if ($componentStatus->status === false) {
            return false;
        }

        $migrationSettings = SettingsHelper::getSettings()->get('migration_status', '');
        $component         = str_replace('com_', '', $componentName);

        if (!empty($migrationSettings)) {
            foreach ($migrationSettings as $setting) {
                if ($setting->migration === $component && $setting->status === "complete") {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Deactivate Component
     *
     * @return mixed
     */
    public function deactivate()
    {
        $input   = Factory::getApplication()->input;
        $element = $input->get('element');

        if (ComponentHelper::isInstalled($element) && ComponentHelper::isEnabled($element)) {
            try {
                $db = Factory::getContainer()->get(DatabaseInterface::class);

                $query = $db->getQuery(true);
                $query->update($db->quoteName('#__extensions'));
                $query->set($db->quoteName('enabled') . ' = 0');
                $query->where($db->quoteName('element') . ' = ' . $db->quote($element));

                $db->setQuery($query);

                $db->execute();

                $response = (object) [
                    'status'  => true,
                    'message' => 'Successfully deactivated',
                ];

                die(json_encode($response));
            } catch (\Exception $e) {
                $response = (object) [
                    'status'  => false,
                    'message' => $e->getMessage(),
                ];

                die(json_encode($response));
            }
        }

        $response = (object) [
            'status'  => false,
            'message' => 'Component Not Found',
        ];

        die(json_encode($response));
    }

    /**
     * Function to get the component name from which to migrate
     *
     * @return string
     */
    private function setMigrateFrom()
    {
        $installedComponents      = ComponentHelper::getComponents();
        $migrationValidComponents = $this->getAllowedComponents();
        $migrateFrom              = '';

        foreach ($installedComponents as $key => $value) {
            if (in_array($key, $migrationValidComponents) && $value->enabled == 1) {
                $migrateFrom = $key;
                break;
            }
        }

        return $migrateFrom;
    }

    /**
     * Check if all required fields of a settings page are filled up.
     *
     * @param  array  $requiredFields An array containing the names of the required fields.
     * @param  string $type           The type of settings page (e.g., 'general', 'tax').
     * @return bool                   Returns true if all required fields are filled up, otherwise false.
     * @since  1.1.0
     */
    private function checkRequiredFieldsOfSettingsPage($requiredFields, $type)
    {
        $settings = SettingsHelper::getSettings();

        foreach ($requiredFields as $field) {
            $check = $settings->get($type . '.' . $field);

            if (empty($check)) {
                return false;
            }
        }

        return true;
    }
}
