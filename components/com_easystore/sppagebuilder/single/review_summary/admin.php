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
        'addon_name' => 'review_summary',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_REVIEW_SUMMARY'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_SINGLE_PRODUCT'),
        'context'    => 'easystore.single',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path stroke="currentColor" stroke-miterlimit="10" stroke-width="2" d="M26 6.033H6v19.934h20V6.033Z"/><path fill="#676767" stroke="currentColor" stroke-miterlimit="10" d="m16.733 11.1 1.6.2-1.133 1.267.267 1.733L16 13.5l-1.4.8.267-1.733-1.2-1.267 1.6-.2L16 9.5l.733 1.6ZM11.2 11.1l1.667.2-1.2 1.267L12 14.3l-1.467-.8-1.466.8.266-1.733-1.2-1.267 1.667-.2.667-1.6.733 1.6ZM22.267 11.1l1.6.2-1.134 1.267L23 14.3l-1.467-.8-1.4.8.267-1.733-1.2-1.267 1.6-.2.733-1.6.734 1.6Z"/><path stroke="currentColor" stroke-miterlimit="10" stroke-width="2" d="M8.533 18.767h14.934M8.533 22.033h14.934"/></svg>',
        'settings'   => [
            'ratings' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_OPTION_RATING'),
                'fields' => [
                    'align_items' => [
                        'type'       => 'buttons',
                        'title'      => Text::_("COM_SPPAGEBUILDER_GLOBAL_ALIGNMENT"),
                        'responsive' => true,
                        'values'     => [
                            [
                                'label' => [
                                    'tooltip' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_ALIGN_START"),
                                    'icon'    => 'alignStart',
                                ],
                                'value' => 'flex-start',
                            ],
                            [
                                'label' => [
                                    'tooltip' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_ALIGN_CENTER"),
                                    'icon'    => 'alignCenter',
                                ],
                                'value' => 'center',
                            ],
                            [
                                'label' => [
                                    'tooltip' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_ALIGN_END"),
                                    'icon'    => 'alignEnd',
                                ],
                                'value' => 'flex-end',
                            ],
                            [
                                'label' => [
                                    'tooltip' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_ALIGN_STRETCH"),
                                    'icon'    => 'alignStretch',
                                ],
                                'value' => 'stretch',
                            ],
                        ],
                    ],

                    'gap' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_GAP'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 4,
                        ],
                    ],

                    'rating_count_separator' => [
                        'type' => 'separator',
                    ],

                    'count_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'count_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    'rating_total_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_EASYSTORE_ADDON_OPTION_RATING_TOTAL'),
                    ],

                    'total_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'total_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    'rating_spacing_separator' => [
                        'type' => 'separator',
                    ],

                    'spacing' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_SPACING'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 8,
                        ],
                    ],
                ],
            ],

            'stars' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_OPTION_RATING_STARS'),
                'fields' => [
                    'stars_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'std'   => '#f7d000',
                    ],

                    'stars_size' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_SIZE'),
                        'min'        => 0,
                        'max'        => 100,
                        'std'        => ['xl' => 32],
                        'responsive' => true,
                    ],

                    'stars_gap' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_GAP'),
                        'min'        => 0,
                        'max'        => 100,
                        'std'        => ['xl' => 5],
                        'responsive' => true,
                    ],

                    'stars_spacing_separator' => [
                        'type' => 'separator',
                    ],

                    'stars_spacing' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_SPACING'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 16,
                        ],
                    ],
                ],
            ],

            'content' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_OPTION_RATING_CONTENT'),
                'fields' => [
                    'content_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'content_typography' => [
                        'type'       => 'typography',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_SIZE'),
                        'responsive' => true,
                    ],
                ],
            ],
        ],
    ]
);
