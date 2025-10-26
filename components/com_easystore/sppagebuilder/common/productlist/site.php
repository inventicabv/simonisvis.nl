<?php

/**
 * @package SP Page Builder
 * @author JoomShaper https://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

//no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Uri\Uri;
use JoomShaper\Component\EasyStore\Administrator\Concerns\HasRelationship;
use JoomShaper\Component\EasyStore\Administrator\Constants\ProductListSource;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Site\Model\ProductModel;
use JoomShaper\Component\EasyStore\Site\Model\ProductsModel;

class SppagebuilderAddonEasystoreCommonProductlist extends SppagebuilderAddons
{
    use HasRelationship;

    public function render()
    {
        $addon = $this->addon;

        $layouts     = new stdClass();
        $layout_path = JPATH_ROOT . '/components/com_sppagebuilder/layouts';

        $layouts->row_start = new FileLayout('row.start', $layout_path);
        $layouts->row_end   = new FileLayout('row.end', $layout_path);
        $layouts->row_css   = new FileLayout('row.css', $layout_path);

        $layouts->column_start = new FileLayout('column.start', $layout_path);
        $layouts->column_end   = new FileLayout('column.end', $layout_path);
        $layouts->column_css   = new FileLayout('column.css', $layout_path);

        $layouts->addon_start = new FileLayout('addon.start', $layout_path);
        $layouts->addon_end   = new FileLayout('addon.end', $layout_path);
        $layouts->addon_css   = new FileLayout('addon.css', $layout_path);

        $pageName  = 'none';
        $storeData = $addon->storeData ?? [];
        $output    = '';

        /** @var CMSApplication */
        $app      = Factory::getApplication();
        $document = $app->getDocument();

        // required assets
        EasyStoreHelper::attachRequiredAssets();

        $source                           = $addon->settings->source ?? 'latest';
        [$products, $pagination]          = $this->getProducts($addon->settings);
        $storeData['easystoreList']       = $products->products;
        $storeData['easystorePagination'] = $pagination;


        if ($source === 'up_sells' && empty($products->products)) {
            return $output = '';
        }

        if (empty($products->products)) {
            return LayoutHelper::render('products.emptyState', [], $this->easystoreLayoutPath);
        }

        $products->products[0]->source = $source;

        $animation    = AddonParser::generateAnimation($addon);
        $custom_class = "";
        $custom_class .= (isset($addon->settings->hidden_md) && filter_var($addon->settings->hidden_md, FILTER_VALIDATE_BOOLEAN)) ? 'sppb-hidden-md sppb-hidden-lg ' : '';
        $custom_class .= (isset($addon->settings->hidden_sm) && filter_var($addon->settings->hidden_sm, FILTER_VALIDATE_BOOLEAN)) ? 'sppb-hidden-sm ' : '';
        $custom_class .= (isset($addon->settings->hidden_xs) && filter_var($addon->settings->hidden_xs, FILTER_VALIDATE_BOOLEAN)) ? 'sppb-hidden-xs ' : '';

        $output .= AddonParser::generateCollectionCSS($addon, $pageName, $layouts);
        $animationClass      = $addon->settings->class . $custom_class . $animation['class'];
        $animationAttributes = $animation['attributes'];
        $classes             = ["sppb-collection-addon"];

        if (!empty($animationClass)) {
            array_push($classes, $animationClass);
        }

        $enableSlideShow = isset($addon->settings->enable_scroller) ? $addon->settings->enable_scroller : false;
        $addon_id        = "sppb-addon-{$addon->id}";
        $classes         = SppagebuilderHelperSite::classes(["sppb-collection-addon", $animationClass]);
        $output .= "<div id=\"{$addon_id}\" class=\"{$classes}\" {$animationAttributes} x-data=\"easystoreProductDetails\" style=\"min-height: 200px;\">";

        $slicedList            = $storeData['easystoreList'] ?? [];
        $collectionItemElement = '';

        if (!empty($addon->items)) {
            foreach ($slicedList as $index => $item) {
                $collectionItemElement .= '<div class="sppb-collection-item">';

                foreach ($addon->items[0] as $itemAddon) {
                    if (!empty($itemAddon->parent)) {
                        continue;
                    }

                    $collectionItemElement .= AddonParser::getDivHTMLViewForCollection($itemAddon, $addon->items[0], $layouts, $pageName, $storeData, $index);
                }

                $collectionItemElement .= '</div>';
            }
        }

        if (!empty($enableSlideShow)) {
            $output .= '<div class="sppb-productlist-slideshow-arrows">
                <span class="arrow-prev"></span>
                <span class="arrow-next"></span>
            </div>';
            $output .= '<div class="sppb-productlist-slideshow-wrap">';
            $output .= $collectionItemElement;
            $output .= '</div>';
        } else {
            $output .= $collectionItemElement;
        }

        $output .= '</div>';

        $hasPagination = $addon->settings->pagination ?? 1;

        if ($hasPagination && !$enableSlideShow) {
            $output .= '<div class="sppb-pagination-wrapper my-4">';
            $output .= LayoutHelper::render(
                'products.pagination',
                [
                    'pagination' => $pagination,
                ],
                $this->easystoreLayoutPath
            );
            $output .= '</div>';
        }

        return $output;
    }

    public function stylesheets()
    {
        $style_sheet     = [];
        $settings        = $this->addon->settings;
        $enableSlideShow = isset($settings->enable_scroller) ? $settings->enable_scroller : false;

        if (!empty($enableSlideShow)) {
            $style_sheet = [
                Uri::root(true) . '/components/com_sppagebuilder/assets/css/jquery.bxslider.min.css',
            ];
        }

        return $style_sheet;
    }

    public function scripts()
    {
        $settings        = $this->addon->settings;
        $enableSlideShow = isset($settings->enable_scroller) ? $settings->enable_scroller : false;
        if (!empty($enableSlideShow)) {
            HTMLHelper::_('script', 'components/com_sppagebuilder/assets/js/jquery.bxslider.min.js', [], ['defer' => true]);
        }
    }

    public function js()
    {
        $addon           = $this->addon;
        $settings        = $addon->settings;
        $addon_id        = '#sppb-addon-' . $addon->id;
        $enableSlideShow = isset($settings->enable_scroller) ? $settings->enable_scroller : false;

        if (empty($enableSlideShow)) {
            return '';
        }

        $showIndicators = 'true';
        if (isset($settings->show_indicators)) {
            $showIndicators = $settings->show_indicators === 1 ? 'true' : 'false';
        }

        $itemsPerSlide  = isset($settings->items_per_slide_original) ? $settings->items_per_slide_original : null;
        $itemsPerSlide  = empty($itemsPerSlide) ? $settings->items_per_slide : $itemsPerSlide;
        $slidesToScroll = isset($settings->slides_to_scroll_original) ? $settings->slides_to_scroll_original : null;
        $slidesToScroll = empty($slidesToScroll) ? $settings->slides_to_scroll : $slidesToScroll;

        return '
            jQuery(function(){
                "use strict";

                const viewPortWidth = window.innerWidth || document.documentElement.clientWidth;
                const DEVICES = {
                    xl: 1399.98,
                    lg: 1199.98,
                    md: 991.98,
                    sm: 767.98,
                    xs: 575.98,
                };

                function getItemsPerSlideByDevice(values) {
                    values = JSON.parse(values);

                    let itemsPerSlide = 4;
                    if (viewPortWidth <= DEVICES.xs) {
                        itemsPerSlide = values.xs || 1;
                    } else if (viewPortWidth <= DEVICES.sm) {
                        itemsPerSlide = values.sm || 3;
                    } else if (viewPortWidth <= DEVICES.md) {
                        itemsPerSlide = values.md || 3;
                    } else if (viewPortWidth <= DEVICES.lg) {
                        itemsPerSlide = values.lg || 3;
                    } else {
                        itemsPerSlide = values.xl || 4;
                    }

                    return Number(itemsPerSlide);
                }

                function getSlidesToScrollByDevice(values) {
                    values = JSON.parse(values);

                    var slidesToScroll = 1;
                    if (viewPortWidth <= DEVICES.xs) {
                        slidesToScroll = values.xs || 1;
                    } else if (viewPortWidth <= DEVICES.sm) {
                        slidesToScroll = values.sm || 1;
                    } else if (viewPortWidth <= DEVICES.md) {
                        slidesToScroll = values.md || 1;
                    } else if (viewPortWidth <= DEVICES.lg) {
                        slidesToScroll = values.lg || 1;
                    } else {
                        slidesToScroll = values.xl || 1;
                    }

                    return Number(slidesToScroll);
                }

                var itemsPerSlide = getItemsPerSlideByDevice(\'' . json_encode($itemsPerSlide) . '\');
                var slidesToScroll = getSlidesToScrollByDevice(\'' . json_encode($slidesToScroll) . '\');

                jQuery("' . $addon_id . ' .sppb-collection-addon .sppb-productlist-slideshow-wrap").bxSlider({
                    mode: "horizontal",
                    minSlides: itemsPerSlide,
                    maxSlides: itemsPerSlide,
                    moveSlides: slidesToScroll,
                    infiniteLoop: true,
                    controls: true,
                    pager: ' . $showIndicators . ',
                    nextText: "<i class=\'fa fa-angle-right\' aria-hidden=\'true\'></i>",
                    prevText: "<i class=\'fa fa-angle-left\' aria-hidden=\'true\'></i>",
                    nextSelector: $("' . $addon_id . ' .sppb-collection-addon .arrow-next"),
                    prevSelector: $("' . $addon_id . ' .sppb-collection-addon .arrow-prev"),
                    slideWidth: 1140,
                    auto: false,
                    autoHover: true,
                    touchEnabled: false,
                    onSlideNext: function() {
                        Joomla.imageLazyLoading();
                    },
                    onSlidePrev: function() {
                        Joomla.imageLazyLoading();
                    }
                });
            });
        ';
    }

    public function css()
    {
        $settings        = $this->addon->settings;
        $enableSlideShow = isset($settings->enable_scroller) ? $settings->enable_scroller : false;
        $addon_id        = "#sppb-addon-{$this->addon->id}";
        $cssHelper       = new CSSHelper($addon_id);
        $css             = '';

        $gridCss = $cssHelper->generateStyle('.sppb-collection-addon', $settings, [
            'grid_columns' => 'grid-template-columns:repeat(%s, 1fr)',
            'gap_x'        => 'column-gap',
            'gap_y'        => 'row-gap',
        ], [
            'grid_columns' => false,
        ], [], null, false, "display: grid;");

        if (!empty($enableSlideShow)) {
            $css .= $cssHelper->generateStyle('.sppb-collection-addon .slick-slide', $settings, [
                'gap_x' => 'margin-right',
            ]);
            $css .= $cssHelper->generateStyle('.sppb-collection-addon .slick-list', $settings, [
                'gap_x' => 'margin-right: -%s',
            ]);
        } else {
            $css .= $gridCss;
        }

        $settings->box_shadow = CSSHelper::parseBoxShadow($settings, 'box_shadow');
        $settings->transition = 'all 200ms ease-in';

        // Item
        $css .= $cssHelper->generateStyle('.sppb-collection-item', $settings, [
            'padding'       => 'padding',
            'background'    => 'background-color',
            'border_radius' => 'border-radius',
            'box_shadow'    => 'box-shadow',
            'transition'    => 'transition',
        ], ['padding' => false, 'background' => false, 'transition' => false, 'box_shadow' => false]);

        $css .= $cssHelper->border('.sppb-collection-item', $settings, 'border');

        // Hover
        $settings->box_shadow_hover = CSSHelper::parseBoxShadow($settings, 'box_shadow_hover');
        $css .= $cssHelper->generateStyle('.sppb-collection-item:hover', $settings, [
            'padding_hover'       => 'padding',
            'background_hover'    => 'background-color',
            'border_radius_hover' => 'border-radius',
            'box_shadow_hover'    => 'box-shadow',
        ], ['padding' => false, 'background' => false, 'box_shadow_hover' => false]);

        $css .= $cssHelper->border('.sppb-collection-item:hover', $settings, 'border_hover');

        return $css;
    }

    public function getProducts($settings)
    {
        $app           = Factory::getApplication();
        $model         = new ProductsModel();
        $source        = $settings->source ?? 'latest';
        $limit         = $settings->limit ?? $app->get('list_limit', 0);
        $start         = (int) $model->getState('list.start', 0);
        $hasPagination = $settings->pagination ?? 0;
        $categoryId    = $settings->category ?? 0;

        if (!empty($limit)) {
            $model->setState(
                'list.limit',
                $limit
            );
        }

        $attributes = [
            'source'        => $source,
            'start'         => $start,
            'limit'         => $limit,
            'catid'         => $categoryId,
            'pagination'    => $settings->pagination ?? 0,
        ];

        $model->setState(
            'attr',
            $attributes
        );

        if ($source === ProductListSource::UP_SELLS) {
            $productId = $this->getProductId();
            
            if (!$productId) {
                return [(object) ['products' => []], null];
            }

            $relatedIds = $this->getRelatedRecords(
            $productId, 
            'product', 
            'upsell',
            '#__easystore_product_upsells'
            );

            if (empty($relatedIds)) {
                return [(object) ['products' => []], null];
            }

            $model->setState('easystore.pks', $relatedIds);

            $items = $model->getItems();

            return [$items, $this->initPagination($model, $hasPagination)];
        }

        if ($source === ProductListSource::RELATED) {
            $relatedProductIds = $this->getSimilarProducts($limit);

            if (empty($relatedProductIds)) {
                return [(object) ['products' => []], null];
            }

            $model->setState('easystore.pks', $relatedProductIds);

            $items = $model->getItems();

            return [$items, $this->initPagination($model, $hasPagination)];
        }

        if ($source === ProductListSource::COLLECTION) {
            $attributes['collection_id'] = $settings->collection_id;
            $model->setState('attr', $attributes);
        }

        $items = $model->getItems();

        return [$items, $this->initPagination($model, $hasPagination)];
    }

    /**
     * Initialize pagination
     *
     * @param  object $model
     * @param  mixed $hasPagination
     * @return mixed
     * 
     * @since 1.4.6
     */
    public function initPagination($model, $hasPagination)
    {
        $pagination = $hasPagination ? $model->getPagination() : null;

        if (!empty($pagination)) {
            foreach ($model->getFilters() as $filter) {
                $value = Factory::getApplication()->getInput()->get($filter, '', 'STRING');
                if (!empty($value)) {
                    $pagination->setAdditionalUrlParam($filter, $value);
                }
            }
        }

        return $pagination;
    } 

    public function getSimilarProducts($limit = 4)
    {
        $productId = $this->getProductId();

        if (!$productId) {
            return [];
        }

        $singleModel = new ProductModel();
        $products    = $singleModel->getSimilarProducts($productId);

        $relatedProducts = array_filter($products, function ($product) {
            return $product->score > 0;
        });

        $relatedProducts = array_slice($relatedProducts, 0, $limit);

        $productIds = array_map(function ($item) {
            return $item->id;
        }, $relatedProducts);

        if (empty($productIds)) {
            return [];
        }

        return $productIds;
    }

    /**
     * Get related product ID
     *
     *
     * @return mixed
     * @since 1.5.0
     */
    public function getProductId()
    {
        $input     = Factory::getApplication()->input;
        $id        = $input->get('id', 0, 'INT');
        $productId = $input->get('product_id', $id, 'INT');

        return $productId;
    }
}
