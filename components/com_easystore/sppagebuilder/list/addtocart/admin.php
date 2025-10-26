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
        'addon_name' => 'addtocart',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_ADDTOCART'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_PRODUCT_LIST'),
        'context'    => 'easystore.list',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2" d="M7.467 5.3v11.067c0 1.266.933 2.266 2.066 2.266h13.2c1.134 0 2.067-.733 2.4-1.8l3.534-11.4"/><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M23.267 22.3H9.533c-1.133 0-2.066-1-2.066-2.267V5.3H3.333"/><path stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2" d="M11.6 26.7c1.141 0 2.067-.985 2.067-2.2 0-1.215-.926-2.2-2.067-2.2s-2.067.985-2.067 2.2c0 1.215.926 2.2 2.067 2.2ZM23.267 26.7c1.141 0 2.066-.985 2.066-2.2 0-1.215-.925-2.2-2.066-2.2-1.142 0-2.067.985-2.067 2.2 0 1.215.925 2.2 2.067 2.2ZM18.067 6.1v7.867M22.133 10.033H14"/></svg>',
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
