<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Model;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseInterface;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * EasyStore Settings Model
 *
 * @since  1.0.0
 */
class SettingsModel extends ListModel
{
    /**
      * Function to get Settings
      *
      * @param string $key
      * @return object
      */
    public function getSettings(string $key = '')
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('*');
        $query->from($db->quoteName('#__easystore_settings'));

        if (!empty($key)) {
            $query->where($db->quoteName('key') . " = " . $db->quote($key));
        }

        $db->setQuery($query);

        try {
            if (empty($key)) {
                $result = $db->loadObjectList();
            } else {
                $result = $db->loadObject();
            }

            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Function to create/update Settings
     *
     * @param object $settingsInfo
     * @return bool
     */
    public function createOrUpdate($settingsInfo)
    {
        $db         = Factory::getContainer()->get(DatabaseInterface::class);
        $query      = $db->getQuery(true);

        $query->select($db->quoteName('id'));
        $query->from($db->quoteName('#__easystore_settings'));
        $query->where($db->quoteName('key') . " = " . $db->quote($settingsInfo->property));
        $db->setQuery($query);

        try {
            $existingSettings = $db->loadObject();

            if (!$existingSettings) {
                $data = (object) [
                    'key'   => $settingsInfo->property,
                    'value' => $settingsInfo->data,
                ];

                $result = EasyStoreDatabaseOrm::updateOrCreate('#__easystore_settings', $data);

                return !empty($result->id);
            } else {
                $data = (object) [
                    'id'    => $existingSettings->id,
                    'key'   => $settingsInfo->property,
                    'value' => $settingsInfo->data,
                ];

                $result = EasyStoreDatabaseOrm::updateOrCreate('#__easystore_settings', $data);

                return !empty($result->id);
            }
        } catch (Exception $error) {
            return false;
        }
    }
}
