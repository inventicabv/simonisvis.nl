<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright (C) 2023 - 2024 JoomShaper. <https: //www.joomshaper.com>
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Traits;

use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\Path;
use JoomShaper\Component\EasyStore\Administrator\Concerns\Taxable;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Administrator\Supports\Shop;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

trait ProductVariant
{
    use Taxable;

    /**
     * Function to get variants
     *
     * @param int $id
     * @param bool $isPriceSegmented    The price values will return segmented data as object
     * @return array
     */
    public function getVariants($id, $product)
    {
        $orm      = new EasyStoreDatabaseOrm();
        $variants = $this->fetchVariants($orm, $id);

        if (!empty($variants)) {
            foreach ($variants as &$variant) {
                $variant->catid  = $product->catid;
                $variant->image = $this->fetchVariantImage($orm, $variant->image_id);
                $this->processVariant($variant, $product);
            }
        }

        return $variants;
    }

    private function fetchVariants($orm, $id)
    {
        return $orm->setColumns([
                'id',
                'product_id',
                'combination_name',
                'combination_value',
                'image_id',
                'price',
                'visibility',
                'inventory_status',
                'inventory_amount',
                'is_taxable',
                'sku',
                'weight',
                'unit',
            ])
            ->hasMany($id, '#__easystore_product_skus', 'product_id')
            ->updateQuery(function ($query) use ($orm) {
                $query->order($orm->quoteName('ordering') . ' ASC');
            })
            ->loadObjectList();
    }

    private function fetchVariantImage($orm, $imageId)
    {
        $image = $orm->setColumns(['id', 'src', 'name', 'type', 'is_featured', 'alt_text'])
            ->hasOne($imageId, '#__easystore_media', 'id')
            ->updateQuery(function ($query) use ($orm) {
                $query->order($orm->quoteName('ordering') . ' ASC');
            })
            ->loadObject();

        if (isset($image)) {
            $image->src = !empty($image->src) ? Uri::root(true) . '/' . Path::clean($image->src) : '';
        }

        return $image;
    }

    private function processVariant(&$variant, $product)
    {
        $variant->combination = $variant->combination_value;
        $variant->visibility = (bool) $variant->visibility;
        $variant->price = (float) $variant->price;
        $variant->price = Shop::applyTaxToPrice($variant->is_taxable) ? $variant->price + Shop::calculateTaxableAmount($variant->price, $product->tax_rate) : $variant->price;
        $variant->is_tracking_inventory = $product->is_tracking_inventory;
        $variant->inventory_status = (bool) $variant->inventory_status;
        $variant->catid = $product->catid;
        $variant->tax_rate = $variant->is_taxable ? $this->getProductTaxRate($variant) : 0;

        // Format prices
        $variant->price_with_currency = EasyStoreHelper::formatCurrency($variant->price);
        $variant->price_with_segments = EasyStoreHelper::formatCurrency($variant->price, true);
        $variant->discounted_price = ($product->has_sale && $product->discount_value)
            ? EasyStoreHelper::calculateDiscountedPrice($product->discount_type, $product->discount_value, $variant->price)
            : 0;
        $variant->discounted_price_with_currency = EasyStoreHelper::formatCurrency($variant->discounted_price);
        $variant->discounted_price_with_segments = EasyStoreHelper::formatCurrency($variant->discounted_price, true);

        // Remove unnecessary fields
        unset($variant->combination_name, $variant->combination_value, $variant->image_id);
    }
}
