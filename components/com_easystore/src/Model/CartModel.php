<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Filesystem\Path;
use JoomShaper\Component\EasyStore\Administrator\Checkout\CartManager;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Supports\Shop;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Site\Traits\Token;
use Throwable;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class CartModel extends BaseDatabaseModel
{
    use Token;

    /**
     * Model context string.
     *
     * @var        string
     */
    protected $_context = 'com_easystore.cart';

    public $paymentMethod;

    public function getItem($shippingId = null, $country = null, $state = null, $city = null, $zipCode = null)
    {
        $db  = $this->getDatabase();
        $orm = new EasyStoreDatabaseOrm();

        try {
            $token = $this->getToken();
            $cart  = $this->getCartByToken($token);

            if (empty($cart)) {
                return null;
            }

            $cart->items = $this->fetchCartItems($cart->id, $orm, $db);

            $cartManager = CartManager::createWith(
                $cart->items,
                $cart->coupon_code,
                $cart->customer_id,
                $country,
                $state,
                $city,
                $zipCode,
                $shippingId
            );

            $cart->items = $cartManager->getItems();
            $cart->cross_sells = $cartManager->getCrossellProducts();

            $cart->shipping_method        = $cartManager->getShippingMethod();
            $cart->total_weight           = $cartManager->calculateTotalWeight();
            $cart->total_weight_with_unit = SettingsHelper::getWeightWithUnit($cart->total_weight); // @todo Need to update this for Unit
            $cart->sub_total              = (float) bcdiv($cartManager->calculateSubtotal() * 100, '100', 2);
            $cart->discounted_sub_total   = (float) bcdiv($cartManager->calculateDiscountedSubtotal() * 100, '100', 2);
            $cart->taxable_amount         = (float) bcdiv($cartManager->calculateTax() * 100, '100', 2);
            $cart->shipping_tax_rate      = (float) bcdiv($cartManager->calculateShippingTaxRate() * 100, '100', 2);
            $cart->shipping_tax           = (float) bcdiv($cartManager->calculateShippingTax() * 100, '100', 2);
            $cart->total                  = (float) bcdiv($cartManager->calculateTotal() * 100, '100', 2);
            $cart->coupon_discount        = (float) bcdiv($cartManager->calculateCouponDiscount() * 100, '100', 2);
            $cart->sales_tax              = $cart->taxable_amount - $cart->shipping_tax;

            Shop::formatWithCurrency($cart, ['sub_total', 'discounted_sub_total', 'taxable_amount', 'total', 'coupon_discount', 'shipping_tax', 'sales_tax']);
            return $cart;
        } catch (Throwable $error) {
            throw $error;
        }
    }

    private function getCartByToken($token)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $conditions = [
            $db->quoteName('token') . ' = ' . $db->quote($token),
        ];

        $query->select('*')->from($db->quoteName('#__easystore_cart'))
            ->where($conditions);
        $db->setQuery($query);

        try {
            return $db->loadObject();
        } catch (\Throwable $th) {
            return $th;
        }
    }

    private function getCartItemColumns()
    {
        return [
            'ci.quantity',
            'product.id AS product_id',
            'product.title',
            'product.alias',
            'product.catid',
            'product.regular_price',
            'product.has_sale',
            'product.quantity AS available_quantity',
            'product.is_tracking_inventory',
            'product.inventory_status AS product_inventory_status',
            'product.quantity AS product_inventory_amount',
            'product.discount_type',
            'product.discount_value',
            'product.enable_out_of_stock_sell',
            'product.has_variants',
            'product.weight',
            'product.unit',
            'product.is_taxable',
            'variant.id AS sku_id',
            'variant.combination_value',
            'variant.price AS price',
            'variant.weight AS sku_weight',
            'variant.unit AS sku_unit',
            'variant.image_id',
            'variant.inventory_status AS sku_inventory_status',
            'variant.inventory_amount AS sku_inventory_amount',
            'variant.is_taxable AS is_taxable_variant',
        ];
    }

    private function fetchCartItems($cartId, $orm, $db)
    {
        $columns = $this->getCartItemColumns();

        $products = $orm->setColumns(['id'])
            ->hasMany($cartId, ['#__easystore_cart_items', 'ci'], 'cart_id')
            ->updateQuery(function ($query) use ($db, $columns) {
                $query->select($columns)
                    ->join('LEFT', $db->quoteName('#__easystore_products', 'product') . ' ON ' . $db->quoteName('product.id') . ' = ' . $db->quoteName('ci.product_id'))
                    ->join('LEFT', $db->quoteName('#__easystore_product_skus', 'variant') . ' ON ' . $db->quoteName('variant.id') . ' = ' . $db->quoteName('ci.sku_id'));
            })->loadObjectList();

        if (empty($products)) {
            return [];
        }

        $products = array_map(function ($item) use ($orm, $db) {
            $item->options = EasyStoreHelper::detectProductOptionFromCombination(
                EasyStoreHelper::getProductOptionsById($item->product_id),
                $item->combination_value
            );
            return $this->populateItemImage($item, $orm, $db);
        }, $products);

        return $products;
    }

    private function populateItemImage($item, $orm, $db)
    {
        if (!empty($item->image_id)) {
            $item->image = $orm->setColumns(['id', 'src'])
                ->hasOne($item->image_id, '#__easystore_media', 'id')
                ->loadObject();
        } else {
            $item->image = $orm->setColumns(['id', 'src'])
                ->hasOne($item->product_id, '#__easystore_media', 'product_id')
                ->updateQuery(function ($query) use ($db) {
                    $query->where($db->quoteName('is_featured') . ' = 1');
                })->loadObject();
        }

        if (!empty($item->image) && !empty($item->image->src)) {
            $item->image->src = Uri::root(true) . '/' . Path::clean($item->image->src);
        }

        return $item;
    }

    /**
     * Calculate total weight of items in cart
     *
     * @return float Total weight in standard unit
     * 
     * @since 1.5.0
     */
    public function calculateTotalWeight()
    {
        $cart = $this->getCartByToken($this->getToken());
        
        if (empty($cart)) {
            return 0;
        }

        $db = $this->getDatabase();
        $orm = new EasyStoreDatabaseOrm();
        
        // Get cart items with weight information
        $columns = [
            'product.weight as product_weight',
            'product.unit as product_unit',
            'sku.unit as sku_unit', 
            'sku.weight as sku_weight',
            'ci.quantity',
            'ci.sku_id'
        ];

        $items = $orm->setColumns(['product_id', 'sku_id'])
            ->hasMany($cart->id, ['#__easystore_cart_items', 'ci'], 'cart_id')
            ->updateQuery(function ($query) use ($db, $columns) {
                $query->select($columns)
                    ->join('LEFT', $db->quoteName('#__easystore_products', 'product'), 
                        $db->quoteName('product.id') . ' = ' . $db->quoteName('ci.product_id'))
                    ->join('LEFT', $db->quoteName('#__easystore_product_skus', 'sku'),
                        $db->quoteName('sku.id') . ' = ' . $db->quoteName('ci.sku_id'));
            })->loadObjectList() ?? [];

        if (empty($items)) {
            return 0;
        }

        $standardUnit = SettingsHelper::getSettings()->get('products.standardUnits.weight', 'kg');
        $totalWeight = 0;

        foreach ($items as $item) {
            $itemWeight = $this->calculateItemWeight($item, $standardUnit);
            $totalWeight = bcadd($totalWeight, $itemWeight * (int) $item->quantity, 2);
        }

        return (float) $totalWeight;
    }

    /**
     * Calculate weight for a single cart item
     * 
     * @param object $item Cart item with weight information
     * @param string $standardUnit Standard weight unit
     * @return float Item weight in standard unit
     * 
     * @since 1.5.0
     */
    private function calculateItemWeight($item, $standardUnit)
    {
        // Set defaults for null values
        $item->product_weight = $item->product_weight ?? 0;
        $item->sku_weight = $item->sku_weight ?? 0;
        $item->product_unit = $item->product_unit ?? $standardUnit;
        $item->sku_unit = $item->sku_unit ?? $standardUnit;

        // If both weights are 0, return 0
        if ($item->product_weight === 0 && $item->sku_weight === 0) {
            return 0;
        }

        $weight = $item->product_weight;
        $unit = $item->product_unit;

        // Use SKU weight if exists, otherwise use product weight
        if (!empty($item->sku_id)) {
            $weight = $item->sku_weight;
            $unit = $item->sku_unit;
        }

        // Convert weight to standard unit if needed
        if ($unit !== $standardUnit) {
            $weight = EasyStoreHelper::convertWeight((float) $weight, $unit, $standardUnit);
        }

        return (float) $weight;
    }

    public function updateStatus(string $status)
    {
        $orm = new EasyStoreDatabaseOrm();

        $data = (object) [
            'token'  => $this->getToken(),
            'status' => $status,
        ];

        try {
            $orm->update('#__easystore_cart', $data, 'token');
        } catch (\Throwable $error) {
            throw $error;
        }
    }

    /**
     * Updates the cart data in the database.
     *
     * This function updates the cart data in the database using the provided data. It
     * sets the token for the data and then attempts to update the corresponding record
     * in the `#__easystore_cart` table using the ORM. If the update fails, it throws
     * the caught exception.
     *
     * @param object $data The data to update in the cart.
     * @return object Returns update data.
     * @throws \Throwable If there is an error during the update process.
     *
     * @since 1.0.0
     */
    public function update($data)
    {
        $orm         = new EasyStoreDatabaseOrm();
        $data->token = $this->getToken();

        try {
            return $orm->update('#__easystore_cart', $data, 'token');
        } catch (\Throwable $error) {
            throw $error;
        }
    }

    /**
     * Checks if the cart is empty.
     *
     * This function checks if there are any items in the cart. If the cart is not empty,
     * it returns true. If the cart is empty, it throws a RuntimeException.
     *
     * @return bool Returns true if the cart is not empty.
     * @throws \RuntimeException If the cart is empty.
     *
     * @since 1.2.0
     */
    public function checkCartStatus()
    {
        $cartItem = $this->getItem();

        if (!empty($cartItem) && !empty($cartItem->items)) {
            return true;
        }

        throw new \RuntimeException(Text::_("COM_EASYSTORE_CART_EMPTY"), 500);
    }

    /**
     * Removes the cart data from the database.
     *
     * This function retrieves the current token, constructs a query to delete the corresponding
     * cart data from the database, and executes the query. If the query executes successfully,
     * it removes the token. In case of an error, it throws the caught exception.
     *
     * @throws \Throwable If there is an error executing the database query.
     * @return void
     *
     * @since 1.2.0
     */
    public function removeCartData()
    {
        $token = $this->getToken();

        $db = $this->getDatabase();

        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__easystore_cart'))
            ->where($db->quoteName('token') . ' = :token')
            ->bind(':token', $token, ParameterType::STRING);

        $db->setQuery($query);

        try {
            $db->execute();
            $this->removeToken();
        } catch (\Throwable $error) {
            throw $error;
        }
    }

    public function setShippingMethods($shippingMethods)
    {
        $token = $this->getToken();
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__easystore_cart'))
            ->set($db->quoteName('shipping_method') . ' = ' . $db->quote(json_encode($shippingMethods)))
            ->where($db->quoteName('token') . ' = ' . $db->quote($token));
        $db->setQuery($query);

        try {
            $db->execute();
        } catch (\Throwable $error) {
            throw $error;
        }
    }

    public function getShippingMethods()
    {
        $token = $this->getToken();
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select($db->quoteName('shipping_method'))
            ->from($db->quoteName('#__easystore_cart'))
            ->where($db->quoteName('token') . ' = ' . $db->quote($token));
        $db->setQuery($query);

        try {
            return $db->loadResult();
        } catch (\Throwable $error) {
            throw $error;
        }
    }
}