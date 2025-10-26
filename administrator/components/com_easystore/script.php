<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\Installer\Installer;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Component\ComponentHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Install script class
 *
 * @since 1.0.0
 */
class com_easystoreInstallerScript
{
    /**
     * Method to uninstall the component
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function uninstall($parent)
    {
        $extensions = [
            ['type' => 'module', 'name' => 'mod_easystore_cart_icon'],
            ['type' => 'module', 'name' => 'mod_easystore_search'],
            ['type' => 'plugin', 'name' => 'paypal', 'group' => 'easystore'],
            ['type' => 'plugin', 'name' => 'cod', 'group' => 'easystore'],
            ['type' => 'plugin', 'name' => 'easystoreupdater', 'group' => 'system'],
            ['type' => 'plugin', 'name' => 'easystoremail', 'group' => 'system'],
            ['type' => 'plugin', 'name' => 'easystorecore', 'group' => 'system'],
            ['type' => 'plugin', 'name' => 'easystoreprofile', 'group' => 'user'],
            ['type' => 'plugin', 'name' => 'easystoretoj2storemigration', 'group' => 'system'],
            ['type' => 'plugin', 'name' => 'easystore', 'group' => 'finder'],
            ['type' => 'plugin', 'name' => 'easystorequickicon', 'group' => 'quickicon'],
            ['type' => 'plugin', 'name' => 'easystoreloadmodule', 'group' => 'content'],
        ];

        foreach ($extensions as $key => $extension) {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);
            $query->select($db->quoteName(['extension_id']));
            $query->from($db->quoteName('#__extensions'));
            $query->where($db->quoteName('type') . ' = ' . $db->quote($extension['type']));
            $query->where($db->quoteName('element') . ' = ' . $db->quote($extension['name']));
            $db->setQuery($query);
            $id = $db->loadResult();

            if (isset($id) && $id) {
                try {
                    $installer = new Installer();
                    $installer->setDatabase(Factory::getContainer()->get(DatabaseInterface::class));
                    $installer->uninstall($extension['type'], $id);
                } catch (\Exception $e) {
                    return Factory::getApplication()->enqueueMessage(
                        sprintf('Error installing %s: %s', $extension['name'], $e->getMessage()),
                        'error'
                    );
                }

            }
        }
    }

    /**
     * Method to run after an install/update/uninstall method
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function postflight($type, $parent)
    {
        if ($type === 'uninstall') {
            return;
        }

        $extensions = [
            ['type' => 'module', 'name' => 'mod_easystore_cart_icon'],
            ['type' => 'module', 'name' => 'mod_easystore_search'],
            ['type' => 'plugin', 'name' => 'paypal', 'group' => 'easystore'],
            ['type' => 'plugin', 'name' => 'cod', 'group' => 'easystore'],
            ['type' => 'plugin', 'name' => 'easystoreupdater', 'group' => 'system'],
            ['type' => 'plugin', 'name' => 'easystoremail', 'group' => 'system'],
            ['type' => 'plugin', 'name' => 'easystorecore', 'group' => 'system'],
            ['type' => 'plugin', 'name' => 'easystoreprofile', 'group' => 'user'],
            ['type' => 'plugin', 'name' => 'easystoretoj2storemigration', 'group' => 'system'],
            ['type' => 'plugin', 'name' => 'easystore', 'group' => 'finder'],
            ['type' => 'plugin', 'name' => 'easystorequickicon', 'group' => 'quickicon'],
            ['type' => 'plugin', 'name' => 'easystoreloadmodule', 'group' => 'content'],
        ];

        foreach ($extensions as $key => $extension) {
            $ext       = $parent->getParent()->getPath('source') . '/' . $extension['type'] . 's/' . $extension['name'];

            try {
                    $installer = new Installer();
                    $installer->setDatabase(Factory::getContainer()->get(DatabaseInterface::class));
                    $installer->install($ext);
            } catch (\Exception $e) {
                return Factory::getApplication()->enqueueMessage(
                    sprintf('Error installing %s: %s', $extension['name'], $e->getMessage()),
                    'error'
                );
            }

            if ($extension['type'] === 'plugin') {
                $db    = Factory::getContainer()->get(DatabaseInterface::class);
                $query = $db->getQuery(true);

                $fields     = [$db->quoteName('enabled') . ' = 1'];
                $conditions = [
                    $db->quoteName('type') . ' = ' . $db->quote($extension['type']),
                    $db->quoteName('element') . ' = ' . $db->quote($extension['name']),
                    $db->quoteName('folder') . ' = ' . $db->quote($extension['group']),
                ];

                $query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);
                $db->setQuery($query);
                $db->execute();
            }
        }

        $this->addInstallationDate();
        $this->insertRootCategory();
        $this->saveEmailTemplates($parent->getParent()->getPath('source'));
        $this->saveCashOnDelivery();
    }

    private function addInstallationDate()
    {
        if (!ComponentHelper::isInstalled('com_easystore')) {
            return;
        }

        $component   = ComponentHelper::getComponent('com_easystore');
        $params      = $component->getParams();
        $installedOn = $params->get('installed_on', null);

        if (!empty($installedOn)) {
            return;
        }

        $params->set('installed_on', Factory::getDate('now')->toSql());
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $fields = [
            $db->quoteName('params') . ' = ' . $db->quote($params->toString()),
        ];

        $conditions = [
            $db->quoteName('extension_id') . ' = ' . $component->id,
        ];

        $query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);
        $db->setQuery($query);

        try {
            $db->execute();
        } catch (Throwable $error) {
            throw $error;
        }
    }

    private function hasRootCategory()
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('id')
            ->from($db->quoteName('#__easystore_categories'))
            ->where($db->quoteName('alias') . ' = ' . $db->quote('root'));

        $db->setQuery($query);

        try {
            $category = $db->loadResult() ?? null;

            return !empty($category);
        } catch (Throwable $error) {
            throw $error;
        }

        return false;
    }

    private function insertRootCategory()
    {
        if ($this->hasRootCategory()) {
            return;
        }

        $data = (object) [
            'id'        => null,
            'title'     => 'ROOT',
            'alias'     => 'root',
            'published' => 1,
            'rgt' => 1,
            'created'   => Factory::getDate('now')->toSql(),
            'modified'  => Factory::getDate('now')->toSql(),
        ];

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            $db->insertObject('#__easystore_categories', $data, 'id');
        } catch (Throwable $error) {
            throw $error;
        }
    }

    /**
     * Save the email templates on after installing the component.
     * This will check the emails json and update the settings accordingly
     */
    private static function getSettings()
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select($db->quoteName(['key', 'value']))->from($db->quoteName('#__easystore_settings'));
        $db->setQuery($query);

        try {
            $settings = $db->loadObjectList('key') ?? [];

            foreach ($settings as &$setting) {
                $setting = json_decode($setting->value) ?? null;
            }

            unset($setting);

            $settings = (object) $settings;

            return new Registry($settings);
        } catch (Throwable $error) {
            return new Registry([]);
        }
    }

    public function saveEmailTemplates($base)
    {
        $settings       = static::getSettings();
        $savedTemplates = $settings->get('email_templates');

        $localTemplatePath = $base . '/media/data/emails.json';

        if (!file_exists($localTemplatePath)) {
            return;
        }

        $localTemplates = json_decode(file_get_contents($localTemplatePath));

        if (empty($savedTemplates)) {
            $this->saveToDatabase($localTemplates);
            return;
        }

        $newGroups     = $this->getNewGroups($savedTemplates, $localTemplates);
        $removedGroups = $this->getRemovedGroups($savedTemplates, $localTemplates);

        $savedTemplates = $this->removeGroups($savedTemplates, $removedGroups);
        $savedTemplates = $this->addNewGroups($savedTemplates, $newGroups);
        $savedTemplates = $this->updateGroups($savedTemplates, $localTemplates);

        $this->saveToDatabase($savedTemplates);
    }

    private function getNewGroups($saved, $local)
    {
        $savedGroups = array_keys((array) $saved);
        $localGroups = array_keys((array) $local);

        return array_values(array_diff($localGroups, $savedGroups));
    }

    private function getRemovedGroups($saved, $local)
    {
        $savedGroups = array_keys((array) $saved);
        $localGroups = array_keys((array) $local);

        return array_values(array_diff($savedGroups, $localGroups));
    }

    private function removeGroups($data, $groups)
    {
        if (!empty($groups)) {
            foreach ($groups as $group) {
                unset($data->$group);
            }
        }

        return $data;
    }

    private function addNewGroups($data, $groups)
    {
        if (!empty($groups)) {
            foreach ($groups as $group) {
                if (!isset($data->$group)) {
                    $data->$group = $group;
                }
            }
        }

        return $data;
    }

    private function updateGroups($saved, $local)
    {
        $savedKeys = array_keys((array) $saved);
        $localKeys = array_keys((array) $local);

        $updatingGroups = array_intersect($savedKeys, $localKeys);

        foreach ($updatingGroups as $group) {
            if (isset($saved->$group) && isset($local->$group)) {
                $savedTemplates = $saved->$group->templates ?? [];
                $localTemplates = $local->$group->templates ?? [];

                $templates = [];

                foreach ($localTemplates as $localTemplate) {
                    $savedTemplate = null;

                    foreach ($savedTemplates as $template) {
                        if ($template->type === $localTemplate->type) {
                            $savedTemplate = $template;
                            break;
                        }
                    }

                    if (empty($savedTemplate)) {
                        $templates[] = $localTemplate;
                        continue;
                    }

                    $subject   = $savedTemplate->subject ?? '';
                    $body      = $savedTemplate->body ?? '';
                    $isEnabled = $savedTemplate->is_enabled ?? false;

                    $template             = $localTemplate;
                    $template->subject    = $subject;
                    $template->body       = $body;
                    $template->is_enabled = $isEnabled;

                    $templates[] = $template;
                }

                $saved->$group            = $local->$group;
                $saved->$group->templates = $templates;
            }
        }

        return $saved;
    }

    private function saveToDatabase($value, $key = 'email_templates')
    {
        $data = (object) [
            'key'   => $key,
            'value' => json_encode($value),
        ];

        $settings     = static::getSettings();
        $settingsData = $settings->get($key);

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            if (is_null($settingsData)) {
                $db->insertObject('#__easystore_settings', $data);
            } else {
                $db->updateObject('#__easystore_settings', $data, 'key');
            }
        } catch (Throwable $error) {
            throw $error;
        }
    }

    /**
     * Saves Cash On Delivery payment method if it's enabled and manual payment is available.
     * @since 1.0.10
     */
    private function saveCashOnDelivery()
    {
        $settings = self::getSettings();
        $payments = $settings->get('payment');

        if (is_null($payments)) {
            return;
        }

        $paymentNames = array_column($payments->list ?? [], 'name');

        if (in_array('cod', $paymentNames) || !in_array('manual_payment', $paymentNames)) {
            return;
        }

        $payments->list = array_filter($payments->list, function ($payment) {
            return $payment->name !== 'manual_payment';
        });

        $data = (object) [
            'type'    => 'plugin',
            'element' => 'cod',
            'folder'  => 'easystore',
        ];

        $codPluginInfo = $this->getDataFromExtensionsTable($data);

        if (!is_null($codPluginInfo) && is_object($codPluginInfo)) {

            $params              = json_decode($codPluginInfo->params);
            $logoPath            = Uri::root(true) . '/plugins/easystore/' . $codPluginInfo->element . '/assets/images/logo.svg';
            $editUrl             = (Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $codPluginInfo->extension_id, false));
            $paymentInstructions = isset($params->payment_instruction) ? $params->payment_instruction : "";

            $cashOnDeliveryData = (object) [
                'type'              => $codPluginInfo->type,
                'name'              => $codPluginInfo->element,
                'id'                => $codPluginInfo->extension_id,
                'title'             => $params->title,
                'instruction'       => $paymentInstructions,
                'logo'              => $logoPath,
                'enabled'           => true,
                'edit_url'          => $editUrl,
                'plugin_id'         => $codPluginInfo->extension_id,
                'has_update'        => false,
                'link'              => "",
                'is_configured'     => true,
                'payment_type'      => 'manual',
                'is_manual_payment' => true,
            ];

            $payments->list[] = $cashOnDeliveryData;
            $payments->list   = array_values($payments->list);

            $this->saveToDatabase($payments, 'payment');
        }
    }

    /**
     * Retrieves data from the #__extensions table based on the provided extension information.
     *
     * @param  stdClass   $data An object containing information about the extension (type, element, folder).
     * @return mixed|null       The retrieved data as an object, or null if no data is found.
     * @throws Throwable        If an error occurs during database query execution.
     * @since  1.0.10
     */
    private function getDataFromExtensionsTable($data)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('*')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote($data->type))
            ->where($db->quoteName('element') . ' = ' . $db->quote($data->element))
            ->where($db->quoteName('folder') . ' = ' . $db->quote($data->folder));

        $db->setQuery($query);

        try {
            return $db->loadObject();
        } catch (Throwable $error) {
            throw $error;
        }
    }
}
