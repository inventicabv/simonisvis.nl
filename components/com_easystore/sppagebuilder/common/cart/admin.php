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
        'addon_name' => 'cart',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_CART'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_COMMON'),
        'context'    => 'easystore.common',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 48 48"><path stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2" d="M11.2 7.94995V24.5499C11.2 26.4499 12.6 27.95 14.3 27.95H34.0999C35.7999 27.95 37.2 26.85 37.7 25.25L43 8.14995"/><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M34.9 33.45H14.3C12.6 33.45 11.2 31.9499 11.2 30.0499V7.94995H5"/><path stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2" d="M17.4 40.05C19.112 40.05 20.5 38.573 20.5 36.75 20.5 34.927 19.112 33.45 17.4 33.45 15.688 33.45 14.3 34.927 14.3 36.75 14.3 38.573 15.688 40.05 17.4 40.05ZM34.9 40.05C36.612 40.05 38 38.573 38 36.75 38 34.927 36.612 33.45 34.9 33.45 33.188 33.45 31.8 34.927 31.8 36.75 31.8 38.573 33.188 40.05 34.9 40.05Z"/><rect width="16.673" height="1.5" x="17.227" y="19.341" fill="currentColor" rx=".75"/><rect width="16.673" height="1.5" x="17.227" y="13.341" fill="currentColor" rx=".75"/></svg>',
        'settings'   => [
            'basic' => [
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
                        'std'   => 'h4',
                    ],

                    'alignment_separator' => [
                        'type' => 'separator',
                    ],

                    'alignment' => [
                        'type'              => 'alignment',
                        'title'             => Text::_('COM_SPPAGEBUILDER_GLOBAL_ALIGNMENT'),
                        'responsive'        => true,
                        'available_options' => ['left', 'center', 'right'],
                        'std'               => [
                            'xl' => 'center',
                            'lg' => '',
                            'md' => '',
                            'sm' => '',
                            'xs' => '',
                        ],
                    ],
                ],
            ],
        ],
    ]
);
