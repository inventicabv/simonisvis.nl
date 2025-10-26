<?php

/**
 * @package SP Page Builder
 * @author JoomShaper https://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;

SpAddonsConfig::addonConfig(
    [
        'type'       => 'product',
        'addon_name' => 'availability',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_AVAILABILITY'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_SINGLE_PRODUCT'),
        'context'    => 'easystore.single',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2" d="M5.8 5.333v21.334M26.2 5.333v21.334M14.2 6.6H8.8v7.533h5.4V6.6ZM23.2 6.6h-5.4v7.533h5.4V6.6ZM14.2 17.867H8.8V25.4h5.4v-7.533ZM23.2 17.867h-5.4V25.4h5.4v-7.533Z"/></svg>',
        'settings'   => [
            'general' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_GENERAL'),
                'fields' => [
                    'typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    'color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'quantity_separator' => [
                        'type' => 'separator',
                    ],

                    'show_quantity' => [
                        'type'   => 'select',
                        'title'  => Text::_('COM_EASYSTORE_ADDON_INVENTORY_SHOW_QUANTITY'),
                        'values' => [
                            '1' => Text::_('COM_SPPAGEBUILDER_YES'),
                            '0' => Text::_('COM_SPPAGEBUILDER_NO'),
                        ],
                        'std' => '1',
                    ],

                    'icon_separator' => [
                        'type' => 'separator',
                    ],

                    'show_icon' => [
                        'type'   => 'select',
                        'title'  => Text::_('COM_EASYSTORE_ADDON_INVENTORY_SHOW_ICON'),
                        'values' => [
                            '1' => Text::_('COM_SPPAGEBUILDER_YES'),
                            '0' => Text::_('COM_SPPAGEBUILDER_NO'),
                        ],
                        'std' => '1',
                    ],

                    'icon_size' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_SIZE'),
                        'min'        => 4,
                        'max'        => 100,
                        'responsive' => true,
                    ],

                    'icon_spacing' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_SPACING'),
                        'min'        => 4,
                        'max'        => 100,
                        'responsive' => true,
                    ],

                    'icon_color_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_EASYSTORE_ADDON_INVENTORY_ICON_COLOR'),
                    ],

                    'available_stock_icon_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_EASYSTORE_ADDON_INVENTORY_AVAILABLE_STOCK_ICON_COLOR'),
                    ],

                    'out_of_stock_icon_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_EASYSTORE_ADDON_INVENTORY_OUT_OF_STOCK_ICON_COLOR'),
                    ],
                ],
            ],
        ],
    ]
);
