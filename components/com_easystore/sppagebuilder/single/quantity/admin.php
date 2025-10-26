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
        'addon_name' => 'quantity',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_QUANTITY'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_SINGLE_PRODUCT'),
        'context'    => 'easystore.single',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M28.957 14.427h-8.98l6.35-6.349a11.384 11.384 0 0 1 2.63 6.35ZM17.013 3.744c2.273.198 4.499 1.074 6.349 2.629l-6.35 6.349V3.744Zm-3.575 13.107a1 1 0 0 0 1 1h11.396c-.506 5.853-5.414 10.442-11.396 10.442C8.121 28.293 3 23.17 3 16.85c0-5.98 4.591-10.888 10.438-11.395V16.85Z"/></svg>',
        'settings'   => [
            'input' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_QUANTITY_OPTION_INPUT'),
                'fields' => [
                    'width' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_WIDTH'),
                        'min'        => 40,
                        'max'        => 1000,
                        'responsive' => true,
                    ],

                    'height' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_HEIGHT'),
                        'min'        => 10,
                        'max'        => 100,
                        'responsive' => true,
                    ],

                    'typography_separator' => [
                        'type' => 'separator',
                    ],

                    'typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    'color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'background' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                    ],

                    'border_separator' => [
                        'type' => 'separator',
                    ],

                    'border' => [
                        'type'  => 'border',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                    ],

                    'radius' => [
                        'type'       => 'advancedslider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_RADIUS'),
                        'responsive' => true,
                    ],
                ],
            ],

            'button' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTONS'),
                'fields' => [
                    'button_width' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_WIDTH'),
                        'min'        => 16,
                        'max'        => 100,
                        'responsive' => true,
                    ],

                    'button_icon_size' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_ICON_SIZE'),
                        'min'        => 10,
                        'max'        => 100,
                        'responsive' => true,
                    ],

                    'button_color_separator' => [
                        'type' => 'separator',
                    ],

                    'button_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'button_color_hover' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR_HOVER'),
                    ],
                ],
            ],
        ],
    ],
);
