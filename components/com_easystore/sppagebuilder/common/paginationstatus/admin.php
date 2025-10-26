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
        'type'       => 'productCommon',
        'addon_name' => 'paginationstatus',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PAGINATION_STATUS_TITLE'),
        'desc'       => Text::_('COM_EASYSTORE_ADDON_PAGINATION_STATUS_DESC'),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_COMMON'),
        'context'    => 'easystore.common',
        'icon'       => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7 8.563v6.896c0 .5-.494.7-.79.4l-3.062-3.498c-.197-.2-.197-.6 0-.8L6.21 8.065c.296-.2.79.1.79.5Zm4.175 3.525a1.088 1.088 0 1 1-2.175 0 1.088 1.088 0 0 1 2.175 0Zm4 0a1.088 1.088 0 1 1-2.175 0 1.088 1.088 0 0 1 2.175 0ZM17 15.459V8.563c0-.4.494-.7.79-.5l3.062 3.499c.197.2.197.6 0 .8l-3.062 3.497c-.296.3-.79.1-.79-.4Z" fill="#6F7CA3"/></svg>',
        'settings'   => [
            'basic' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_PAGINATION_STATUS_BASIC'),
                'fields' => [
                    'pattern' => [
                        'type'   => 'select',
                        'title'  => Text::_('COM_EASYSTORE_ADDON_PAGINATION_STATUS_PATTERN_TITLE'),
                        'values' => [
                            'pattern1' => Text::_('COM_EASYSTORE_ADDON_PAGINATION_STATUS_PATTERN_1'),
                            'pattern2' => Text::_('COM_EASYSTORE_ADDON_PAGINATION_STATUS_PATTERN_2'),
                            'pattern3' => Text::_('COM_EASYSTORE_ADDON_PAGINATION_STATUS_PATTERN_3'),
                            'custom'   => Text::_('COM_EASYSTORE_ADDON_PAGINATION_STATUS_PATTERN_CUSTOM'),
                        ],
                        'std' => 'pattern1',
                    ],

                    'custom_pattern' => [
                        'type'    => 'text',
                        'title'   => Text::_('COM_EASYSTORE_ADDON_PAGINATION_STATUS_CUSTOM_PATTERN_TITLE'),
                        'desc'    => Text::_('COM_EASYSTORE_ADDON_PAGINATION_STATUS_CUSTOM_PATTERN_DESC'),
                        'std'     => 'Displaying {{R}} of {{T}} products',
                        'depends' => [['pattern', '=', 'custom']],
                    ],

                    'color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],
                    'typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                ],
            ],
            'numbers' => [
                'title'  => Text::_('COM_EASYSTORE_ADDON_PAGINATION_STATUS_NUMBERS'),
                'fields' => [
                    'number_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'number_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],
                ],
            ],
        ],
    ]
);
