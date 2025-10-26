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
        'addon_name' => 'ratings',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_RATINGS'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_PRODUCT_LIST'),
        'context'    => 'easystore.list',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path stroke="currentColor" stroke-miterlimit="10" stroke-width="2" d="m17.633 9.287 2.8.4c.2 0 .267.266.134.466l-2 1.934c-.067.066-.067.133-.067.2l.467 2.8c.066.2-.2.4-.4.266L16.1 14.02c-.067-.067-.133-.067-.267 0l-2.466 1.333c-.2.134-.4-.066-.4-.266l.466-2.8c0-.067 0-.2-.066-.2l-2-1.934c-.134-.133-.067-.4.133-.466l2.8-.4c.067 0 .133-.067.2-.134l1.267-2.533c.066-.2.4-.2.466 0L17.5 9.153c0 .067.067.134.133.134ZM9.833 19.42l2.8.4c.2 0 .267.267.134.467l-2 1.933c-.067.067-.067.133-.067.2l.467 2.8c.066.2-.2.4-.4.267L8.3 24.153c-.067-.066-.133-.066-.267 0l-2.466 1.334c-.2.133-.4-.067-.4-.267l.466-2.8c0-.067 0-.2-.066-.2l-2-1.933c-.134-.134-.067-.4.133-.467l2.8-.4c.067 0 .133-.067.2-.133l1.267-2.534c.066-.2.4-.2.466 0L9.7 19.287c0 .066.067.133.133.133ZM25.5 19.42l2.8.4c.2 0 .267.267.133.467l-2 1.933c-.066.067-.066.133-.066.2l.466 2.8c.067.2-.2.4-.4.267l-2.466-1.334c-.067-.066-.134-.066-.267 0l-2.467 1.334c-.2.133-.4-.067-.4-.267l.467-2.8c0-.067 0-.2-.067-.2l-2-1.933c-.133-.134-.066-.4.134-.467l2.8-.4c.066 0 .133-.067.2-.133l1.266-2.534c.067-.2.4-.2.467 0l1.267 2.534c-.067.066 0 .133.133.133Z"/></svg>',
        'settings'   => [
            'general' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_GENERAL'),
                'fields' => [
                    'color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'size' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_SIZE'),
                        'min'        => 4,
                        'max'        => 100,
                        'responsive' => true,
                    ],

                    'spacing' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_SPACING'),
                        'min'        => 0,
                        'max'        => 100,
                        'responsive' => true,
                    ],

                    'spacing_separator' => [
                        'type' => 'separator',
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

                    'border_separator' => [
                        'type' => 'separator',
                    ],

                    'border' => [
                        'type'  => 'border',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                    ],

                    'radius' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_RADIUS'),
                        'min'        => 0,
                        'max'        => 100,
                        'responsive' => true,
                    ],
                ],
            ],

            'label_option' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_RATINGS_SHOW_COUNT'),
                'fields' => [
                    'show_count' => [
                        'type'   => 'checkbox',
                        'title'  => Text::_('COM_EASYSTORE_ADDON_RATINGS_SHOW_COUNT'),
                        'values' => [
                            1 => Text::_('YES'),
                            0 => Text::_('NO'),
                        ],
                        'std'       => 1,
                        'is_header' => 1,
                    ],

                    'show_label' => [
                        'type'   => 'checkbox',
                        'title'  => Text::_('COM_EASYSTORE_ADDON_RATINGS_SHOW_LABEL'),
                        'values' => [
                            1 => Text::_('YES'),
                            0 => Text::_('NO'),
                        ],
                        'std'     => 1,
                        'depends' => [
                            ['show_count', '=', 1],
                        ],
                    ],

                    'count_color' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'depends' => [
                            ['show_count', '=', 1],
                        ],
                    ],

                    'count_typography' => [
                        'type'    => 'typography',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                        'depends' => [
                            ['show_count', '=', 1],
                        ],
                    ],

                    'count_spacing' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_SPACING'),
                        'min'        => 0,
                        'max'        => 100,
                        'responsive' => true,
                        'depends'    => [
                            ['show_count', '=', 1],
                        ],
                    ],
                ],
            ],
        ],
    ]
);
