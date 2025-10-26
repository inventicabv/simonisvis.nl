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
        'addon_name' => 'specification',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_SPECIFICATION'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_SINGLE_PRODUCT'),
        'context'    => 'easystore.single',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path stroke="currentColor" stroke-miterlimit="10" stroke-width="2" d="M16 24.267a8.267 8.267 0 1 0 0-16.534 8.267 8.267 0 0 0 0 16.534Z"/><path stroke="currentColor" stroke-miterlimit="10" stroke-width="2" d="M16 20.533a4.533 4.533 0 1 0 0-9.066 4.533 4.533 0 0 0 0 9.066Z"/><path stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2" d="M9.067 16H5.333M26.667 16H23M15.867 22.933v3.734M15.867 5.333V9"/></svg>',
        'settings'   => [
            'basic' => [
                'fields' => [

                    // Specification Title
                    'specification_title_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_EASYSTORE_ADDON_SPECIFICATION'),
                    ],

                    'specification_title_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'specification_title_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    // Specification Item(s) key
                    'specification_item_key_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_EASYSTORE_ADDON_SPECIFICATION_ITEM_KEY'),
                    ],

                    'specification_item_key_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'specification_item_key_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    // Specification Item(s) Value
                    'specification_item_value_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_EASYSTORE_ADDON_SPECIFICATION_ITEM_KEY_VALUE'),
                    ],
                    'specification_item_value_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'specification_item_value_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    // Specification Item Separator
                    'specification_item_key_value_separator_label' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_EASYSTORE_ADDON_SPECIFICATION_ITEM_SEPARATOR'),
                    ],

                    'specification_item_key_value_separator' => [
                        'type'  => 'text',
                        'title' => Text::_('COM_EASYSTORE_ADDON_SEPARATOR'),
                        'std'   => ':',
                    ],

                    'specification_item_key_value_separator_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'specification_item_key_value_separator_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],
                ],
            ],
        ],
        'attr' => [],
    ]
);
