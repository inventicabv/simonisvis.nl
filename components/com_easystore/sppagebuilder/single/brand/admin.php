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
        'addon_name' => 'brand',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_BRAND'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_SINGLE_PRODUCT'),
        'context'    => 'easystore.single',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path fill="#6F7CA3" fill-rule="evenodd" d="M5.05 17.8c0 .6.5 1.1 1.1 1.1h11.6c.6 0 1.1-.5 1.1-1.1V6.2c0-.6-.5-1.1-1.1-1.1H6.2c-.6 0-1.1.5-1.1 1.1v11.6h-.05Zm1.15 2.1c-1.15 0-2.1-.95-2.1-2.1V6.2c0-1.2.9-2.1 2.1-2.1h11.6c1.15 0 2.1.95 2.1 2.1v11.6c0 1.15-.95 2.1-2.1 2.1H6.2Z" clip-rule="evenodd"/><path fill="#6F7CA3" d="M11.97 6.474c.16 0 .346.075.428.255l1.406 2.844 3.132.448.008.002.008.001c.16.04.32.133.371.338a.661.661 0 0 1-.044.432l-.012.022-.018.018-2.27 2.205.542 3.121.012.075a.467.467 0 0 1-.205.414l-.008.005-.008.004c-.056.028-.156.082-.263.082-.03 0-.075 0-.116-.006a.287.287 0 0 1-.128-.049l-2.803-1.465-2.813 1.47-.003.001a.502.502 0 0 1-.51-.042.462.462 0 0 1-.196-.478l.404-3.195-2.13-2.142c-.148-.115-.178-.31-.138-.472.052-.205.21-.297.371-.338l.007-.001.008-.002 3.13-.448L11.54 6.73c.082-.18.27-.255.43-.255Zm-1.155 3.785-.017.017c-.032.032-.081.08-.137.122a.415.415 0 0 1-.211.082v.002l-2.452.35 1.75 1.72.002.002.067.084a.57.57 0 0 1 .053.102c.025.065.04.151.018.239h.001L9.481 15.4l2.19-1.137.002-.002.062-.027a.38.38 0 0 1 .063-.015c.038-.007.076-.006.106-.006s.074-.001.115.005a.287.287 0 0 1 .127.05l2.18 1.132-.408-2.42-.002-.013v-.013c0-.064.01-.137.031-.204a.48.48 0 0 1 .11-.196l.002-.002 1.778-1.72-2.448-.35c-.166-.006-.316-.093-.376-.248L11.934 8.02l-1.119 2.239Z"/></svg>',
        'settings'   => [
            'features' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_PRODUCT_BRAND'),
                'fields' => [   
                    'show_brand_label'  => [
                        'type'      => 'checkbox',
                        'title'     => Text::_('COM_EASYSTORE_ADDON_PRODUCT_BRAND_LABEL'),
                        'std'       => 1,
                    ],   
                    'brand_separator_2' => [
                        'type'      => 'separator',
                    ],
                    'show_brand_name'  => [
                        'type'      => 'checkbox',
                        'title'     => Text::_('COM_EASYSTORE_ADDON_PRODUCT_BRAND_NAME'),
                        'std'       => 1,
                    ],
                    'show_brand_link'  => [
                        'type'      => 'checkbox',
                        'title'     => Text::_('COM_EASYSTORE_ADDON_PRODUCT_BRAND_LINK'),
                        'std'       => 1,
                    ],
                    'brand_separator' => [
                        'type'      => 'separator',
                    ],
                    'show_brand_image' => [
                        'type'      => 'checkbox',
                        'title'     => Text::_('COM_EASYSTORE_ADDON_PRODUCT_BRAND_IMAGE'),
                        'std'       => 1,
                    ],
                    'brand_image_height' => [
                        'type'      => 'slider',
                        'title'     => Text::_('COM_EASYSTORE_ADDON_PRODUCT_BRAND_IMAGE_HEIGHT'),
                        'std'       => 50,
                        'responsive' => true,
                        'description' => Text::_('COM_EASYSTORE_ADDON_PRODUCT_BRAND_IMAGE_HEIGHT_DESC'),
                    ],
                    'brand_image_width' => [
                        'type'      => 'slider',
                        'title'     => Text::_('COM_EASYSTORE_ADDON_PRODUCT_BRAND_IMAGE_WIDTH'),
                        'std'       => 50,
                        'responsive' => true,
                        'description' => Text::_('COM_EASYSTORE_ADDON_PRODUCT_BRAND_IMAGE_WIDTH_DESC'),
                    ],
                    'brand_image_radius' => [
                        'type'      => 'slider',
                        'title'     => Text::_('COM_EASYSTORE_ADDON_PRODUCT_BRAND_IMAGE_RADIUS'),
                        'std'       => 0,
                        'responsive' => true,
                        'description' => Text::_('COM_EASYSTORE_ADDON_PRODUCT_BRAND_IMAGE_RADIUS_DESC'),
                    ],
                ],
            ],
        ],
        'attr'       => [],
    ]
);
