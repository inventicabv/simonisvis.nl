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
        'addon_name' => 'gallery',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_GALLERY'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_SINGLE_PRODUCT'),
        'context'    => 'easystore.single',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2" d="M5.8 5.333v21.334M26.2 5.333v21.334M14.2 6.6H8.8v7.533h5.4V6.6ZM23.2 6.6h-5.4v7.533h5.4V6.6ZM14.2 17.867H8.8V25.4h5.4v-7.533ZM23.2 17.867h-5.4V25.4h5.4v-7.533Z"/></svg>',
        'settings'   => [
            'general' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_GENERAL'),
                'fields' => [
                    'columns' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_EASYSTORE_ADDON_OPTIONS_COLUMNS'),
                        'min'   => 1,
                        'max'   => 8,
                        'std'   => [
                            'xl' => 3,
                        ],
                        'responsive' => true,
                    ],

                    'spacing' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_SPACING'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 4,
                        ],
                        'responsive' => true,
                    ],
                ],
            ],

            'image' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_IMAGE'),
                'fields' => [
                    'padding' => [
                        'type'       => 'padding',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING'),
                        'responsive' => true,
                    ],

                    'radius_separator' => [
                        'type' => 'separator',
                    ],

                    'radius' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_RADIUS'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 4,
                        ],
                        'responsive' => true,
                    ],

                    'image_admin_options_separator' => [
                        'type' => 'separator',
                    ],

                    'image_admin_options' => [
                        'type'   => 'radio',
                        'values' => [
                            'normal' => Text::_('COM_SPPAGEBUILDER_GLOBAL_NORMAL'),
                            'hover'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_HOVER'),
                            'active' => Text::_('COM_SPPAGEBUILDER_GLOBAL_ACTIVE'),
                        ],
                        'std' => 'normal',
                    ],

                    'background_color' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
                        'depends' => [['image_admin_options', '=', 'normal']],
                    ],

                    'opacity' => [
                        'type'    => 'slider',
                        'title'   => Text::_('COM_EASYSTORE_ADDON_GALLERY_OPTION_OPACITY'),
                        'step'    => 0.1,
                        'min'     => 0,
                        'max'     => 1,
                        'depends' => [['image_admin_options', '=', 'normal']],
                    ],

                    'border' => [
                        'type'    => 'border',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                        'depends' => [['image_admin_options', '=', 'normal']],
                    ],

                    'background_color_hover' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
                        'depends' => [['image_admin_options', '=', 'hover']],
                    ],

                    'opacity_hover' => [
                        'type'    => 'slider',
                        'title'   => Text::_('COM_EASYSTORE_ADDON_GALLERY_OPTION_OPACITY'),
                        'step'    => 0.1,
                        'min'     => 0,
                        'max'     => 1,
                        'depends' => [['image_admin_options', '=', 'hover']],
                    ],

                    'border_hover' => [
                        'type'    => 'border',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                        'depends' => [['image_admin_options', '=', 'hover']],
                    ],

                    'background_color_active' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
                        'depends' => [['image_admin_options', '=', 'active']],
                    ],

                    'opacity_active' => [
                        'type'    => 'slider',
                        'title'   => Text::_('COM_EASYSTORE_ADDON_GALLERY_OPTION_OPACITY'),
                        'step'    => 0.1,
                        'min'     => 0,
                        'max'     => 1,
                        'depends' => [['image_admin_options', '=', 'active']],
                    ],

                    'border_active' => [
                        'type'    => 'border',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                        'depends' => [['image_admin_options', '=', 'active']],
                    ],
                ],
            ],
        ],
    ]
);
