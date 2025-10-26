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
        'addon_name' => 'price',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_PRICE'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_PRODUCT_LIST'),
        'context'    => 'easystore.list',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path fill="currentColor" d="M22.314 2.26a.881.881 0 0 0-.842-.231l-4.736 1.203a.876.876 0 0 0-.406.23L2.26 17.53a.887.887 0 0 0 0 1.248l10.636 10.636c.182.183.438.445.614.519a.882.882 0 0 0 .96-.192l14.07-14.07a.876.876 0 0 0 .231-.407l1.2-4.736a.881.881 0 0 0-.23-.842L22.314 2.26ZM9.204 19.471a.882.882 0 0 1-.625-1.504l6.234-6.234a.88.88 0 0 1 1.248 0 .877.877 0 0 1 0 1.245l-6.234 6.234a.877.877 0 0 1-.624.259Zm2.105 2.102a.882.882 0 0 1-.624-1.504l6.233-6.233a.887.887 0 0 1 1.248 0 .887.887 0 0 1 0 1.248l-6.236 6.23a.87.87 0 0 1-.621.26Zm8.96-4.39-6.237 6.237a.877.877 0 0 1-1.248 0 .88.88 0 0 1 0-1.248l6.234-6.234a.887.887 0 0 1 1.248 0 .88.88 0 0 1 .003 1.245Zm3.856-5.206a2.886 2.886 0 0 1-2.051.85 2.88 2.88 0 0 1-2.055-.85 2.91 2.91 0 0 1 0-4.106 2.89 2.89 0 0 1 2.055-.848c.774 0 1.504.3 2.05.848a2.905 2.905 0 0 1 0 4.106Z"/></svg>',
        'settings'   => [
            'basic' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_GENERAL'),
                'fields' => [
                    'spacing' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_SPACING'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 8,
                        ],
                    ],

                    'align_items' => [
                        'type'       => 'buttons',
                        'title'      => Text::_("COM_SPPAGEBUILDER_GLOBAL_ALIGNMENT"),
                        'responsive' => true,
                        'values'     => [
                            ['label' => ['icon' => 'alignStart'], 'value' => 'flex-start'],
                            ['label' => ['icon' => 'alignCenter'], 'value' => 'center'],
                            ['label' => ['icon' => 'alignEnd'], 'value' => 'flex-end'],
                            ['label' => ['icon' => 'alignStretch'], 'value' => 'stretch'],
                        ],
                    ],

                    'padding' => [
                        'type'       => 'padding',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING'),
                        'responsive' => true,
                    ],

                    'margin' => [
                        'type'       => 'margin',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_MARGIN'),
                        'responsive' => true,
                    ],
                ],
            ],

            'price' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_OPTION_PRICE'),
                'fields' => [
                    'price_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'price_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    'symbol_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_EASYSTORE_ADDON_PRICE_OPTION_SYMBOL'),
                    ],

                    'symbol_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'symbol_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    'symbol_align_self' => [
                        'type'   => 'buttons',
                        'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_ALIGNMENT'),
                        'values' => [
                            ['label' => ['icon' => 'alignStart'], 'value' => 'flex-start'],
                            ['label' => ['icon' => 'alignCenter'], 'value' => 'center'],
                            ['label' => ['icon' => 'alignEnd'], 'value' => 'flex-end'],
                        ],
                    ],

                    'symbol_spacing' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_SPACING'),
                        'min'   => 0,
                        'max'   => 20,
                    ],

                    'decimal_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_EASYSTORE_ADDON_PRICE_OPTION_DECIMAL'),
                    ],

                    'decimal_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'decimal_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    'decimal_align_self' => [
                        'type'   => 'buttons',
                        'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_ALIGNMENT'),
                        'values' => [
                            ['label' => ['icon' => 'alignStart'], 'value' => 'flex-start'],
                            ['label' => ['icon' => 'alignCenter'], 'value' => 'center'],
                            ['label' => ['icon' => 'alignEnd'], 'value' => 'flex-end'],
                        ],
                    ],
                ],
            ],

            'original_price' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_PRICE_OPTION_ORIGINAL_PRICE'),
                'fields' => [
                    'original_price_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'original_price_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    'original_symbol_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_EASYSTORE_ADDON_PRICE_OPTION_SYMBOL'),
                    ],

                    'original_symbol_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'original_symbol_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    'original_symbol_align_self' => [
                        'type'   => 'buttons',
                        'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_ALIGNMENT'),
                        'values' => [
                            ['label' => ['icon' => 'alignStart'], 'value' => 'flex-start'],
                            ['label' => ['icon' => 'alignCenter'], 'value' => 'center'],
                            ['label' => ['icon' => 'alignEnd'], 'value' => 'flex-end'],
                        ],
                    ],

                    'original_symbol_spacing' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_SPACING'),
                        'min'   => 0,
                        'max'   => 20,
                    ],

                    'original_decimal_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_EASYSTORE_ADDON_PRICE_OPTION_DECIMAL'),
                    ],

                    'original_decimal_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'original_decimal_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    'original_decimal_align_self' => [
                        'type'   => 'buttons',
                        'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_ALIGNMENT'),
                        'values' => [
                            ['label' => ['icon' => 'alignStart'], 'value' => 'flex-start'],
                            ['label' => ['icon' => 'alignCenter'], 'value' => 'center'],
                            ['label' => ['icon' => 'alignEnd'], 'value' => 'flex-end'],
                        ],
                    ],

                    'original_stroke_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_EASYSTORE_ADDON_PRICE_OPTION_STROKE'),
                    ],

                    'original_stroke_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_EASYSTORE_ADDON_PRICE_OPTION_STROKE_COLOR'),
                        'std'   => '#E14848',
                    ],

                    'original_stroke_thickness' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_EASYSTORE_ADDON_PRICE_OPTION_STROKE_THICKNESS'),
                        'step'  => 0.1,
                        'min'   => 0.5,
                        'max'   => 5,
                        'std'   => 1.6,
                    ],

                    'original_stroke_angle' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_EASYSTORE_ADDON_PRICE_OPTION_STROKE_ANGLE'),
                        'min'   => -360,
                        'max'   => 360,
                        'info'  => 'deg',
                        'std'   => -15,
                    ],
                ],
            ],
            'tax_label' => [
                'title' => Text::_('COM_EASYSTORE_ADDON_OPTION_TAX_LABEL'),
                'fields' => [
                    'tax_label_custom_text' => [
                        'type'  => 'text',
                        'title' => Text::_('COM_EASYSTORE_ADDON_PRICE_OPTION_TAX_TITLE_TEXT'),
                        'desc'  => Text::_('COM_EASYSTORE_ADDON_PRICE_OPTION_TAX_DESC'),
                    ],
                    'tax_label_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'tax_label_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],
                ]
            ],
        ],
    ]
);
