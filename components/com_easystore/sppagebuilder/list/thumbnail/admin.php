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
        'addon_name' => 'thumbnail',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_THUMBNAIL'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_PRODUCT_LIST'),
        'context'    => 'easystore.list',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2" d="M23.2 24H8.8A2.817 2.817 0 0 1 6 21.2V10.8C6 9.267 7.267 8 8.8 8h14.4c1.533 0 2.8 1.267 2.8 2.8v10.4c0 1.533-1.267 2.8-2.8 2.8Z"/><path stroke="currentColor" stroke-linecap="square" stroke-miterlimit="10" stroke-width="2" d="M6.667 22 9.2 18.733c.2-.266.533-.266.8-.066L13.2 22c.267.2.667.2.867-.133l5.2-5.867c.2-.267.533-.267.8-.067L25.4 22.6"/><path stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2" d="M12.733 16a2.067 2.067 0 1 0 0-4.133 2.067 2.067 0 0 0 0 4.133Z"/></svg>',
        'settings'   => [
            'general' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_GENERAL'),
                'fields' => [
                    'toggle_image' => [
                        'type'  => 'checkbox',
                        'title' => Text::_('COM_EASYSTORE_ADDON_THUMBNAIL_OPTION_TOGGLE_IMAGE'),
                        'std'   => 0,
                    ],

                    'alt_text' => [
                        'type'  => 'text',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_ALT_TEXT'),
                    ],

                    'show_link' => [
                        'type'  => 'checkbox',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_LINK'),
                        'std'   => 1,
                    ],

                    'background_color_separator' => [
                        'type' => 'separator',
                    ],

                    'background_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
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
            'effects' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_EFFECTS'),
                'fields' => [
                    'is_effects_enabled' => [
                        'type'      => 'checkbox',
                        'title'     => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_EFFECTS'),
                        'std'       => 0,
                        'is_header' => 1,
                    ],

                    'image_effects' => [
                        'type'    => 'effects',
                        'depends' => [
                            ['is_effects_enabled', '=', 1],
                        ],
                    ],
                ],
            ],
        ],
    ]
);
