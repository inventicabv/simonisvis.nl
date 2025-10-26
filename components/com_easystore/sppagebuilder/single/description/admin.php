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
        'addon_name' => 'description',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_DESCRIPTION'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_SINGLE_PRODUCT'),
        'context'    => 'easystore.single',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2" d="M11.733 5.333H5.667v9.134h6.066V5.333ZM15.667 7.667h10.666M15.667 12.133h10.666M11.733 17.533H5.667v9.134h6.066v-9.134ZM15.667 19.8h10.666M15.667 24.333h10.666"/></svg>',
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
        ],
    ],
);
