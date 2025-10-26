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
        'addon_name' => 'variants',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_VARIANTS'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_SINGLE_PRODUCT'),
        'context'    => 'easystore.single',
        'icon'       => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.954 3.754a9.402 9.402 0 0116.05 6.648c0 4.753 1.017 7.736 1.978 9.497.482.884.955 1.47 1.292 1.826a4.631 4.631 0 00.48.443l.014.01a1 1 0 01-.564 1.826H3a1 1 0 01-.563-1.827l.013-.01a4.617 4.617 0 00.48-.442c.337-.356.81-.942 1.292-1.826.961-1.762 1.979-4.744 1.979-9.498 0-2.493.99-4.884 2.753-6.647zM5.27 22.004h20.664c-.23-.33-.47-.711-.708-1.147-1.14-2.089-2.222-5.407-2.222-10.455a7.402 7.402 0 00-14.803 0c0 5.048-1.083 8.366-2.223 10.455a12.14 12.14 0 01-.708 1.147z" fill="currentColor"/><path opacity=".5" fill-rule="evenodd" clip-rule="evenodd" d="M12.678 27.74a1 1 0 011.367.363 1.8 1.8 0 003.115 0 1 1 0 011.73 1.003 3.8 3.8 0 01-6.575 0 1 1 0 01.363-1.366z" fill="currentColor"/></svg>',
        'settings'   => [
            'general' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_GENERAL'),
                'fields' => [
                     
                    'spacing' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_SPACING'),
                        'min'        => 4,
                        'max'        => 100,
                        'responsive' => true,
                    ],
                    // title
                    'title_admin_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_EASYSTORE_ADDON_VARIANT_TITLE'),
                    ],

                    'title_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'title_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    'title_spacing_admin_separator' => [
                        'type' => 'separator',
                    ],

                    'title_spacing' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_SPACING'),
                        'min'        => 0,
                        'max'        => 100,
                        'responsive' => true,
                    ],

                    'value_admin_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_EASYSTORE_ADDON_VARIANT_VALUE'),
                    ],

                    'value_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'value_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    'label_value_admin_separator' => [
                        'type' => 'separator',
                    ],

                    'label_value_separator' => [
                        'type'  => 'text',
                        'title' => Text::_('COM_EASYSTORE_ADDON_VARIANT_SEPARATOR'),
                        'std'   => ':',
                    ],

                    'label_value_separator_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'label_value_separator_spacing' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_SPACING'),
                        'min'        => 0,
                        'max'        => 100,
                        'responsive' => true,
                    ],
                ],
            ],

            'color_variant' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_VARIANT_COLOR_VARIANT'),
                'fields' => [
                    'color_variant_size' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_SIZE'),
                        'min'        => 4,
                        'max'        => 100,
                        'responsive' => true,
                    ],

                    'color_variant_border_radius' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_RADIUS'),
                        'min'        => 0,
                        'max'        => 100,
                        'responsive' => true,
                    ],

                    'color_variant_border_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_COLOR'),
                    ],

                    'color_variant_spacing' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_SPACING'),
                        'min'        => 0,
                        'max'        => 100,
                        'responsive' => true,
                    ],
                ],
            ],

            'list_variant' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_VARIANT_LIST_VARIANT'),
                'fields' => [
                    'list_variant_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    'list_variant_padding' => [
                        'type'       => 'padding',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING'),
                        'responsive' => true,
                    ],

                    'list_variant_border_radius' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_RADIUS'),
                        'min'        => 0,
                        'max'        => 100,
                        'responsive' => true,
                    ],

                    'list_variant_spacing' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_SPACING'),
                        'min'        => 0,
                        'max'        => 100,
                        'responsive' => true,
                    ],

                    'list_variant_option' => [
                        'type'   => 'radio',
                        'values' => [
                            'normal' => Text::_('COM_SPPAGEBUILDER_GLOBAL_NORMAL'),
                            'active' => Text::_('COM_SPPAGEBUILDER_GLOBAL_ACTIVE'),
                        ],
                        'std' => 'normal',
                    ],

                    'list_variant_background_color' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
                        'depends' => [
                            ['list_variant_option', '=', 'normal'],
                        ],
                    ],

                    'list_variant_color' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'depends' => [
                            ['list_variant_option', '=', 'normal'],
                        ],
                    ],

                    'list_variant_border_separator' => [
                        'type'    => 'separator',
                        'depends' => [
                            ['list_variant_option', '=', 'normal'],
                        ],
                    ],

                    'list_variant_border' => [
                        'type'    => 'border',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                        'depends' => [
                            ['list_variant_option', '=', 'normal'],
                        ],
                    ],

                    'list_variant_active_background_color' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
                        'depends' => [
                            ['list_variant_option', '=', 'active'],
                        ],
                    ],

                    'list_variant_active_color' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'depends' => [
                            ['list_variant_option', '=', 'active'],
                        ],
                    ],

                    'list_variant_active_border_separator' => [
                        'type'    => 'separator',
                        'depends' => [
                            ['list_variant_option', '=', 'active'],
                        ],
                    ],

                    'list_variant_active_border' => [
                        'type'    => 'border',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                        'depends' => [
                            ['list_variant_option', '=', 'active'],
                        ],
                    ],
                ],
            ],
        ],
    ]
);
