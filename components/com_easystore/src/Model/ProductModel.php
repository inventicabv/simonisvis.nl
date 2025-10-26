<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Site\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Language\Multilanguage;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use JoomShaper\Component\EasyStore\Administrator\Concerns\Taxable;
use JoomShaper\Component\EasyStore\Site\Traits\WishList;
use JoomShaper\Component\EasyStore\Site\Traits\ProductItem;
use JoomShaper\Component\EasyStore\Site\Helper\ProductStock;
use JoomShaper\Component\EasyStore\Site\Traits\Availability;
use JoomShaper\Component\EasyStore\Site\Traits\ProductMedia;
use JoomShaper\Component\EasyStore\Site\Traits\ProductOption;
use JoomShaper\Component\EasyStore\Site\Traits\ProductVariant;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Site\Traits\SimilarProducts;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Administrator\Model\ProductModel as AdminProductModel;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper as AdminEasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Supports\Shop;

class ProductModel extends ItemModel
{
    use Taxable;
    use ProductMedia;
    use ProductOption;
    use ProductVariant;
    use WishList;
    use Availability;
    use SimilarProducts;
    use ProductItem;

    /**
     * Model context string.
     *
     * @var        string
     */
    protected $_context = 'com_easystore.product';

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @since   1.0.0
     *
     * @return void
     */
    protected function populateState()
    {
        /**
         * @var CMSApplication
         */
        $app   = Factory::getApplication();
        $input = $app->getInput();

        // Load state from the request.
        $pk = $input->getInt('id');
        $this->setState('product.id', $pk);

        $offset = $input->getUint('limitstart');
        $this->setState('list.offset', $offset);

        // Load the parameters.
        $params = $app->getParams();
        $this->setState('params', $params);

        $this->setState('filter.language', Multilanguage::isEnabled());
    }

    /**
     * Returns a message for display
     * @param int $pk Primary key of the "message item", currently unused
     * @return mixed Message object
     */
    public function getItem($pk = null)
    {
        $pk        = (int) ($pk ?: $this->getState('product.id'));
        $db        = $this->getDatabase();
        $query     = $db->getQuery(true);
        $variantId = Factory::getApplication()->input->get('variant', null, 'INT');
        $app       = Factory::getApplication();

        if ($this->getState('variant_id', null)) {
            $variantId = $this->getState('variant_id');
        }

        $query->select(
            [
                'a.*',
                $db->quoteName('c.title', 'category_title'),
                $db->quoteName('c.alias', 'category_alias'),
                $db->quoteName('b.title', 'brand_title'),
                $db->quoteName('b.alias', 'brand_alias'),
                $db->quoteName('b.image', 'brand_image'),
                $db->quoteName('b.id', 'brand_id'),
            ]
        )
            ->from($db->quoteName('#__easystore_products', 'a'))
            ->where($db->quoteName('a.id') . ' = ' . $pk)
            ->where($db->quoteName('a.published') . ' = 1')
            ->join('LEFT', $db->quoteName('#__easystore_categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
            ->join('LEFT', $db->quoteName('#__easystore_brands', 'b'), $db->quoteName('b.id') . ' = ' . $db->quoteName('a.brand_id'))
            ->where($db->quoteName('c.published') . ' = 1');
            if (Multilanguage::isEnabled()) {
                $query->whereIn($db->quoteName('a.language'), [Factory::getApplication()->getLanguage()->getTag(), '*'], ParameterType::STRING);
            }

        $db->setQuery($query);

        $item = $db->loadObject() ?? null;

        if (empty($item)) {
            return null;
        }

        $item->tax_rate = $item->is_taxable ? $this->getProductTaxRate($item) : 0;
        $item->media    = $this->getMedia($item->id);
        $item->options  = $this->getOptions($item->id);
        $item->variants = $this->getVariants($item->id, $item);
        $item->link     = Route::_('index.php?option=com_easystore&view=product&id=' . $item->id . '&catid=' . $item->catid);

        if (!empty($item->variants) && !empty($item->media->gallery)) {
            $imageIds = array_map(function ($variant) {
                if (isset($variant->image) && isset($variant->image->id)) {
                    return $variant->image->id;
                }
                return null;
            }, (array) $item->variants);

            $imageIds = array_filter($imageIds, function ($id) {
                return !is_null($id);
            });

            $imageIds = array_unique(array_values($imageIds));

            usort($item->media->gallery, function ($first, $second) use ($imageIds) {
                $firstIndex  = array_search($first->id, $imageIds);
                $secondIndex = array_search($second->id, $imageIds);

                if ($firstIndex === false) {
                    $firstIndex = count($imageIds);
                }
                if ($secondIndex === false) {
                    $secondIndex = count($imageIds);
                }

                return $firstIndex - $secondIndex;
            });
        }

        $item->category_link = Route::_('index.php?option=com_easystore&view=products&catid=' . $item->catid);
        $item->reviews       = EasyStoreHelper::getReviews($item->id);
        $user                = $app->getIdentity();
        $item->currentUser   = $user;

        if ($user->id) {
            $item->inWishList     = $this->isProductInWishlist($item->id, $user->id);
            $item->canReview      = EasyStoreHelper::canReview($user->id, $item->id);
            $item->hasGivenReview = EasyStoreHelper::hasGivenReview($user->id, $item->id);
        }

        $item->availability = !empty($item->variants) ? $this->checkAvailability((array) $item->variants, $item->options) : [];

        $item->regular_price = Shop::applyTaxToPrice($item->is_taxable) ? $item->regular_price + $this->getTaxableAmount($item->regular_price, $item->tax_rate) : $item->regular_price;

        $item->discounted_price = ($item->has_sale && $item->discount_value) ? AdminEasyStoreHelper::calculateDiscountedPrice($item->discount_type, $item->discount_value, $item->regular_price) : 0;

        $item->discounted_price_with_currency = AdminEasyStoreHelper::formatCurrency($item->discounted_price);
        $item->discounted_price_with_segments = AdminEasyStoreHelper::formatCurrency($item->discounted_price, true);
        $item->regular_price_with_currency    = AdminEasyStoreHelper::formatCurrency($item->regular_price);
        $item->regular_price_with_segments    = AdminEasyStoreHelper::formatCurrency($item->regular_price, true);
        $item->currency_discounted_price      = AdminEasyStoreHelper::formatCurrency($item->discounted_price, true);
        $item->currency_symbol                = AdminEasyStoreHelper::getCurrencySymbol();
        $item->reviewData                     = AdminProductModel::getReviewData($item->id);
        $item->page                           = 'product-detail-page';

        if ($item->has_variants) {
            $item->variants       = $this->getVariantOptionAssociation($item);
            $item->active_variant = $this->getVariantById($item, $variantId);
        }

        $item->thumbnail      = $this->getProductThumbnail($item,true);
        $item->prices         = $this->getPriceSegments($item);
        $item->stock          = $this->getProductStock($item);
        $item->overallStock   = in_array($item->stock->status, [ProductStock::OUT_OF_STOCK, ProductStock::UNAVAILABLE], true) ? $this->getOverallProductStockStatus($item) : $item->stock->status;
        $item->tags           = $this->getTags($item->id);

        EasyStoreHelper::setSiteTitle($item->title);

        return $item;
    }

    /**
     * Get all product related tags
     *
     * @param int $id    Product id
     *
     * @return mixed
     *
     * @since 1.0.4
     */
    public function getTags($id)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('tag.id, tag.title, tag.alias')
            ->from($db->quoteName('#__easystore_tags', 'tag'))
            ->join('LEFT', $db->quoteName('#__easystore_product_tag_map', 'tag_map'), $db->quoteName('tag_map.tag_id') . ' = ' . $db->quoteName('tag.id'))
            ->where($db->quoteName('tag.published') . ' = 1')
            ->where($db->quoteName('tag_map.product_id') . " = " . $db->quote($id));

        $db->setQuery($query);

        try {
            return $db->loadObjectList();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Add the review to the database.
     *
     * @param  array     $data  An array of data to be inserted into the database.
     * @return mixed            Message object
     * @since  1.0.0
     */
    public function insertDataToDB($data)
    {
        $app = Factory::getApplication();
        $db  = $this->getDatabase();

        $values = [
            $db->quote($data['created_by']),
            $db->quote($data['product_id']),
            $db->quote($data['rating']),
            $db->quote($data['subject']),
            $db->quote($data['review']),
            $db->quote($data['published']),
        ];

        if (!empty($data) && $data) {
            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__easystore_reviews'))
                ->columns($db->quoteName([
                    'created_by',
                    'product_id',
                    'rating',
                    'subject',
                    'review',
                    'published',
                ]));

            $query->values(implode(',', $values));

            $db->setQuery($query);

            try {
                return $db->execute();
            } catch (\RuntimeException $e) {
                $app->enqueueMessage($e->getMessage(), 'error');
            }
        }
    }

    /**
     * Get order fulfilment information for a specific product and user.
     *
     * @param int $productID - Product ID.
     * @param int $userID    - User ID.
     *
     * @return array|null An array of order fulfilment information or null if no data is found.
     *
     * @throws \Exception If there is an error in the database operation.
     * @since  1.0.0
     */
    public function getOrderFullfillment($productID, $userID)
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);
        $orm   = new EasyStoreDatabaseOrm();

        $customerId = $orm->get('#__easystore_users', 'user_id', $userID, 'id')->loadResult();

        if (!empty($customerId)) {
            $query->select([$db->quoteName('o.id'), $db->quoteName('o.fulfilment')])
                ->from($db->quoteName('#__easystore_orders', 'o'))
                ->where($db->quoteName('o.customer_id') . " = " . $db->quote($customerId));

            $query->join('LEFT', $db->quoteName('#__easystore_order_product_map', 'opm'), $db->quoteName('opm.order_id') . ' = ' . $db->quoteName('o.id'))
                ->where($db->quoteName('opm.product_id') . " = " . $db->quote($productID));

            $db->setQuery($query);

            try {
                return $db->loadObjectList();
            } catch (\Throwable $e) {
                throw new \Exception($e->getMessage());
            }
        }
    }

    /**
     * Deducts the product quantity from the product or SKU table.
     *
     * This function checks if inventory tracking is enabled for the given product. If tracking is enabled,
     * it deducts the specified quantity from either the product or SKU table based on the presence of a variant ID.
     *
     * @param object $product An object representing the product, which includes the product_id, variant_id, and quantity.
     * @return void
     * @throws \Throwable If there is an error during the database operations.
     *
     * @since 1.2.0
     */
    public function deductFromProductOrSkuTable($product)
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select('is_tracking_inventory')
            ->from($db->quoteName('#__easystore_products'))
            ->where($db->quoteName('id') . ' = ' . (int) $product->product_id);

        $db->setQuery($query);

        try {
            $isTrackingInventory = (bool) ($db->loadResult() ?? 0);
        } catch (\Throwable $error) {
            throw $error;
        }

        if (!$isTrackingInventory) {
            return;
        }

        $tableName  = !empty($product->variant_id) ? '#__easystore_product_skus' : '#__easystore_products';
        $columnName = !empty($product->variant_id) ? 'inventory_amount' : 'quantity';

        $fields = [
            $db->quoteName($columnName) . ' = ' . $db->quoteName($columnName) . ' - ' . $product->quantity,
        ];

        $conditions = [
            $db->quoteName('id') . ' = ' . $db->quote(!empty($product->variant_id) ? $product->variant_id : $product->product_id),
        ];

        $query = $db->getQuery(true)
                    ->update($tableName)
                    ->set($fields)
                    ->where($conditions);

        $db->setQuery($query);

        try {
            $db->execute();
        } catch (\Throwable $error) {
            throw $error;
        }
    }
}
