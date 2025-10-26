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
        'addon_name' => 'review_form',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_REVIEW_FORM'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_SINGLE_PRODUCT'),
        'context'    => 'easystore.single',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path stroke="currentColor" stroke-miterlimit="10" stroke-width="2" d="M8.5 5.067c-.667 0-1.2.533-1.2 1.2V25.8c0 .667.533 1.2 1.2 1.2h14.933c.667 0 1.2-.533 1.2-1.2V11.067L18.567 5H8.5v.067Z"/><path stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2" d="M18.633 10.933v-5.4c0-.2.2-.266.334-.133l5.4 5.4c.133.133.066.333-.134.333h-5.6M11.233 11.8h3.6M11.1 16.133h10.067M11.1 20.4h10.067"/></svg>',
        'settings'   => [
            'normal' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_NORMAL'),
                'fields' => [
                    'color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    'background_separator' => [
                        'type' => 'separator',
                    ],

                    'background' => [
                        'type'  => 'advancedcolor',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
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

                    'border_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                    ],

                    'border_width' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_WIDTH'),
                        'min'        => 0,
                        'max'        => 100,
                        'responsive' => true,
                    ],

                    'border_style' => [
                        'type'   => 'select',
                        'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_STYLE'),
                        'values' => [
                            'none'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_STYLE_NONE'),
                            'solid'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_STYLE_SOLID'),
                            'dashed' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_STYLE_DASHED'),
                            'dotted' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_STYLE_DOTTED'),
                        ],
                    ],

                    'border_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'radius_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                    ],

                    'radius' => [
                        'type'       => 'advancedslider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_RADIUS'),
                        'responsive' => true,
                    ],

                    'icon_separator' => [
                        'type' => 'separator',
                    ],

                    'show_icon' => [
                        'type'   => 'select',
                        'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_ICON'),
                        'values' => [
                            '1' => Text::_('COM_SPPAGEBUILDER_YES'),
                            '0' => Text::_('COM_SPPAGEBUILDER_NO'),
                        ],
                        'std' => '1',
                    ],

                    'icon_color' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'depends' => [['show_icon', '=', '1']],
                    ],

                    'show_divider_separator' => [
                        'type'    => 'separator',
                        'depends' => [['show_icon', '=', '1']],
                    ],

                    'show_divider' => [
                        'type'   => 'select',
                        'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_DIVIDER'),
                        'values' => [
                            '1' => Text::_('JYES'),
                            '0' => Text::_('JNO'),
                        ],
                        'std'     => '1',
                        'depends' => [['show_icon', '=', '1']],
                    ],

                    'divider_color' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'depends' => [['show_icon', '=', '1']],
                    ],

                    'gap_separator' => [
                        'type'    => 'separator',
                        'depends' => [['show_icon', '=', '1']],
                    ],

                    'gap' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_GAP'),
                        'min'        => 0,
                        'max'        => 100,
                        'std'        => ['xl' => 4],
                        'responsive' => true,
                        'depends'    => [['show_icon', '=', '1']],
                    ],
                ],
            ],

            'hover' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_HOVER'),
                'fields' => [
                    'color_hover' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'background_hover' => [
                        'type'  => 'advancedcolor',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                    ],

                    'border_color_hover' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_COLOR'),
                    ],

                    'icon_separator_hover' => [
                        'type'    => 'separator',
                        'depends' => [['show_icon', '=', '1']],
                    ],

                    'icon_color_hover' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_ICON_COLOR'),
                        'depends' => [['show_icon', '=', '1']],
                    ],

                    'show_divider_separator_hover' => [
                        'type'    => 'separator',
                        'depends' => [['show_icon', '=', '1']],
                    ],

                    'divider_color_hover' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'depends' => [['show_icon', '=', '1']],
                    ],
                ],
            ],
        ],
    ]
);

// button
// input
// Spacing
