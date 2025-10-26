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
        'addon_name' => 'title',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_TITLE'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_PRODUCT_LIST'),
        'context'    => 'easystore.list',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path stroke="currentColor" stroke-miterlimit="10" stroke-width="2" d="M23.733 9.167H8.267M7.867 7.433v3.4M24.133 7.433v3.4M16 9.167v15.4M17.733 24.567h-3.466"/></svg>',
        'settings'   => [
            'basic' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_TITLE'),
                'fields' => [
                    'color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    'selector' => [
                        'type'  => 'headings',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_HTML_ELEMENT'),
                        'std'   => 'h1',
                    ],

                    'show_link' => [
                        'type'  => 'checkbox',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_LINK'),
                        'std'   => 1,
                    ],

                    'alignment' => [
                        'type'              => 'alignment',
                        'title'             => Text::_('COM_SPPAGEBUILDER_GLOBAL_ALIGNMENT'),
                        'responsive'        => true,
                        'available_options' => ['left', 'center', 'right'],
                    ],

                    'alignment_separator' => [
                        'type' => 'separator',
                    ],

                    'title_text_shadow' => [
                        'type'   => 'boxshadow',
                        'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_TEXT_SHADOW'),
                        'std'    => '0 0 0 transparent',
                        'config' => ['spread' => false],
                    ],
                ],
            ],

            'icon' => [
                'title'  => 'Icon',
                'fields' => [
                    'title_icon' => [
                        'type'  => 'icon',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_ICON'),
                    ],

                    'title_icon_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'title_icon_position' => [
                        'type'   => 'select',
                        'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_POSITION'),
                        'values' => [
                            'before' => Text::_('COM_SPPAGEBUILDER_ADDON_TITLE_ICON_POSITION_BEFORE'),
                            'after'  => Text::_('COM_SPPAGEBUILDER_ADDON_TITLE_ICON_POSITION_AFTER'),
                        ],
                        'std' => 'before',
                    ],
                ],
            ],

            'title_spacing' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_TITLE_SPACING'),
                'fields' => [
                    'title_margin' => [
                        'type'       => 'margin',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_MARGIN'),
                        'desc'       => Text::_('COM_SPPAGEBUILDER_GLOBAL_MARGIN_DESC'),
                        'std'        => ['xl' => '0px 0px 0px 0px', 'lg' => '', 'md' => '', 'sm' => '', 'xs' => ''],
                        'responsive' => true,
                    ],

                    'title_padding' => [
                        'type'       => 'padding',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING'),
                        'desc'       => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING_DESC'),
                        'std'        => ['xl' => '0px 0px 0px 0px', 'lg' => '', 'md' => '', 'sm' => '', 'xs' => ''],
                        'responsive' => true,
                    ],
                ],
            ],
        ],
    ]
);
