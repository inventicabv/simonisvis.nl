<?php

/**
 * @package SP Page Builder
 * @author JoomShaper https://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

//no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Administrator\Constants\ProductListSource;
use JoomShaper\Component\EasyStore\Site\Helper\CollectionHelper;

$presetsData = SpPageBuilderAddonHelper::getPresets('easystore_common_productlist');
$collections = CollectionHelper::getCollectionsAsOptions();

SpAddonsConfig::addonConfig([
    'type'       => 'structure',
    'addon_name' => 'productlist',
    'title'      => Text::_('COM_SPPAGEBUILDER_ADDON_PRODUCT_LIST'),
    'desc'       => Text::_('COM_SPPAGEBUILDER_ADDON_PRODUCT_LIST_DESC'),
    'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_COMMON'),
    'context'    => 'easystore.common',
    'icon'       => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M19 5.47v7.145a.616.616 0 0 1-.352.464l-6.4 2.865a.577.577 0 0 1-.496 0l-6.4-2.865A.665.665 0 0 1 5 12.527V5.47a.64.64 0 0 1 .352-.552l6.4-2.864a.6.6 0 0 1 .496 0l6.4 2.864c.127.059.23.161.288.288A.584.584 0 0 1 19 5.47Zm-7.6 3.257L6.2 6.39v5.753l5.2 2.329V8.727Zm4.158-1.33L12.6 8.728v5.745l5.2-2.329V6.39l-2.2.989-.014.031-.028-.012Zm-.223-1.204 1.617-.723-4.936-2.216-1.546.694 4.865 2.245ZM9.261 4.491l4.863 2.245-2.108.943L7.08 5.47l2.18-.979ZM4 18.285c0-.315.265-.571.593-.571h14.814c.328 0 .593.256.593.571a.582.582 0 0 1-.593.572H4.593A.582.582 0 0 1 4 18.285ZM9.571 20a.571.571 0 1 0 0 1.143h5.715a.571.571 0 0 0 0-1.143H9.57Z" fill="#6F7CA3"/></svg>',
    'settings'   => [
        'content' => [
            'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_GENERAL'),
            'fields' => [
                'presets' => [
                    'type'   => 'preset',
                    'title'  => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_PRESETS_TITLE'),
                    'desc'   => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_PRESETS_DESC'),
                    'values' => $presetsData['presets'],
                    'std'    => $presetsData['default_preset'],
                ],
                'grid_columns' => [
                    'type'       => 'slider',
                    'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_GRID_COLUMNS_TITLE'),
                    'desc'       => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_GRID_COLUMNS_DESC'),
                    'min'        => 1,
                    'max'        => 6,
                    'responsive' => true,
                    'std'        => ['xl' => '3'],
                    'depends'    => [['enable_scroller', '=', 0]],
                ],
                'gap_x' => [
                    'type'       => 'slider',
                    'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_GAP_X_TITLE'),
                    'desc'       => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_GAP_X_DESC'),
                    'min'        => 0,
                    'max'        => 100,
                    'responsive' => true,
                    'std'        => ['xl' => '32'],
                ],
                'gap_y' => [
                    'type'       => 'slider',
                    'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_GAP_Y_TITLE'),
                    'desc'       => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_GAP_Y_DESC'),
                    'min'        => 0,
                    'max'        => 100,
                    'responsive' => true,
                    'std'        => ['xl' => '32'],
                    'depends'    => [['enable_scroller', '=', 0]],
                ],
                'limit' => [
                    'type'  => 'slider',
                    'title' => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_LIMIT_TITLE'),
                    'desc'  => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_LIMIT_DESC'),
                    'min'   => 1,
                    'max'   => 100,
                    'std'   => 16,
                ],
                'source' => [
                    'type'   => 'select',
                    'title'  => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_SOURCE_TITLE'),
                    'desc'   => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_SOURCE_DESC'),
                    'values' => [
                        ProductListSource::LATEST       => 'Latest',
                        ProductListSource::FEATURED     => 'Featured',
                        ProductListSource::BEST_SELLING => 'Best Selling',
                        ProductListSource::ON_SALE      => 'On Sale',
                        ProductListSource::OLDEST       => 'Oldest',
                        ProductListSource::RELATED      => 'Related',
                        ProductListSource::WISHLIST     => 'Wishlist',
                        ProductListSource::COLLECTION   => 'Collection',
                        ProductListSource::UP_SELLS     => 'Upsell',
                    ],
                    'std' => 'latest'
                ],
                'collection_id' => [
                    'type'   => 'select',
                    'title'  => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_COLLECTION_ID_TITLE'),
                    'desc'   => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_COLLECTION_ID_DESC'),
                    'values' => $collections,
                    'std'    => '',
                    'depends' => [['source', '=', ProductListSource::COLLECTION]],
                ],
                'category' => [
                    'type'    => 'category',
                    'context' => 'com_easystore',
                    'title'   => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_CATEGORY_TITLE'),
                    'desc'    => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_CATEGORY_DESC'),
                    'std'     => '',
                    'depends' => [['source', '!=', ProductListSource::COLLECTION]],
                ],
                'enable_scroller' => [
                    'type'  => 'checkbox',
                    'title' => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_ENABLE_SCROLLER_TITLE') ,
                    'desc'  => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_ENABLE_SCROLLER_DESC'),
                    'std'   => 0,
                ],
                'items_per_slide' => [
                    'type'       => 'slider',
                    'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_ITEMS_PER_SLIDE_TITLE'),
                    'desc'       => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_ITEMS_PER_SLIDE_DESC'),
                    'min'        => 1,
                    'max'        => 6,
                    'std'        => ['xl' => '4'],
                    'responsive' => true,
                    'depends'    => [['enable_scroller', '=', 1]],
                ],
                'slides_to_scroll' => [
                    'type'       => 'slider',
                    'title'      => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_SLIDES_TO_SCROLL_TITLE'),
                    'desc'       => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_SLIDES_TO_SCROLL_DESC'),
                    'min'        => 1,
                    'max'        => 6,
                    'std'        => ['xl' => '1'],
                    'responsive' => true,
                    'depends'    => [['enable_scroller', '=', 1]],
                ],
                'show_indicators' => [
                    'type'    => 'checkbox',
                    'title'   => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_SHOW_INDICATORS_TITLE'),
                    'desc'    => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_SHOW_INDICATORS_DESC'),
                    'std'     => 1,
                    'depends' => [['enable_scroller', '=', 1]],
                ],
                'pagination' => [
                    'type'    => 'checkbox',
                    'title'   => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_PAGINATION_TITLE'),
                    'desc'    => Text::_('COM_EASYSTORE_ADDON_PRODUCT_LIST_PAGINATION_DESC'),
                    'std'     => 1,
                    'depends' => [['enable_scroller', '=', 0]],
                ],
            ],
        ],

        'item' => [
            'title'  => 'Item',
            'fields' => [
                'padding' => [
                    'type'       => 'padding',
                    'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING'),
                    'responsive' => true,
                ],

                'item_options_separator' => [
                    'type' => 'separator',
                ],

                'item_options' => [
                    'type'   => 'buttons',
                    'values' => [
                        ['label' => Text::_('COM_SPPAGEBUILDER_GLOBAL_NORMAL'), 'value' => 'normal'],
                        ['label' => Text::_('COM_SPPAGEBUILDER_GLOBAL_HOVER'), 'value' => 'hover'],
                    ],
                    'std' => 'normal',
                ],

                'background' => [
                    'type'    => 'color',
                    'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                    'depends' => [['item_options', '=', 'normal']],
                ],

                'border_separator' => [
                    'type'    => 'separator',
                    'depends' => [['item_options', '=', 'normal']],
                ],

                'border' => [
                    'type'       => 'border',
                    'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                    'responsive' => true,
                    'depends'    => [['item_options', '=', 'normal']],
                ],

                'border_radius' => [
                    'type'       => 'slider',
                    'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_RADIUS'),
                    'responsive' => true,
                    'depends'    => [['item_options', '=', 'normal']],
                ],

                'box_shadow' => [
                    'type'    => 'boxshadow',
                    'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BOX_SHADOW'),
                    'depends' => [['item_options', '=', 'normal']],
                ],

                // hover state
                'background_hover' => [
                    'type'    => 'color',
                    'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                    'depends' => [['item_options', '=', 'hover']],
                ],

                'border_separator_hover' => [
                    'type'    => 'separator',
                    'depends' => [['item_options', '=', 'hover']],
                ],

                'border_hover' => [
                    'type'    => 'border',
                    'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                    'depends' => [['item_options', '=', 'hover']],
                ],

                'border_radius_hover' => [
                    'type'       => 'slider',
                    'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_RADIUS'),
                    'responsive' => true,
                    'depends'    => [['item_options', '=', 'hover']],
                ],

                'box_shadow_hover' => [
                    'type'    => 'boxshadow',
                    'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BOX_SHADOW'),
                    'depends' => [['item_options', '=', 'hover']],
                ],
            ],
        ],
    ],
]);
