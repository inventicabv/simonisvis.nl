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
        'addon_name' => 'badge',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_BADGE'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_SINGLE_PRODUCT'),
        'context'    => 'easystore.single',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path stroke="currentColor" stroke-miterlimit="10" stroke-width="2" d="M16 19.34c3.94 0 7.133-3.134 7.133-7s-3.193-7-7.133-7-7.133 3.134-7.133 7 3.193 7 7.133 7Z"/><path stroke="currentColor" stroke-miterlimit="10" stroke-width="2" d="M16 14.473a2.133 2.133 0 1 0 0-4.266 2.133 2.133 0 0 0 0 4.266ZM11.667 18.007v8.333c0 .2.266.333.4.2l4-3.2c.066-.067.2-.067.333 0l4 3.267c.2.133.4 0 .4-.2v-8.8"/></svg>',
        'settings'   => [

            'sale' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_BADGE_SALE'),
                'fields' => [
                    'sale' => [
                        'type'      => 'checkbox',
                        'title'     => '',
                        'std'       => 1,
                        'is_header' => 1,
                    ],
                    'text' => [
                        'type'   => 'select',
                        'title'  => Text::_('COM_EASYSTORE_ADDON_BADGE_OPTION_TEXT'),
                        'values' => [
                            'sale'    => Text::_('COM_EASYSTORE_ADDON_BADGE_SALE'),
                            'on_sale' => Text::_('COM_EASYSTORE_ADDON_BADGE_ON_SALE'),
                            'off'     => Text::_('COM_EASYSTORE_ADDON_BADGE_OFF'),
                            'custom'  => Text::_('COM_EASYSTORE_ADDON_BADGE_OPTION_CUSTOM'),
                        ],
                        'std'     => 'sale',
                        'depends' => [['sale', '=', 1]],
                    ],

                    'custom_text' => [
                        'type'    => 'text',
                        'title'   => Text::_('COM_EASYSTORE_ADDON_BADGE_OPTION_CUSTOM_TEXT'),
                        'depends' => [
                            ['text', '=', 'custom'],
                            ['sale', '=', 1],
                        ],
                        'std' => 'Custom',
                    ],

                    'typography_separator' => [
                        'type'    => 'separator',
                        'depends' => [['sale', '=', 1]],
                    ],

                    'typography' => [
                        'type'    => 'typography',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                        'depends' => [['sale', '=', 1]],
                    ],

                    'color' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'std'     => '#ffffff',
                        'depends' => [['sale', '=', 1]],
                    ],

                    'background_color' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
                        'std'     => '#24A148',
                        'depends' => [['sale', '=', 1]],
                    ],
                ],
            ],
            'featured' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_BADGE_FEATURED'),
                'fields' => [
                    'featured' => [
                        'type'      => 'checkbox',
                        'title'     => Text::_('COM_EASYSTORE_ADDON_BADGE_FEATURED'),
                        'std'       => 0,
                        'is_header' => 1,
                    ],

                    'featured_text' => [
                        'type'    => 'text',
                        'title'   => Text::_('COM_EASYSTORE_ADDON_BADGE_OPTION_FEATURED_TEXT'),
                        'depends' => [
                            ['featured', '=', 1],
                        ],
                        'std' => 'Featured',
                    ],
                    'featured_typography_separator' => [
                        'type'    => 'separator',
                        'depends' => [
                            ['featured', '=', 1],
                        ],
                    ],
                    'featured_typography' => [
                        'type'    => 'typography',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                        'depends' => [
                            ['featured', '=', 1],
                        ],
                    ],

                    'featured_color' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'std'     => '#ffffff',
                        'depends' => [
                            ['featured', '=', 1],
                        ],
                    ],

                    'featured_background_color' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
                        'std'     => '#000000',
                        'depends' => [
                            ['featured', '=', 1],
                        ],
                    ],
                ],
            ],
            'basic' => [
                'title'  => Text::_('COM_EASYSTORE_GLOBAL_BASIC'),
                'fields' => [
                    'padding' => [
                        'type'       => 'padding',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING'),
                        'responsive' => true,
                        'std'        => [
                            'xl' => '4px 8px 4px 8px',
                        ],
                    ],

                    'border_separator' => [
                        'type' => 'separator',
                    ],
                    'border' => [
                        'type'       => 'border',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                        'responsive' => true,
                    ],

                    'border_radius' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_RADIUS'),
                        'max'   => 100,
                        'std'   => [
                            'xl' => 4,
                        ],
                        'responsive' => true,
                    ],
                ],
            ],
        ],
    ]
);
