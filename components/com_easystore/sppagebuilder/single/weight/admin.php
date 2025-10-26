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
        'addon_name' => 'weight',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_WEIGHT'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_SINGLE_PRODUCT'),
        'context'    => 'easystore.single',
        'icon'       => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M5.75 4C5.33579 4 5 4.33579 5 4.75C5 5.16421 5.33579 5.5 5.75 5.5H10.089V7.53387H7.03056C6.22221 7.53387 5.53632 8.13926 5.43835 8.94414L4.40252 17.4442C4.34654 17.9026 4.49 18.361 4.79445 18.7039C5.09889 19.0468 5.53632 19.2463 5.99824 19.2463H17.3923C17.8542 19.2463 18.2916 19.0468 18.596 18.7039C18.9005 18.361 19.044 17.9026 18.988 17.4442L17.9522 8.94414C17.8542 8.13926 17.1683 7.53387 16.3564 7.53387H13.3015V5.5H17.25C17.6642 5.5 18 5.16421 18 4.75C18 4.33579 17.6642 4 17.25 4H5.75ZM11.6953 17.7171C9.30867 17.7171 7.36651 15.775 7.36651 13.3883C7.36651 11.0017 9.30867 9.0631 11.6953 9.0631C14.0818 9.0631 16.0205 11.0017 16.0205 13.3883C16.0205 15.775 14.0818 17.7171 11.6953 17.7171ZM12.0417 12.2613L12.0421 10.3504C12.0421 10.157 11.8856 10.0005 11.6922 10.0005C11.4987 10.0005 11.3422 10.157 11.3422 10.3504L11.3419 12.2615C10.8593 12.4119 10.5059 12.8574 10.5059 13.389C10.5059 14.0434 11.0378 14.5753 11.6922 14.5753C12.3465 14.5753 12.8784 14.0434 12.8784 13.389C12.8784 12.8571 12.5247 12.4115 12.0417 12.2613Z" fill="#6F7CA3"/>
                        </svg>',
        'settings' => [
            'basic' => [
                'fields' => [

                    // Weight Title
                    'title_admin_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_EASYSTORE_APP_TITLE'),
                    ],

                    'title_text' => [
                        'type'  => 'text',
                        'title' => Text::_('COM_EASYSTORE_ADDON_WEIGHT_TITLE'),
                        'std'   => "",
                    ],

                    'title_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],

                    'title_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],

                    // Weight Value
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

                    // Weight Separator
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
