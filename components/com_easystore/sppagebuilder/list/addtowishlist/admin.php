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
        'type'       => 'productList',
        'addon_name' => 'addtowishlist',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_ADDTOWISHLIST'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_PRODUCT_LIST'),
        'context'    => 'easystore.list',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path stroke="currentColor" stroke-miterlimit="10" stroke-width="2" d="M22.1 14.4c1.533 0 2.933.6 4 1.533l.133-.133c2-2 2.334-5.2.8-7.533-.333-.534-.8-1-1.333-1.4C22.967 4.8 18.5 5.4 15.967 8.8c-2.2-3.133-6.4-3.867-9.267-2.067-.133.067-.267.2-.4.334-2.867 2.2-3.133 6.466-.533 9.066l10.2 10.2 1.8-1.866C16.767 23.4 16.1 22 16.1 20.4c.067-3.333 2.733-6 6-6Z"/><path stroke="currentColor" stroke-miterlimit="10" stroke-width="2" d="M17.833 24.4c1.067 1.133 2.6 1.867 4.267 1.867 3.267 0 5.933-2.667 5.933-5.934 0-1.733-.733-3.266-1.933-4.4"/><path stroke="currentColor" stroke-miterlimit="10" stroke-width="2" d="M26.1 15.933c-1.067-.933-2.467-1.533-4-1.533-3.267 0-5.933 2.667-5.933 5.933 0 1.6.6 3 1.666 4.067M22.1 17.333v6M25.167 20.333H19.1"/></svg>',
        'settings'   => [
            'button' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON'),
                'fields' => [
                    'layout' => [
                        'type'   => 'select',
                        'title'  => Text::_('COM_EASYSTORE_BUTTON_LAYOUT'),
                        'values' => [
                            'text'      => Text::_('COM_EASYSTORE_BUTTON_LAYOUT_TEXT'),
                            'icon'      => Text::_('COM_EASYSTORE_BUTTON_LAYOUT_ICON'),
                            'icon-text' => Text::_('COM_EASYSTORE_BUTTON_LAYOUT_ICON_TEXT'),
                        ],
                        'std' => 'text',
                    ],

                    'typography' => [
                        'type'    => 'typography',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                        'depends' => [['layout', '!=', 'icon']],
                    ],

                    'padding' => [
                        'type'       => 'padding',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING'),
                        'responsive' => true,
                    ],

                    'width_separator' => [
                        'type' => 'separator',
                    ],

                    'width' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_WIDTH'),
                        'unit'       => ['px', '%', 'vw'],
                        'responsive' => true,
                    ],

                    'radius' => [
                        'type'       => 'advancedslider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_RADIUS'),
                        'responsive' => true,
                    ],

                    'button_state_separator' => [
                        'type' => 'separator',
                    ],

                    'button_state' => [
                        'type'   => 'buttons',
                        'values' => [
                            ['label' => Text::_('COM_SPPAGEBUILDER_GLOBAL_NORMAL'), 'value' => 'normal'],
                            ['label' => Text::_('COM_SPPAGEBUILDER_GLOBAL_HOVER'), 'value' => 'hover'],
                        ],
                        'std' => 'normal',
                    ],

                    // Normal
                    'color' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'depends' => [
                            ['button_state', '=', 'normal'],
                        ],
                    ],

                    'background' => [
                        'type'    => 'advancedcolor',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                        'depends' => [
                            ['button_state', '=', 'normal'],
                        ],
                    ],

                    'border_separator' => [
                        'type'    => 'separator',
                        'depends' => [
                            ['button_state', '=', 'normal'],
                        ],
                    ],

                    'border' => [
                        'type'    => 'border',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                        'depends' => [
                            ['button_state', '=', 'normal'],
                        ],
                    ],

                    // Hover
                    'color_hover' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'depends' => [
                            ['button_state', '=', 'hover'],
                        ],
                    ],

                    'background_hover' => [
                        'type'    => 'advancedcolor',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                        'depends' => [
                            ['button_state', '=', 'hover'],
                        ],
                    ],

                    'border_hover_separator' => [
                        'type'    => 'separator',
                        'depends' => [
                            ['button_state', '=', 'hover'],
                        ],
                    ],

                    'border_hover' => [
                        'type'    => 'border',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                        'depends' => [
                            ['button_state', '=', 'hover'],
                        ],
                    ],

                ],
            ],

            'icon' => [
                'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_ICON'),
                'depends' => [['layout', '!=', 'text']],
                'fields'  => [
                    'icon_size' => [
                        'type'    => 'slider',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_SIZE'),
                        'depends' => [['layout', '!=', 'text']],
                    ],

                    'gap' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_GAP'),
                        'min'        => 0,
                        'max'        => 100,
                        'std'        => ['xl' => 4],
                        'responsive' => true,
                        'depends'    => [['layout', '=', 'icon-text']],
                    ],

                    'icon_state_separator' => [
                        'type' => 'separator',
                    ],

                    'icon_state' => [
                        'type'   => 'buttons',
                        'values' => [
                            ['label' => Text::_('COM_SPPAGEBUILDER_GLOBAL_NORMAL'), 'value' => 'normal'],
                            ['label' => Text::_('COM_SPPAGEBUILDER_GLOBAL_HOVER'), 'value' => 'hover'],
                        ],
                        'std' => 'normal',
                    ],

                    'icon_color' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'depends' => [
                            ['layout', '!=', 'text'],
                            ['icon_state', '=', 'normal'],
                        ],
                    ],

                    'icon_color_hover' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'depends' => [
                            ['layout', '!=', 'text'],
                            ['icon_state', '=', 'hover'],
                        ],
                    ],
                ],
            ],
        ],
    ]
);
