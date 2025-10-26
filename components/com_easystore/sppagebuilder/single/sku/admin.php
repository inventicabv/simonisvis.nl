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
        'addon_name' => 'sku',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_SKU'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_SINGLE_PRODUCT'),
        'context'    => 'easystore.single',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path fill="currentColor" d="M27.7472 30.9888L19.6864 22.9664L17.7408 26.6112C17.5808 26.896 17.296 27.1392 16.9728 27.1808C16.4448 27.2608 15.9583 26.9376 15.8368 26.4512L12.3936 13.6097C12.3136 13.2449 12.3936 12.8801 12.6784 12.6369C12.9216 12.3937 13.328 12.272 13.6512 12.3937L26.3712 16C26.6944 16.08 26.976 16.3232 27.0977 16.6497C27.3025 17.1328 27.0592 17.6608 26.6112 17.904L22.8864 19.808L30.9056 27.8689C31.7984 28.7584 31.7984 30.176 30.9056 31.0272C30.0544 31.8784 28.5984 31.8784 27.7472 30.9888Z"/><path fill="currentColor" d="M11.8688 0C17.9456 0 23.008 4.53762 23.696 10.5728L23.7056 10.6848C23.7216 11.2064 23.3344 11.6672 22.8064 11.7056H22.7648C22.2368 11.7472 21.7504 11.3408 21.6704 10.8161V10.7744C20.8608 3.68644 12.6369 -0.97275 5.22563 4.29444C5.14563 4.33287 5.06244 4.37444 5.02406 4.45444C-1.53912 11.9904 3.64487 21.1456 11.0976 21.6289L11.2256 21.6544C11.7248 21.7793 12.0672 22.2016 12.0288 22.7232V22.7649C11.9904 23.3313 11.504 23.7377 10.9376 23.6961L10.6304 23.6705C4.66244 23.0753 0 17.9233 0 11.8689C0 5.34725 5.30556 6.25e-05 11.8688 6.25e-05V0ZM10.1664 4.81919C13.2864 3.97119 16.4447 5.42719 17.9456 8.18237L17.9999 8.29756C18.1951 8.75838 17.9871 9.29913 17.5391 9.55838L17.4239 9.616C16.9631 9.81119 16.4223 9.60319 16.1631 9.15519L16.0479 8.95681C14.9984 7.21919 12.9664 6.256 10.8544 6.72325C9.27675 7.04962 7.98081 8.18244 7.41119 9.68006C6.44163 12.1921 7.696 14.8672 9.96475 15.8784L10.0736 15.9361C10.5248 16.2017 10.7167 16.7265 10.4896 17.2161L10.4352 17.3249C10.1664 17.7729 9.64475 17.9681 9.15519 17.7409L8.93437 17.6384C5.95194 16.1952 4.28156 12.7169 5.38875 9.31525C6.07675 7.12969 7.89756 5.38881 10.1664 4.81919Z"/></svg>',
        'settings'   => [
            'basic' => [
                'fields' => [

                    // SKU Title
                    'title_admin_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_EASYSTORE_APP_TITLE'),
                    ],

                    'title_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'title_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    // SKU Value
                    'value_admin_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_EASYSTORE_ADDON_VALUE'),
                    ],

                    'value_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'value_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    // SKU Separator
                    'title_value_admin_separator' => [
                        'type' => 'separator',
                    ],

                    'title_value_separator' => [
                        'type'  => 'text',
                        'title' => Text::_('COM_EASYSTORE_ADDON_SEPARATOR'),
                        'std'   => ':',
                    ],

                    'title_value_separator_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'title_value_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],
                ],
            ],
        ],
    ],
);
