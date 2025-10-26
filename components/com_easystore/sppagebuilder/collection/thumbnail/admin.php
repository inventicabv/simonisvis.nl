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
        'type'       => 'productCollection',
        'addon_name' => 'thumbnail',
        'title'      => Text::_('COM_EASYSTORE_COLLECTION_ADDON_THUMBNAIL'),
        'desc'       => Text::_('COM_EASYSTORE_COLLECTION_ADDON_THUMBNAIL_DESC'),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_COLLECTION'),
        'context'    => 'easystore.collection',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2" d="M23.2 24H8.8A2.817 2.817 0 0 1 6 21.2V10.8C6 9.267 7.267 8 8.8 8h14.4c1.533 0 2.8 1.267 2.8 2.8v10.4c0 1.533-1.267 2.8-2.8 2.8Z"/><path stroke="currentColor" stroke-linecap="square" stroke-miterlimit="10" stroke-width="2" d="M6.667 22 9.2 18.733c.2-.266.533-.266.8-.066L13.2 22c.267.2.667.2.867-.133l5.2-5.867c.2-.267.533-.267.8-.067L25.4 22.6"/><path stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2" d="M12.733 16a2.067 2.067 0 1 0 0-4.133 2.067 2.067 0 0 0 0 4.133Z"/></svg>',
        'settings'   => [
            'general' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_GENERAL'),
                'fields' => [
                    'width' => [
                        'type' => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_WIDTH'),
                        'max' => 2000,
                        'min' => 0,
                        'responsive' => true,
                    ],

                    'height' => [
                        'type' => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_HEIGHT'),
                        'max' => 2000,
                        'min' => 0,
                        'responsive' => true,
                    ],

                    'image_fit' => [
                        'type'  => 'select',
                        'title' => Text::_('COM_EASYSTORE_COLLECTION_THUMBNAIL_IMAGE_FIT'),
                        'std'   => 'cover',
                        'values' => [
                            'none'          => Text::_('COM_EASYSTORE_COLLECTION_THUMBNAIL_IMAGE_FIT_NONE'),
                            'cover'         => Text::_('COM_EASYSTORE_COLLECTION_THUMBNAIL_IMAGE_FIT_COVER'),
                            'contain'       => Text::_('COM_EASYSTORE_COLLECTION_THUMBNAIL_IMAGE_FIT_CONTAIN'),
                            'fill'          => Text::_('COM_EASYSTORE_COLLECTION_THUMBNAIL_IMAGE_FIT_FILL'),
                            'scale-down'    => Text::_('COM_EASYSTORE_COLLECTION_THUMBNAIL_IMAGE_FIT_SCALE_DOWN'),
                        ],
                    ],
                    'aspect_ratio' => [
                        'type' => 'select',
                        'title' => Text::_('COM_EASYSTORE_COLLECTION_THUMBNAIL_ASPECT_RATIO'),
                        'values' => [
                            'none'   => Text::_('COM_EASYSTORE_COLLECTION_THUMBNAIL_ASPECT_RATIO_NONE'),
                            '16/9'   => Text::_('COM_EASYSTORE_COLLECTION_THUMBNAIL_ASPECT_RATIO_WIDESCREEN'),
                            '4/3'    => Text::_('COM_EASYSTORE_COLLECTION_THUMBNAIL_ASPECT_RATIO_STANDARD'),
                            '1/1'    => Text::_('COM_EASYSTORE_COLLECTION_THUMBNAIL_ASPECT_RATIO_SQUARE'),
                            '3/2'    => Text::_('COM_EASYSTORE_COLLECTION_THUMBNAIL_ASPECT_RATIO_PHOTOGRAPHY'),
                            '9/16'   => Text::_('COM_EASYSTORE_COLLECTION_THUMBNAIL_ASPECT_RATIO_PORTRAIT'),
                            '21/9'   => Text::_('COM_EASYSTORE_COLLECTION_THUMBNAIL_ASPECT_RATIO_ULTRA_WIDE'),
                            'custom' => Text::_('COM_EASYSTORE_COLLECTION_THUMBNAIL_ASPECT_RATIO_CUSTOM'),
                        ],
                    ],
                    'custom_aspect_ratio' => [
                        'type' => 'text',
                        'title' => Text::_('COM_EASYSTORE_COLLECTION_THUMBNAIL_CUSTOM_ASPECT_RATIO'),
                        'placeholder' => 'e.g. 16/9',
                        'depends' => [
                            ['aspect_ratio', '=', 'custom'],
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
