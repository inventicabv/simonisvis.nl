<?php

/**
 * @package SP Page Builder
 * @author JoomShaper https://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;

SpAddonsConfig::addonConfig(
    [
        'type'       => 'productCommon',
        'addon_name' => 'search',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_SEARCH'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_COMMON'),
        'context'    => 'easystore.common',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path fill="currentColor" d="M29.8665 25.978L23.1342 19.2428C22.1306 20.8041 20.8041 22.1306 19.2428 23.1342L25.978 29.8665C27.0521 30.9435 28.7953 30.9435 29.8665 29.8665C30.9435 28.7953 30.9435 27.0521 29.8665 25.978ZM23.3367 12.3316C23.3367 6.25379 18.4094 1.32642 12.3316 1.32642C6.25379 1.32642 1.32642 6.25379 1.32642 12.3316C1.32642 18.4094 6.25379 23.3367 12.3316 23.3367C18.4094 23.3367 23.3367 18.4094 23.3367 12.3316ZM12.3316 20.584C7.77984 20.584 4.07624 16.8833 4.07624 12.3316C4.07624 7.77984 7.77984 4.07624 12.3316 4.07624C16.8833 4.07624 20.5869 7.77984 20.5869 12.3316C20.5869 16.8833 16.8833 20.584 12.3316 20.584ZM5.91337 12.3316H7.74756C7.74756 9.80186 9.80186 7.74756 12.3316 7.74756V5.91337C8.79232 5.91337 5.91337 8.79232 5.91337 12.3316Z"/></svg>',
        'settings'   => [
            'input' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_PRODUCT_SEARCH_INPUT'),
                'fields' => [
                    'color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'background_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
                    ],

                    'border' => [
                        'type'  => 'border',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                    ],

                    'border_radius_separator' => [
                        'type' => 'separator',
                    ],

                    'border_radius' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_RADIUS'),
                        'max'        => 100,
                        'responsive' => true,
                    ],

                    'typography_separator' => [
                        'type' => 'separator',
                    ],

                    'typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    'padding_separator' => [
                        'type' => 'separator',
                    ],

                    'padding' => [
                        'type'       => 'padding',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING'),
                        'responsive' => true,
                    ],
                ],
            ],

            'icon' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_ICON'),
                'fields' => [
                    'icon_size' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_SIZE'),
                        'max'        => 48,
                        'responsive' => true,
                    ],

                    'icon_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'icon_spacing' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_SPACING'),
                        'max'        => 48,
                        'responsive' => true,
                    ],
                ],
            ],

            'placeholder' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_PLACEHOLDER'),
                'fields' => [
                    'placeholder' => [
                        'type'  => 'text',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_PLACEHOLDER'),
                    ],

                    'placeholder_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],
                ],
            ],
        ],
    ]
);
