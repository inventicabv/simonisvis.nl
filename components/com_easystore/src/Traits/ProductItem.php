<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Traits;


use JoomShaper\Component\EasyStore\Administrator\Concerns\Taxable;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Site\Helper\ArrayHelper;
use JoomShaper\Component\EasyStore\Site\Helper\ProductStock;
use JoomShaper\Component\EasyStore\Site\Helper\StringHelper;

defined('_JEXEC') or die;

trait ProductItem
{
    use Taxable;

    protected function getProductStock($item)
    {
        $isTracking           = $item->is_tracking_inventory;
        $isEnableOutStockSell = $item->enable_out_of_stock_sell;
        $status               = ProductStock::OUT_OF_STOCK;
        $amount               = 0;
        $sku                  = '';
        $weight               = '';
        $unit                 = '';

        if ($item->has_variants && !empty($item->active_variant)) {
            $activeVariant = $item->active_variant;

            if ($isTracking) {
                $status = $activeVariant->inventory_amount > 0
                    ? ProductStock::IN_STOCK
                    : ProductStock::OUT_OF_STOCK;
                $amount = $activeVariant->inventory_amount;
            } else {
                $status = $activeVariant->inventory_status
                    ? ProductStock::IN_STOCK
                    : ProductStock::OUT_OF_STOCK;
            }

            if (!$activeVariant->visibility) {
                $status = ProductStock::UNAVAILABLE;
                $amount = 0;
            }

            $sku    = $item->active_variant->sku;
            $weight = $item->active_variant->weight;
            $unit   = $item->active_variant->unit ?? SettingsHelper::getSettings()->get('general.unit');
        } else {
            if ($isTracking) {
                $status = $item->quantity > 0 ? ProductStock::IN_STOCK : ProductStock::OUT_OF_STOCK;
                $amount = $item->quantity;
            } else {
                $status = $item->inventory_status ? ProductStock::IN_STOCK : ProductStock::OUT_OF_STOCK;
                $amount = 0;
            }

            $sku    = $item->sku;
            $weight = $item->weight;
            $unit   = $item->unit ?? SettingsHelper::getSettings()->get('general.unit');
        }

        if ($status === ProductStock::OUT_OF_STOCK && $status !== ProductStock::UNAVAILABLE && $isEnableOutStockSell) {
            $status = ProductStock::IN_STOCK;
            $amount = 0;
        }

        return (object) [
            'status' => $status,
            'amount' => $amount,
            'sku'    => $sku,
            'weight' => $weight,
            'unit'   => $unit,
        ];
    }

    protected function getPriceSegments($item, $isListView = false)
    {
        if ($isListView) {
            return (object)[
                'price'                          => $item->min_price,
                'price_with_currency'            => $item->min_price_with_currency,
                'price_with_segments'            => $item->min_price_with_segments,
                'discounted_price'               => $item->discounted_min_price,
                'discounted_price_with_currency' => $item->discounted_min_price_with_currency,
                'discounted_price_with_segments' => $item->discounted_min_price_with_segments,
            ];
        }

        if ($item->has_variants && !empty($item->active_variant)) {
            $activeVariant = $item->active_variant;

            return (object)[
                'price'                          => $activeVariant->price,
                'price_with_currency'            => $activeVariant->price_with_currency,
                'price_with_segments'            => $activeVariant->price_with_segments,
                'discounted_price'               => $activeVariant->discounted_price,
                'discounted_price_with_currency' => $activeVariant->discounted_price_with_currency,
                'discounted_price_with_segments' => $activeVariant->discounted_price_with_segments,
            ];
        }

        return (object)[
            'price'                          => $item->regular_price,
            'price_with_currency'            => $item->regular_price_with_currency,
            'price_with_segments'            => $item->regular_price_with_segments,
            'discounted_price'               => $item->discounted_price,
            'discounted_price_with_currency' => $item->discounted_price_with_currency,
            'discounted_price_with_segments' => $item->discounted_price_with_segments,
        ];
    }

    protected function getProductThumbnail($item, $isSingleView = false)
    {
        $thumbnail = ArrayHelper::find(function ($media) {
            return $media->is_featured;
        }, $item->media->gallery);

        if ($isSingleView && !empty($item->active_variant) && isset($item->active_variant->image)) {
            $thumbnail = $item->active_variant->image;
        }

        if (!empty($thumbnail)) {
            $thumbnail->alt_text = $item->title;

            if (!empty($item->active_variant)) {
                $thumbnail->alt_text .= str_replace(';', ' ', $item->active_variant->combination);
            }
        }

        return $thumbnail;
    }

    protected function getVariantOptionAssociation($item)
    {
        $variants = $item->variants;
        $options  = $item->options;

        if (empty($variants) || empty($options)) {
            return null;
        }

        foreach ($variants as &$variant) {
            $combination  = $variant->combination;
            $items        = StringHelper::stringToArray($combination, ';');
            $associations = [];

            if (empty($items)) {
                $variant->variant_option_map = null;
                continue;
            }

            foreach ($options as $option) {
                if (empty($option->values)) {
                    continue;
                }

                foreach ($option->values as $value) {
                    foreach ($items as $item) {
                        if ($item === $value->name) {
                            $associations[$option->name] = $item;
                        }
                    }
                }
            }


            $variant->variant_option_map = (object) $associations;
        }

        unset($variant);

        return $variants;
    }

    protected function getVariantById($item, $id)
    {
        if (!$item->has_variants) {
            return null;
        }

        if (empty($item->variants)) {
            return null;
        }

        if (empty($id) && !empty($item->variants)) {
            return reset($item->variants);
        }

        return ArrayHelper::find(function ($item) use ($id) {
            return (int) $item->id === (int) $id;
        }, $item->variants);
    }

    /**
     * Determine the overall stock status of a product.
     *
     * @param  object $item   The product item.
     * @return int            The overall stock status.
     * @since  1.0.10
     */
    protected function getOverallProductStockStatus($item)
    {
        $isTracking           = $item->is_tracking_inventory;
        $isEnableOutStockSell = $item->enable_out_of_stock_sell;
        $status               = ProductStock::OUT_OF_STOCK;

        if ($item->has_variants) {
            foreach ($item->variants as $variant) {
                if ($isEnableOutStockSell && $variant->visibility) {
                    return ProductStock::IN_STOCK;
                }

                if (!$variant->visibility) {
                    $status = ProductStock::UNAVAILABLE;
                }

                if ($isTracking && $variant->visibility) {
                    $status = $variant->inventory_amount > 0 ? ProductStock::IN_STOCK : ProductStock::OUT_OF_STOCK;
                }

                if (!$isTracking && $variant->visibility) {
                    $status = $variant->inventory_status ? ProductStock::IN_STOCK : ProductStock::OUT_OF_STOCK;
                }

                if ($status === ProductStock::IN_STOCK) {
                    return $status;
                }
            }
        } else {
            if ($isEnableOutStockSell) {
                return ProductStock::IN_STOCK;
            }

            if ($isTracking) {
                $status = $item->quantity > 0 ? ProductStock::IN_STOCK : ProductStock::OUT_OF_STOCK;
            } else {
                $status = $item->inventory_status ? ProductStock::IN_STOCK : ProductStock::OUT_OF_STOCK;
            }
        }

        return $status;
    }
}
