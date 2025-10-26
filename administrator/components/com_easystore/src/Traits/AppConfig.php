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

use Exception;
use JConfig;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Extension\ExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Path;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper as UtilitiesArrayHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Model\SettingsModel;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;
use JoomShaper\Component\EasyStore\Site\Helper\ArrayHelper;
use JoomShaper\Component\EasyStore\Site\Lib\Email;
use JoomShaper\Component\EasyStore\Site\Traits\Checkout;
use Throwable;

trait AppConfig
{
    use Checkout;
    use Extensions;
    use Plugins;

    protected $hasDuplicateShipping = false;

    public function appConfig()
    {
        $requestMethod = $this->getInputMethod();

        $this->checkNotAllowedMethods(['POST', 'PUT', 'DELETE', 'PATCH'], $requestMethod);

        if ($requestMethod === 'GET') {
            $this->getAppConfig();
        }
    }

    /**
     * API for getting the app-config data.
     *
     * @return void
     */
    protected function getAppConfig()
    {
        $params   = ComponentHelper::getParams('com_easystore');
        $settings = SettingsHelper::getSettings();

        $currency      = $settings->get('general.currency', EasyStoreHelper::getDefaultCurrency());
        $currencyChunk = explode(':', $currency, 2);

        $shipping = (array) $settings->get('shipping', []);
        $shipping = array_values($shipping);

        $shopPages = $this->getShopPages();

        $settingsModel = new SettingsModel();
        $settingsData  = $settingsModel->getSettings();
        $settingsArray = [];

        foreach ($settingsData as $data) {
            $settingsArray[$data->key] = json_decode($data->value, true);
        }

        $settingsArray['has_products'] = $this->hasProduct() > 0;

        if (!empty($settingsArray['shipping'])) {
            foreach ($shipping as &$entry) {
                if (!empty($entry->carriers)) {
                    foreach ($entry->carriers as &$carrier) {
                        $carrier->edit_url   = Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $carrier->id, false);
                        $carrier->plugin_id  = $carrier->id;
                        $carrier->has_update = $this->getPluginUpdateStatus($carrier->name);
                        $carrier->link       = $this->getPluginUpdateLink($carrier->name);

                        PluginHelper::importPlugin('easystoreshipping', $carrier->name);
                        $plugin       = ExtensionHelper::getExtensionRecord($carrier->name, 'plugin', 0, 'easystoreshipping');
                        $pluginParams = !empty($plugin) ? json_decode($plugin->params) : [];

                        $carrier->is_configured = !empty($pluginParams) ? true : false;
                        $carrier->enabled       = $carrier->is_configured ? ($carrier->enabled ?? false) : false;
                        $carrier->title         = isset($pluginParams->title) ? $pluginParams->title : $carrier->title;

                    }
                    unset($carrier);
                }
            }
            unset($entry);

            $settingsArray['shipping'] = $shipping;

            try {
                $shippingData = (object) [
                    'property' => 'shipping',
                    'data'     => json_encode($settingsArray['shipping']),
                ];
                $this->saveSettings($shippingData);
            } catch (Exception $error) {
                $this->sendResponse(['message' => $error->getMessage()], 500);
            }
        }

        if (!empty($settingsArray['payment']['list'])) {
            foreach ($settingsArray['payment']['list'] as &$item) {
                $item['edit_url']   = (Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $item['id'], false));
                $item['plugin_id']  = $item['id'];
                $item['has_update'] = $this->getPluginUpdateStatus($item['name']);
                $item['link']       = $this->getPluginUpdateLink($item['name']);

                PluginHelper::importPlugin('easystore', $item['name']);
                $event = AbstractEvent::create(
                    'onBeforePayment',
                    [
                        'subject' => new \stdClass(),
                    ]
                );

                $plugin       = ExtensionHelper::getExtensionRecord($item['name'], 'plugin', 0, 'easystore');
                $pluginParams = !empty($plugin) ? json_decode($plugin->params) : [];
                $eventResult  = Factory::getApplication()->getDispatcher()->dispatch($event->getName(), $event);

                $item['is_configured']          = $item['name'] === 'cod' ? true : $eventResult->getArgument('result');
                $item['enabled']                = !$item['is_configured'] ? false : ($item['enabled'] ?? false);
                $item['instruction']            = isset($pluginParams->payment_instruction) ? $pluginParams->payment_instruction : $item['instruction'];
                $item['title']                  = isset($pluginParams->title) ? $pluginParams->title : $item['title'];
                $item['additional_information'] = isset($pluginParams->additional_information) ? $pluginParams->additional_information : "";
            }

            unset($item);

            try {
                $paymentData = (object) [
                    'property' => 'payment',
                    'data'     => json_encode($settingsArray['payment']),
                ];
                $this->saveSettings($paymentData);
            } catch (Exception $error) {
                $this->sendResponse(['message' => $error->getMessage()], 500);
            }
        } else {
            $settingsArray['payment'] = [
                'list' => [],
            ];
        }

        if (empty($settingsArray['general']['storeEmail'])) {
            /** @var CMSApplication */
            $app                                    = Factory::getApplication();
            $config                                 = $app->getConfig();
            $email                                  = $config->get('mailfrom');
            $settingsArray['general']['storeEmail'] = $email;
        }

        if (empty($settingsArray['migration_status'])) {
            $settingsArray['migration_status'] = [];
        }

        $data = [
            'baseUrl'                => Uri::root(),
            'currency'               => [
                'symbol' => $currencyChunk[1],
                'name'   => $currencyChunk[0],
            ],
            'acceptedImageTypes'     => $this->getAcceptedImageTypes(),
            'disableAnimation'       => false,
            'settings'               => $settingsArray,
            'shipping'               => array_values($shipping),
            'shipping_carriers'      => $this->getShippingCarriers(),
            'payment_methods'        => $this->getPaymentMethodList(),
            'shop_pages'             => $shopPages,
            'email'                  => $this->getEmailConfigs(),
        ];

        $data['settings']['currency'] = $currencyChunk[1];
        $data['settings']['unit']     = $params->get('product_standard_weight', 'kg');
        $data['permissions']          = $this->getPermissions();

        $this->sendResponse($data);
    }

    private function getPermissions()
    {
        $acl = AccessControl::create();

        return [
            'is_admin'           => $acl->isAdmin(),
            'can_manage'         => $acl->canManage(),
            'can_create'         => $acl->canCreate(),
            'can_edit'           => $acl->canEdit(),
            'can_delete'         => $acl->canDelete(),
            'can_manage_options' => $acl->canManageOptions(),
            'can_import'         => $acl->canImport(),
            'can_export'         => $acl->canExport(),
            'can_edit_state'     => $acl->canEditState(),
        ];
    }

    protected function getEmailConfigs()
    {
        /** @var CMSApplication */
        $app    = Factory::getApplication();
        $config = $app->getConfig();

        $fromName  = $config->get('fromname', 'no-reply', 'STRING');
        $fromEmail = $config->get('mailfrom', '', 'EMAIL');

        $settings = SettingsHelper::getSettings();
        $groups   = $settings->get('email_templates', null);

        if (!is_null($groups)) {
            foreach ($groups as &$group) {
                $group->name = Text::_($group->name);

                if (!empty($group->templates)) {
                    foreach ($group->templates as &$template) {
                        $template->title    = Text::_($template->title);
                        $template->subtitle = Text::_($template->subtitle);

                        if (!empty($template->variables)) {
                            foreach ($template->variables as &$variable) {
                                $variable->title = Text::_($variable->title);
                            }

                            unset($variable);
                        }
                    }

                    unset($template);
                }
            }

            unset($group);
        }

        return [
            'sender_name'     => $fromName,
            'sender_email'    => $fromEmail,
            'template_groups' => $groups,
        ];
    }

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
     * Get the accepted image extensions.
     *
     * @return array
     */
    protected function getAcceptedImageTypes()
    {
        $mediaParams     = ComponentHelper::getParams('com_media');
        $imageExtensions = $mediaParams->get('image_extensions', '');
        $videoExtensions = $mediaParams->get('video_extensions', '');

        $imageExtensions = array_map(function ($extension) {
            return '.' . $extension;
        }, explode(',', $imageExtensions));

        $videoExtensions = array_map(function ($extension) {
            return '.' . $extension;
        }, explode(',', $videoExtensions));

        return array_merge($imageExtensions, $videoExtensions);
    }

    protected function getShopPages()
    {
        $componentId = ComponentHelper::getComponent('com_easystore')->id ?? null;

        if (empty($componentId)) {
            return [];
        }

        /** @var CMSApplication */
        $app       = Factory::getApplication();
        $menuItems = $app->getMenu('site')->getMenu();

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('mt.menutype, mt.title, m.id, m.title as label, m.link as value, m.component_id')
            ->from($db->quoteName('#__menu_types', 'mt'))
            ->join('LEFT', $db->quoteName('#__menu', 'm') . ' ON (mt.menutype = m.menutype)')
            ->where($db->quoteName('mt.client_id') . ' = 0')
            ->where($db->quoteName('m.title') . ' IS NOT NULL')
            ->where($db->quoteName('m.link') . ' IS NOT NULL');

        $db->setQuery($query);

        $menuItems = $db->loadObjectList();

        if (empty($menuItems)) {
            return [];
        }

        $pages = array_reduce($menuItems, function ($acc, $curr) use ($componentId) {
            if ($curr->component_id === $componentId) {
                if (empty($acc['easystore'])) {
                    $acc['easystore'] = [
                        'name'    => 'Easy Store',
                        'options' => [],
                    ];
                }

                if (!empty($curr->id)) {
                    $curr->value = $curr->value . '&Itemid=' . $curr->id;
                }

                $acc['easystore']['options'][] = ['label' => $curr->label, 'value' => $curr->value];

                return $acc;
            }

            if (empty($acc[$curr->menutype])) {
                $acc[$curr->menutype] = [
                    'name'    => $curr->title,
                    'options' => [],
                ];
            }

            $acc[$curr->menutype]['options'][] = ['label' => $curr->label, 'value' => $curr->value];

            return $acc;
        }, []);

        $easystore = [
            'name'    => 'Easy Store',
            'options' => [],
        ];

        if (!empty($pages['easystore'])) {
            $easystore = $pages['easystore'];
            unset($pages['easystore']);
        }

        return array_merge([$easystore], array_values($pages));
    }

    public function saveEmailTemplate()
    {
        $requestMethod = $this->getInputMethod();
        $this->checkNotAllowedMethods(['GET', 'PUT', 'DELETE', 'PATCH'], $requestMethod);

        $group   = $this->getInput('group', '', 'STRING');
        $type    = $this->getInput('type', '', 'STRING');
        $subject = $this->getInput('subject', '', 'STRING');
        $body    = $this->getInput('body', '', 'RAW');

        $settings  = SettingsHelper::getSettings();
        $templates = $settings->get('email_templates');

        if (!isset($templates->$group)) {
            $this->sendResponse(['message' => Text::_('COM_EASYSTORE_EMAIL_TEMPLATE_INVALID_DATA')], 400);
        }

        $group    = $templates->$group;
        $template = ArrayHelper::find(function ($item) use ($type) {
            return $item->type === $type;
        }, $group->templates);

        if (is_null($template)) {
            $this->sendResponse(['message' => Text::_('COM_EASYSTORE_EMAIL_TEMPLATE_INVALID_DATA')], 400);
        }

        $template->subject = $subject;
        $template->body    = $body;

        try {
            $data = (object) [
                'property' => 'email_templates',
                'data'     => json_encode($templates),
            ];
            $this->saveSettings($data);
        } catch (Exception $error) {
            $this->sendResponse(['message' => $error->getMessage()], 500);
        }

        $this->sendResponse(true);
    }

    protected function saveSettings($data)
    {
        try {
            $model = new SettingsModel();
            $model->createOrUpdate($data);
        } catch (Throwable $error) {
            throw $error;
        }
    }

    public function sendTestEmail()
    {
        $requestMethod = $this->getInputMethod();
        $this->checkNotAllowedMethods(['GET', 'PUT', 'DELETE', 'PATCH'], $requestMethod);

        $receiver = $this->getInput('receiver', '', 'EMAIL');
        $subject  = $this->getInput('subject', '', 'STRING');
        $body     = $this->getInput('body', '', 'RAW');

        $email = new Email($receiver);
        $email->setSubject($subject)->setBody($body);

        try {
            $email->send();
        } catch (Exception $error) {
            $this->sendResponse(['message' => $error->getMessage()], 500);
        }

        $this->sendResponse(true);
    }

    public function updateEmailTemplateStatus()
    {
        $requestMethod = $this->getInputMethod();
        $this->checkNotAllowedMethods(['GET', 'PUT', 'DELETE', 'POST'], $requestMethod);

        $group = $this->getInput('group', '', 'STRING');
        $type  = $this->getInput('type', '', 'STRING');
        $value = $this->getInput('value', 0, 'CMD');

        $settings  = SettingsHelper::getSettings();
        $templates = $settings->get('email_templates');

        if (!isset($templates->$group)) {
            $this->sendResponse(['message' => Text::_('COM_EASYSTORE_EMAIL_TEMPLATE_INVALID_DATA')], 400);
        }

        $group    = $templates->$group;
        $template = ArrayHelper::find(function ($item) use ($type) {
            return $item->type === $type;
        }, $group->templates);

        if (is_null($template)) {
            $this->sendResponse(['message' => Text::_('COM_EASYSTORE_EMAIL_TEMPLATE_INVALID_DATA')], 400);
        }

        $template->is_enabled = (bool) $value;

        try {
            $data = (object) [
                'property' => 'email_templates',
                'data'     => json_encode($templates),
            ];
            $this->saveSettings($data);
        } catch (Exception $error) {
            $this->sendResponse(['message' => $error->getMessage()], 500);
        }

        $this->sendResponse(true);
    }

    public function updateConfiguration()
    {
        $requestMethod = $this->getInputMethod();
        $this->checkNotAllowedMethods(['GET', 'PUT', 'DELETE', 'POST'], $requestMethod);

        $key    = $this->getInput('key', '', 'STRING');
        $value  = $this->getInput('value', '', $key === 'name' ? 'STRING' : 'EMAIL');
        $config = UtilitiesArrayHelper::fromObject(new JConfig());

        $configMap = [
            'name'  => 'fromname',
            'email' => 'mailfrom',
        ];

        if (empty($key) || empty($value)) {
            $this->sendResponse(['message' => Text::_('COM_EASYSTORE_EMAIL_TEMPLATE_INVALID_DATA')], 400);
        }

        if ($config[$configMap[$key]] === $value) {
            $this->sendResponse(true);
        }

        $config[$configMap[$key]] = $value;

        try {
            $this->writeConfigFile(new Registry($config));
        } catch (Exception $error) {
            $this->sendResponse(['message' => $error->getMessage()], 500);
        }

        $this->sendResponse(true);
    }

    /**
     * Method to write the configuration to a file.
     *
     * @param   Registry  $config  A Registry object containing all global config data.
     *
     * @return  int  The task exit code
     *
     * @since  4.1.0
     * @throws \Exception
     */
    private function writeConfigFile(Registry $config)
    {
        // Set the configuration file path.
        $file = JPATH_CONFIGURATION . '/configuration.php';

        // Attempt to make the file writeable.
        if (file_exists($file) && Path::isOwner($file) && !Path::setPermissions($file)) {
            throw new Exception(Text::_('COM_EASYSTORE_CONFIG_FILE_NOT_WRITABLE'));
        }

        try {
            // Attempt to write the configuration file as a PHP class named JConfig.
            $configuration = $config->toString('PHP', ['class' => 'JConfig', 'closingtag' => false]);
            File::write($file, $configuration);
        } catch (Throwable $error) {
            throw $error;
        }

        // Invalidates the cached configuration file
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($file);
        }

        // Attempt to make the file un-writeable.
        if (Path::isOwner($file) && !Path::setPermissions($file, '0444')) {
            throw new Exception(Text::_('COM_EASYSTORE_CONFIG_FILE_NOT_WRITABLE'));
        }

        return 0;
    }

    /**
     * Get the plugin update status
     *
     * @param string $searchedElement
     * @return mixed
     *
     * @since 1.0.9
     */
    public function getPluginUpdateStatus($searchedElement)
    {
        $installedPlugins = $this->getInstalledPluginList();

        $filteredPlugins = array_filter($installedPlugins, function ($plugins) use ($searchedElement) {
            return $plugins['element'] === $searchedElement;
        });

        return reset($filteredPlugins)['has_update'] ?? false;
    }

    /**
     * Get the plugin update link
     *
     * @param string $searchedElement
     * @return mixed
     *
     * @since 1.0.9
     */
    public function getPluginUpdateLink($searchedElement)
    {
        $installedPlugins = $this->getInstalledPluginList();

        $filteredPlugins = array_filter($installedPlugins, function ($plugins) use ($searchedElement) {
            return $plugins['element'] === $searchedElement;
        });

        return reset($filteredPlugins)['link'] ?? '';
    }
}
