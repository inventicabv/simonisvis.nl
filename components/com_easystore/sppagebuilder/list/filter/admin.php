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
        'addon_name' => 'filter',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_PRODUCT_LIST'),
        'context'    => 'easystore.list',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path stroke="currentColor" stroke-width="2" d="M18.6387 14.5528L18.6386 14.5527L18.6336 14.5582C18.1751 15.0566 17.9088 15.7121 17.9088 16.4V24.224C17.9088 24.3155 17.8963 24.3495 17.8579 24.4137C17.8286 24.4627 17.7825 24.5279 17.7026 24.6273C17.6408 24.7043 17.5733 24.784 17.4893 24.8833C17.4633 24.914 17.4357 24.9467 17.4062 24.9817L14.0944 28.7282V16.3968C14.0944 15.7167 13.8363 15.0524 13.3634 14.5484C13.3631 14.5481 13.3627 14.5477 13.3624 14.5474L3.08085 3.53963C3.08072 3.53949 3.08059 3.53935 3.08045 3.5392C2.92265 3.3689 3.02062 3.05444 3.31197 3.05444H28.6912C28.984 3.05444 29.0786 3.37228 28.9236 3.53822L28.9235 3.53836L18.6387 14.5528Z"/></svg>',
        'settings'   => [
            'content' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_ITEMS'),
                'fields' => [
                    'filter_items' => [
                        'type'  => 'repeatable',
                        'title' => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_ITEMS'),
                        'attr'  => [
                            'title' => [
                                'type'  => 'text',
                                'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TITLE'),
                                'std'   => 'Filter Title',
                            ],

                            'type' => [
                                'type'   => 'select',
                                'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPE'),
                                'values' => [
                                    'pricerange'   => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_TYPE_PRICE_RANGE'),
                                    'categories'   => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_TYPE_CATEGORIES'),
                                    'variants'     => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_TYPE_VARIANTS'),
                                    'tags'         => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_TYPE_TAGS'),
                                    'brands'       => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_TYPE_BRANDS'),
                                    'availability' => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_TYPE_AVAILABILITY'),
                                    'sorting'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_TYPE_SORTING'),
                                ],
                                'std' => '',
                            ],

                            'option_admin_separator' => [
                                'type' => 'separator',
                            ],

                            // 'style' => [
                            //     'type'   => 'select',
                            //     'title'  => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_STYLE'),
                            //     'values' => [
                            //         'list'     => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_STYLE_LIST'),
                            //         'dropdown' => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_STYLE_DROPDOWN'),
                            //     ],
                            //     'std' => 'list',
                            //     'depends' => [
                            //         ['type', '!=', 'pricerange'],
                            //         ['type', '!=', 'sorting'],
                            //     ],
                            // ],

                            'show_title' => [
                                'type'   => 'checkbox',
                                'title'  => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_SHOW_TITLE'),
                                'values' => [
                                    '1' => Text::_('COM_SPPAGEBUILDER_YES'),
                                    '0' => Text::_('COM_SPPAGEBUILDER_NO'),
                                ],
                                'std' => '1',
                            ],

                            'show_count' => [
                                'type'   => 'checkbox',
                                'title'  => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_SHOW_COUNT'),
                                'values' => [
                                    '1' => Text::_('COM_SPPAGEBUILDER_YES'),
                                    '0' => Text::_('COM_SPPAGEBUILDER_NO'),
                                ],
                                'std'     => '1',
                                'depends' => [
                                    ['type', '!=', 'pricerange'],
                                    ['type', '!=', 'sorting'],
                                ],
                            ],
                            'enable_single_selection' => [
                                'type'   => 'checkbox',
                                'title'  => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_ENABLE_SINGLE_SELECTION'),
                                'values' => [
                                    '1' => Text::_('COM_SPPAGEBUILDER_YES'),
                                    '0' => Text::_('COM_SPPAGEBUILDER_NO'),
                                ],
                                'std'     => '0',
                                'depends' => [
                                    ['type', '!=', 'availability'],
                                    ['type', '!=', 'pricerange'],
                                    ['type', '!=', 'sorting'],
                                ],
                            ],

                            'option_separator' => [
                                'type' => 'separator',
                                'depends' => [
                                    ['type', '!=', 'availability'],
                                    ['type', '!=', 'pricerange'],
                                    ['type', '!=', 'sorting'],
                                ],
                            ],
                            'ordering' => [
                                'type'   => 'select',
                                'title'  => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_ORDERING'),
                                'values' => [
                                    'ASC'   => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_ORDERING_ASC'),
                                    'DESC'   => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_ORDERING_DESC'),
                                ],
                                'std' => '',
                                'depends' => [
                                    ['type', '!=', 'availability'],
                                    ['type', '!=', 'pricerange'],
                                    ['type', '!=', 'sorting'],
                                ],
                            ],

                        ],
                    ],

                    'gap_separator' => [
                        'type' => 'separator',
                    ],

                    'direction' => [
                        'type'   => 'select',
                        'title'  => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_DIRECTION'),
                        'values' => [
                            'row'    => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_DIRECTION_ROW'),
                            'column' => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_DIRECTION_COLUMN'),
                        ],
                        'std' => 'column',
                    ],

                    'gap' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_GAP'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 16,
                        ],
                        'responsive' => true,
                    ],

                    'title_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TITLE'),
                    ],

                    'title_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'std'   => '#000000',
                    ],

                    'title_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    'title_gap' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_MARGIN'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 16,
                        ],
                        'responsive' => true,
                    ],

                    'reset_btn_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_OPTION_RESET_BUTTON'),
                    ],

                    'reset_btn_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],
                ],
            ],

            'checkbox_radio' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_OPTION_CHECKBOX_OR_RADIO'),
                'fields' => [
                    'checkbox_radio_size' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_SIZE'),
                        'min'   => 8,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 16,
                        ],
                        'responsive' => true,
                    ],

                    'checkbox_radio_check_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_OPTION_CHECK_COLOR'),
                    ],

                    'checkbox_border_radius' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_OPTION_CHECKBOX_BORDER_RADIUS'),
                        'min'   => 0,
                        'max'   => 20,
                        'std'   => 4,
                    ],

                    'checkbox_radio_options_separator' => [
                        'type' => 'separator',
                    ],

                    'checkbox_radio_options' => [
                        'type'   => 'buttons',
                        'values' => [
                            ['label' => Text::_('COM_SPPAGEBUILDER_GLOBAL_NORMAL'), 'value' => 'normal'],
                            ['label' => Text::_('COM_SPPAGEBUILDER_GLOBAL_ACTIVE'), 'value' => 'checked'],
                        ],
                        'std' => 'normal',
                    ],

                    'checkbox_radio_background_color' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                        'depends' => [
                            ['checkbox_radio_options', '=', 'normal'],
                        ],
                    ],

                    'checkbox_radio_border_separator' => [
                        'type'    => 'separator',
                        'depends' => [
                            ['checkbox_radio_options', '=', 'normal'],
                        ],
                    ],

                    'checkbox_radio_border' => [
                        'type'    => 'border',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                        'depends' => [
                            ['checkbox_radio_options', '=', 'normal'],
                        ],
                    ],

                    'checkbox_radio_background_color_checked' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                        'depends' => [
                            ['checkbox_radio_options', '=', 'checked'],
                        ],
                    ],

                    'checkbox_radio_border_separator_checked' => [
                        'type'    => 'separator',
                        'depends' => [
                            ['checkbox_radio_options', '=', 'checked'],
                        ],
                    ],

                    'checkbox_radio_border_checked' => [
                        'type'    => 'border',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                        'depends' => [
                            ['checkbox_radio_options', '=', 'checked'],
                        ],
                    ],
                ],
            ],

            'input' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_OPTION_INPUT'),
                'fields' => [
                    'input_background' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                    ],

                    'input_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'input_border_separator' => [
                        'type' => 'separator',
                    ],

                    'input_border' => [
                        'type'  => 'border',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                    ],

                    'input_border_radius_separator' => [
                        'type' => 'separator',
                    ],

                    'input_border_radius' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_RADIUS'),
                        'info'  => 'px',
                    ],

                    'input_padding_separator' => [
                        'type' => 'separator',
                    ],

                    'input_padding' => [
                        'type'  => 'padding',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING'),
                    ],
                ],
            ],

            'list_item' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_OPTION_LIST_ITEM'),
                'fields' => [
                    'list_item_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'std'   => '#000000',
                    ],

                    'list_item_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    'list_item_gap' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_GAP'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 16,
                        ],
                        'responsive' => true,
                    ],

                    'list_item_count_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_LIST_COUNT_COLOR'),
                        'std'   => '#000000',
                    ],
                ],
            ],

            'price_range' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_OPTION_PRICE_RANGE'),
                'fields' => [
                    'price_range_buttons' => [
                        'type'   => 'buttons',
                        'values' => [
                            ['label' => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_OPTION_PRICE_RANGE_SLIDER'), 'value' => 'slider'],
                            ['label' => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_OPTION_PRICE_RANGE_SYMBOL'), 'value' => 'symbol'],
                            ['label' => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_OPTION_PRICE_RANGE_SEPARATOR'), 'value' => 'separator'],
                        ],
                        'std' => 'slider',
                    ],

                    'slider_height' => [
                        'type'    => 'slider',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_HEIGHT'),
                        'info'    => 'px',
                        'depends' => [
                            ['price_range_buttons', '=', 'slider'],
                        ],
                    ],

                    'slider_background' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'depends' => [
                            ['price_range_buttons', '=', 'slider'],
                        ],
                    ],

                    'slider_active_background' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR_ACTIVE'),
                        'depends' => [
                            ['price_range_buttons', '=', 'slider'],
                        ],
                    ],

                    'thumb_separator' => [
                        'type'    => 'separator',
                        'title'   => Text::_('COM_EASYSTORE_ADDON_PRODUCT_FILTER_OPTION_PRICE_RANGE_SLIDER_THUMB'),
                        'depends' => [
                            ['price_range_buttons', '=', 'slider'],
                        ],
                    ],

                    'thumb_size' => [
                        'type'    => 'slider',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_SIZE'),
                        'info'    => 'px',
                        'depends' => [
                            ['price_range_buttons', '=', 'slider'],
                        ],
                    ],

                    'thumb_color' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'depends' => [
                            ['price_range_buttons', '=', 'slider'],
                        ],
                    ],

                    // symbol
                    'symbol_color' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'depends' => [
                            ['price_range_buttons', '=', 'symbol'],
                        ],
                    ],

                    'symbol_size' => [
                        'type'    => 'slider',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_SIZE'),
                        'info'    => 'px',
                        'depends' => [
                            ['price_range_buttons', '=', 'symbol'],
                        ],
                    ],

                    'symbol_gap' => [
                        'type'    => 'slider',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_MARGIN'),
                        'info'    => 'px',
                        'depends' => [
                            ['price_range_buttons', '=', 'symbol'],
                        ],
                    ],

                    // separator
                    'range_separator_label' => [
                        'type'    => 'text',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_LABEL'),
                        'depends' => [
                            ['price_range_buttons', '=', 'separator'],
                        ],
                    ],

                    'range_separator_color' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'depends' => [
                            ['price_range_buttons', '=', 'separator'],
                        ],
                    ],
                ],
            ],
        ],
    ]
);


// range slider -> height, background, active background, border color, border radius, handle size, handle border color, background color, border radius

// Dropdown button -> padding, background, border color, border radius, color, typography, caret size, caret color, caret icon, caret spacing // hover & active state of everything
// dropdown menu -> padding, background, border color, border radius, box shadow
