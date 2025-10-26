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
        'addon_name' => 'dimension',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_DIMENSION'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_SINGLE_PRODUCT'),
        'context'    => 'easystore.single',
        'icon'       => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19.7205 14.7093L16.0524 12.3817C15.8777 12.2767 15.6507 12.2592 15.4585 12.3642C15.2664 12.4692 15.1616 12.6617 15.1616 12.8718V14.4293H9.56195V8.83456H11.1165C11.3261 8.83456 11.5183 8.71206 11.6231 8.53705C11.7279 8.34454 11.7104 8.11704 11.6056 7.94203L9.28247 4.24938C9.07287 3.91687 8.51392 3.91687 8.30431 4.24938L5.98116 7.94203C5.87636 8.11704 5.85889 8.34454 5.9637 8.53705C6.0685 8.72956 6.26064 8.83456 6.47025 8.83456H8.02483V14.4293H4.76856C4.34934 14.4293 4 14.7793 4 15.1993C4 15.6194 4.34934 15.9694 4.76856 15.9694H8.02483V19.23C8.02483 19.65 8.37418 20 8.79339 20C9.2126 20 9.56195 19.65 9.56195 19.23V15.9694H15.1441V17.5269C15.1441 17.7369 15.2664 17.9295 15.441 18.0345C15.5284 18.087 15.6332 18.1045 15.7205 18.1045C15.8253 18.1045 15.9301 18.0695 16.0349 18.017L19.7205 15.6894C19.8952 15.5844 20 15.3919 20 15.1993C20 15.0068 19.8952 14.8143 19.7205 14.7093Z" fill="#6F7CA3"/>
                        </svg>',
        'settings' => [
            'basic' => [
                'fields' => [

                    // Dimension Title
                    'title_admin_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_EASYSTORE_APP_TITLE'),
                    ],
                    'title_text' => [
                        'type'  => 'text',
                        'title' => Text::_('COM_EASYSTORE_ADDON_DIMENSION_TITLE'),
                        'std'   => '',
                    ],

                    'title_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'title_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    // Dimension Value
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

                    // Dimension Separator
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
