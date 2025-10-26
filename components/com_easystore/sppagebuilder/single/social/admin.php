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
        'addon_name' => 'social',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_SOCIAL'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_SINGLE_PRODUCT'),
        'context'    => 'easystore.single',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path stroke="currentColor" stroke-miterlimit="10" stroke-width="2" d="M23.936 18.082c1.557-1.115.802-4.834-1.686-8.306-2.487-3.472-5.765-5.382-7.322-4.267-1.556 1.115-.801 4.833 1.686 8.305 2.488 3.472 5.766 5.383 7.322 4.268ZM10.58 14.86l2.933 4.333-2.133 1.467c-1.2.8-2.8.533-3.667-.667-.8-1.2-.533-2.8.667-3.666l2.2-1.467Z"/><path stroke="currentColor" stroke-miterlimit="10" stroke-width="2" d="m11.247 20.727 4 5.533c.466.6 1.266.733 1.866.267.6-.467.734-1.267.267-1.867l-3.933-5.467 9.266-.733M10.58 14.86l3.6-8.067"/><path stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2" d="m16.58 10.86.667-.533c1-.734 2.4-.534 3.133.466.733 1 .533 2.4-.467 3.134l-.666.533"/></svg>',
        'settings'   => [
            'basic' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_GENERAL'),
                'fields' => [
                    'spacing' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_GAP'),
                        'min'        => 0,
                        'max'        => 100,
                        'std'        => ['xl' => 16],
                        'responsive' => true,
                    ],

                    'justify_content' => [
                        'type'       => 'buttons',
                        'title'      => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_FLEX_JUSTIFY"),
                        'std'        => ['xl' => 'center', 'lg' => '', 'md' => '', 'sm' => '', 'xs' => ''],
                        'responsive' => true,
                        'values'     => [
                            [
                                'label' => [
                                    'tooltip' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_FLEX_START"),
                                    'icon'    => 'justifyStart',
                                ],
                                'value' => 'flex-start',
                            ],
                            [
                                'label' => [
                                    'tooltip' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_FLEX_END"),
                                    'icon'    => 'justifyEnd',
                                ],
                                'value' => 'flex-end',
                            ],
                            [
                                'label' => [
                                    'tooltip' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_FLEX_CENTER"),
                                    'icon'    => 'justifyCenter',
                                ],
                                'value' => 'center',
                            ],
                            [
                                'label' => [
                                    'tooltip' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_SPACE_BETWEEN"),
                                    'icon'    => 'justifySpaceBetween',
                                ],
                                'value' => 'space-between',
                            ],
                            [
                                'label' => [
                                    'tooltip' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_SPACE_AROUND"),
                                    'icon'    => 'justifySpaceAround',
                                ],
                                'value' => 'space-around',
                            ],
                            [
                                'label' => [
                                    'tooltip' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_SPACE_EVENLY"),
                                    'icon'    => 'justifySpaceEvenly',
                                ],
                                'value' => 'space-evenly',
                            ],
                        ],
                    ],
                ],
            ],

            'normal' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_NORMAL'),
                'fields' => [
                    'size' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_ICON_SIZE'),
                        'min'        => 4,
                        'max'        => 100,
                        'responsive' => true,
                    ],

                    'color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'background' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                    ],

                    'width_separator' => [
                        'type' => 'separator',
                    ],

                    'width' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_WIDTH'),
                        'min'        => 4,
                        'max'        => 100,
                        'responsive' => true,
                    ],

                    'height' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_HEIGHT'),
                        'min'        => 4,
                        'max'        => 100,
                        'responsive' => true,
                    ],

                    'radius_separator' => [
                        'type' => 'separator',
                    ],

                    'radius' => [
                        'type'       => 'advancedslider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_RADIUS'),
                        'responsive' => true,
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
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                    ],
                ],
            ],
        ],
    ],
);
