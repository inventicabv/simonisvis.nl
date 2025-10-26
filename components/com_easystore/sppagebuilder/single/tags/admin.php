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
        'addon_name' => 'tags',
        'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_TAGS'),
        'desc'       => Text::_(''),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_SINGLE_PRODUCT'),
        'context'    => 'easystore.single',
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32"><path stroke="currentColor" stroke-miterlimit="10" stroke-width="1.5" d="m6.867 19.113 5.8 5.8c.2.2.533.2.733 0l7.6-7.6c.133-.133.133-.266.133-.4l.067-5.866a.526.526 0 0 0-.533-.534l-5.867.067c-.133 0-.267.067-.4.133l-7.533 7.6c-.2.2-.2.534 0 .8Z"/><path stroke="currentColor" stroke-miterlimit="10" stroke-width="1.5" d="M16 22.513v3.4s-.133.734.533.734h8s.734.133.734-.734V15.58s.133-.4-.334-.733L21.2 11.113M18.333 12.113c.734 0 1.334.6 1.334 1.334 0 .733-.6 1.333-1.334 1.333-.733 0-1.333-.6-1.333-1.333 0-.734.6-1.334 1.333-1.334Z"/><path stroke="currentColor" stroke-miterlimit="10" stroke-width="1.5" d="M18.4 11.78V7.247c.333-1 1.2-1.734 2.2-1.867 1.267-.2 2.533.6 3 1.867v6.466"/></svg>',
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
    ]
);
