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
        'addon_name' => 'reviews',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_REVIEWS'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_SINGLE_PRODUCT'),
        'context'    => 'easystore.single',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path stroke="currentColor" stroke-miterlimit="10" stroke-width="2" d="M22.167 13.62V6.887c0-.667-.534-1.2-1.2-1.2h-14.4c-.667 0-1.2.533-1.2 1.2v11.466c0 .667.533 1.2 1.2 1.2h1.2c.2 0 .333.134.333.334v2.6c0 .133.133.2.2.066l2.533-2.8c.134-.2.467-.133.467-.133h3.267"/><path stroke="currentColor" stroke-miterlimit="10" stroke-width="2" d="M25.7 13.62h-3.467v4.733c0 .667-.533 1.2-1.2 1.2h-6.4v3.534c0 .533.4.933.867.933h6.867s.2 0 .333.133l1.8 2.134c.067.066.133 0 .133-.067v-1.933c0-.134.134-.267.267-.267h.867c.466 0 .866-.4.866-.933v-8.6c-.066-.467-.466-.867-.933-.867Z"/><path stroke="currentColor" stroke-miterlimit="10" stroke-width="2" d="M14.567 19.62h6.4c.666 0 1.2-.533 1.2-1.2v-4.733M13.767 17.487a4.667 4.667 0 1 0 0-9.334 4.667 4.667 0 0 0 0 9.334Z"/><path stroke="currentColor" stroke-miterlimit="10" stroke-width="2" d="M15.367 13.753c0 .8-.6 1.4-1.4 1.4-.8 0-1.4-.6-1.4-1.4"/></svg>',
        'settings'   => [
            'basic' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_OPTION_REVIEW'),
                'fields' => [
                    'background' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                    ],

                    'padding' => [
                        'type'       => 'padding',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING'),
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

                    'spacing_separator' => [
                        'type' => 'separator',
                    ],

                    'spacing' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_SPACING'),
                        'min'        => 0,
                        'max'        => 100,
                        'std'        => ['xl' => 32],
                        'responsive' => true,
                    ],

                    'alignment_separator' => [
                        'type' => 'separator',
                    ],

                    'alignment' => [
                        'type'              => 'alignment',
                        'title'             => Text::_('COM_SPPAGEBUILDER_GLOBAL_ALIGNMENT'),
                        'responsive'        => true,
                        'available_options' => ['left', 'center', 'right'],
                    ],
                ],
            ],

            'subject' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_OPTION_SUBJECT'),
                'fields' => [
                    'subject_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'subject_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    'subject_margin_separator' => [
                        'type' => 'separator',
                    ],

                    'subject_margin' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_SIZE'),
                        'min'        => 0,
                        'max'        => 100,
                        'std'        => ['xl' => 16],
                        'responsive' => true,
                    ],
                ],
            ],

            'ratings' => [
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
                        'std'        => ['xl' => 20],
                        'responsive' => true,
                    ],

                    'stars_spacing' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_SPACING'),
                        'min'        => 0,
                        'max'        => 100,
                        'std'        => ['xl' => 5],
                        'responsive' => true,
                    ],

                    'stars_margin_separator' => [
                        'type' => 'separator',
                    ],

                    'stars_margin' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_MARGIN'),
                        'min'        => 0,
                        'max'        => 100,
                        'std'        => ['xl' => 8],
                        'responsive' => true,
                    ],
                ],
            ],

            'message' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_OPTION_MESSAGE'),
                'fields' => [
                    'message_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'message_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    'message_margin_separator' => [
                        'type' => 'separator',
                    ],

                    'message_margin' => [
                        'type'       => 'slider',
                        'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_MARGIN'),
                        'min'        => 0,
                        'max'        => 100,
                        'std'        => ['xl' => 24],
                        'responsive' => true,
                    ],
                ],
            ],

            'author' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_OPTION_AUTHOR'),
                'fields' => [
                    'author_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'author_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],
                ],
            ],
        ],
    ],
);
