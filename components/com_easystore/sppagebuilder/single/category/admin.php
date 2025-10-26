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
        'addon_name' => 'category',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_CATEGORY'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_SINGLE_PRODUCT'),
        'context'    => 'easystore.single',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path stroke="currentColor" stroke-width="2" d="m14.794 2.706.002-.004a.512.512 0 0 1 .882 0v.003l5.66 9.27.004.008a.52.52 0 0 1-.447.788H9.564a.52.52 0 0 1-.447-.788l.004-.006 5.673-9.271Zm2.969 20.98a5.86 5.86 0 0 1 5.863-5.862 5.86 5.86 0 0 1 5.862 5.863 5.86 5.86 0 0 1-5.862 5.862 5.86 5.86 0 0 1-5.863-5.862Zm-5.575 5.098h-9.15a.53.53 0 0 1-.527-.522v-9.15c0-.286.24-.527.527-.527h9.15a.53.53 0 0 1 .522.526v9.15a.526.526 0 0 1-.522.523Z"/></svg>',
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
