<?php

/**
 * @package     EasyStore.Plugin
 * @subpackage  User.easystoreprofile
 *
 * @copyright   (C) 2024 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Plugin\User\EasyStoreProfile\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseAwareTrait;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * EasyStore Customer plugin
 *
 * @since  1.0.0
 */
final class EasyStoreProfile extends CMSPlugin
{
    use DatabaseAwareTrait;

    /**
     * Redirect easystore customer to their profile.
     *
     * @param  array $options
     * 
     * @return void
     * 
     * @since 1.0.0
     */
    public function onUserAfterLogin(array $options): void
    {
        $app = Factory::getApplication();

        if ($app->isClient('site')) {
            $id       = $app->getIdentity()->get('id');
            $db = $this->getDatabase();
            $query = $db->getQuery(true);

            // Check if the user has a profile record
            $query->select('COUNT(*)')
                ->from($db->quoteName('#__easystore_users'))
                ->where($db->quoteName('user_id') . ' = ' . $db->quote($id));

            $db->setQuery($query);

            try {
                $count = $db->loadResult();

            } catch (\RuntimeException $e) {
                // Handle any database errors here
                $app->enqueueMessage($e->getMessage(), 'error');
            }

            // @todo improve this function later
            // if ($count) {
            //     $redirect_url = Route::_('index.php?option=com_easystore&view=profile', false);
            //     $app->redirect($redirect_url);
            // }
        }
    }

    /**
     * Create customer when new Joomla user created
     *
     * @param  $user
     * @param  $isNew
     * @param  $success
     * @param  $msg
     * 
     * @return void
     * 
     * @since 1.0.0
     */
    public function onUserAfterSave($user, $isNew, $success, $msg)
    {
        if (!$isNew || !$success) {
            return true;
        }

        // Get a db connection.
        $db = $this->getDatabase();

        // Create a new query object.
        $query = $db->getQuery(true);

        // Insert columns.
        $columns = array('user_id');

        // Insert values.
        $values = array($db->quote($user['id']));

        // Prepare the insert query.
        $query
            ->insert($db->quoteName('#__easystore_users'))
            ->columns($db->quoteName($columns))
            ->values(implode(',', $values));

        // Set the query using our newly populated query object and execute it.
        $db->setQuery($query);

        try {
            $db->execute();
        } catch (\RuntimeException $e) {
            // Handle any database errors here
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return false;
        }

        return true;
    }
}