<?php
/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 * 
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Module\EasyStore\Site\Helper;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_easystore_search
 *
 * @since  1.0.0
 */

class SearchHelper
{
    /**
     * Get categories
     *
     * @return array
     * @throws \Exception
     * 
     * @since 1.0.0
     */
    public static function getCategories()
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select([$db->quoteName('c.title', 'title'), $db->quoteName('c.alias', 'alias')])
            ->from($db->quoteName('#__easystore_categories', 'c'))
            ->where($db->quoteName('alias') . ' != ' . $db->quote('root'))
            ->where($db->quoteName('c.published') . ' = 1');

        $db->setQuery($query);

        try {
            return $db->loadAssocList();
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }
    }
}