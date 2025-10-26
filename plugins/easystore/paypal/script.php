<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Install script class
 *
 * @since 1.0.0
 */
class PlgEasystorePaypalInstallerScript
{
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

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $fields     = [$db->quoteName('enabled') . ' = 1'];
        $conditions = [
            $db->quoteName('type') . ' = ' . $db->quote('plugin'),
            $db->quoteName('element') . ' = ' . $db->quote('paypal'),
            $db->quoteName('folder') . ' = ' . $db->quote('easystore'),
        ];

        $query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);
        $db->setQuery($query);
        $db->execute();
    }
}
