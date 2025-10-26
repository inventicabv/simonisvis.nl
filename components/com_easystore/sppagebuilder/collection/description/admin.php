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
		'type'       => 'productCollection',
		'addon_name' => 'description',
		'title'      => Text::_('COM_EASYSTORE_COLLECTION_ADDON_DESCRIPTION'),
		'desc'       => Text::_('COM_EASYSTORE_COLLECTION_ADDON_DESCRIPTION_DESC'),
		'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_COLLECTION'),
		'context'    => 'easystore.collection',
		'icon'       => '<svg width="24" height="24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.566 4.897c0 .188-.11.35-.27.426V9.75c0 .26-.21.472-.471.472H4.759a.472.472 0 0 1-.472-.472V5.323a.472.472 0 0 1-.27-.426V3.75c0-.26.212-.472.472-.472h6.606c.26 0 .471.212.471.472v1.146Zm-7.01-.067V3.818h6.471V4.83h-6.47Zm.27.539h5.932v4.313H4.826V5.37Zm1.416 1.415c0-.447.362-.809.809-.809H8.6a.809.809 0 1 1 0 1.618H7.05a.809.809 0 0 1-.81-.809Zm.809-.27H8.6a.27.27 0 1 1 0 .54H7.05a.27.27 0 1 1 0-.54Zm12.433 7.207a.5.5 0 1 0 0-1h-13a.5.5 0 0 0 0 1h13Zm.5 3a.5.5 0 0 1-.5.5h-13a.5.5 0 1 1 0-1h13a.5.5 0 0 1 .5.5Zm-7.5 4a.5.5 0 1 0 0-1h-6a.5.5 0 0 0 0 1h6Zm7.5-11a.5.5 0 0 1-.5.5h-6.5a.5.5 0 0 1 0-1h6.5a.5.5 0 0 1 .5.5Zm-.5-3a.5.5 0 1 0 0-1h-6.5a.5.5 0 0 0 0 1h6.5Z" fill="#6F7CA3"/></svg>',
		'settings'   => [
			'content' => [
				'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_CONTENT'),
				'fields' => [
					'content_typography' => [
						'type' => 'typography',
						'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
						'fallbacks'   => [
							'font' => 'text_font_family',
							'size' => 'text_fontsize',
							'line_height' => 'text_lineheight',
							'weight' => 'text_fontweight',
						],
					],

					'content_text_color' => [
						'type' => 'color',
						'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
					],

					'alignment_separator' => [
						'type' => 'separator',
					],

					'content_alignment' => [
						'type' => 'alignment',
						'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_ALIGNMENT'),
						'responsive' => true,
					],
				],
			],

			'drop_cap' => [
				'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DROPCAP'),
				'fields' => [
					'has_drop_cap' => [
						'type' => 'checkbox',
						'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DROPCAP'),
						'std' => 0,
						'is_header' => 1
					],

					'drop_cap_font_size' => [
						'type' 	=> 'slider',
						'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_FONT_SIZE'),
						'depends' => [['has_drop_cap', '=', 1]],
						'min' 	=> 0,
						'max' 	=> 200,
						'responsive' => true,
					],

					'drop_cap_color' => [
						'type' => 'color',
						'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
						'depends' => [['has_drop_cap', '=', 1]],
					],
				],
			],

		],
	]
);
