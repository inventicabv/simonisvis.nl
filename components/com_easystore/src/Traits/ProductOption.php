<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright (C) 2023 - 2024 JoomShaper. <https: //www.joomshaper.com>
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Traits;

use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


trait ProductOption
{
    public static function getOptions($id)
    {
        $orm     = new EasyStoreDatabaseOrm();
        $options = $orm->setColumns(['id', 'name', 'type'])
            ->hasMany($id, '#__easystore_product_options', 'product_id')
            ->updateQuery(function ($query) use ($orm) {
                $query->order($orm->quoteName('ordering') . ' ASC');
            })
            ->loadObjectList();

        if (!empty($options)) {
            foreach ($options as &$option) {
                $option->values = $orm->setColumns(['id', 'name', 'color'])
                    ->hasMany($option->id, '#__easystore_product_option_values', 'option_id')
                    ->updateQuery(function ($query) use ($orm) {
                        $query->order($orm->quoteName('ordering') . ' ASC');
                    })
                    ->loadObjectList();
                $option->isCollapsed = true;
            }

            unset($option);
        }

        return $options;
    }
}
