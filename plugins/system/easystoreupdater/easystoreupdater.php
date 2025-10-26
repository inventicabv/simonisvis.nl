<?php

/**
 * @package EasyStore
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
*/

use Joomla\CMS\Factory;
use Joomla\CMS\Http\Http;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseInterface;

//no direct access
defined('_JEXEC') or die('restricted access');
class plgSystemEasystoreupdater extends CMSPlugin
{
    public function onExtensionAfterSave($option, $data)
    {

        if (($option == 'com_config.component') && ( $data->element == 'com_easystore' )) {
            $app = Factory::getApplication();
            $params = new Registry();
            $params->loadString($data->params);

            $email       = $params->get('joomshaper_email');
            $license_key = $params->get('joomshaper_license_key');

            if (empty($email) || empty($license_key)) {
                $app->enqueueMessage('License key or email address field is empty.', 'warning');
                $app->redirect('index.php?option=com_config&view=component&component=com_easystore');

                return false;
            }

            $request  = new Http();
            $response = $request->get('https://www.joomshaper.com/index.php?option=com_product&task=validateLicense&joomshaper_email=' . $email . '&joomshaper_license_key=' . $license_key . '&product=easystore');

            if ($response->code != 200) {
                $app->enqueueMessage('Please enter a valid license key and email address to use EasyStore.', 'warning');
                $app->redirect('index.php?option=com_config&view=component&component=com_easystore');

                return false;
            }

            $fields      = array();

            $db          = Factory::getContainer()->get(DatabaseInterface::class);

            if (!empty($email) and !empty($license_key)) {
                $extra_query = 'joomshaper_email=' . urlencode($email);
                $extra_query .= '&amp;joomshaper_license_key=' . urlencode($license_key);
                $extra_query .= '&amp;product=easystore';

                $fields = array(
                    $db->quoteName('extra_query') . '=' . $db->quote($extra_query),
                    $db->quoteName('last_check_timestamp') . '=0'
                );
            }

            //Update column values of #__update_sites table after extension is saved.
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                        ->update($db->quoteName('#__update_sites'))
                        ->set($fields)
                        ->where($db->quoteName('name') . '=' . $db->quote('EasyStore'));
            $db->setQuery($query);
            $db->execute();
        }
    }
}
