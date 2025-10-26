<?php

/**
 * @package     EasyStore.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Concerns;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\Path;
use JoomShaper\Component\EasyStore\Administrator\Checkout\CartManager;
use JoomShaper\Component\EasyStore\Administrator\Checkout\OrderManager;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper as EasyStoreHelperSite;

trait OrderEditable
{
    public function updateAdminOrder()
    {
        $orderId    = $this->getInput('order_id', null, 'INT');
        $data       = $this->getInput('data', [], 'ARRAY');
        $customerId = $this->getInput('customer_id', null, 'INT');
        $shippingId = $this->getInput('shipping_id', null, 'STRING');
        $couponCode = $this->getInput('coupon_code', null, 'STRING');
        $country    = $this->getInput('country', null, 'STRING');
        $state      = $this->getInput('state', null, 'STRING');
        $city      = $this->getInput('city', null, 'STRING');
        $zipCode      = $this->getInput('zip_code', null, 'STRING');

        if (empty($orderId)) {
            $this->sendResponse(['message' => 'Missing order ID'], 400);
        }

        if (!empty($data)) {
            $data = array_map(function ($item) {
                return is_string($item) ? json_decode($item, true) : $item;
            }, $data);
        }

        $items      = $this->getProducts($data);
        $quantities = $this->getOrderQuantities($data);
        $items      = $this->syncQuantity($items, $quantities);
        $user       = EasyStoreHelper::getUserByCustomerId($customerId);

        if (!empty($user->shipping_address) && is_string($user->shipping_address)) {
            $shippingAddress = json_decode($user->shipping_address);
            $country         = $shippingAddress->country ?? null;
            $state           = $shippingAddress->state ?? null;
        }

        $cartManager = CartManager::createWith($items, $couponCode, $customerId, $country, $state, $city, $zipCode, $shippingId);

        $items        = $cartManager->getItems();
        $userId       = EasyStoreHelperSite::getCustomerById($customerId)->user_id ?? 0;
        $customer     = EasyStoreHelperSite::getCustomerByUserId($userId);
        $orderManager = OrderManager::createWith($orderId, $customer);
        $orderManager->setProducts($this->generateOrderItem($items, $data));
        $order = $orderManager->getOrderItemWithCalculation();

        if (!empty($customerId)) {
            $order->customer_id = $customerId;
        }

        $this->sendResponse($order);
    }

    private function generateOrderItem($items, $data)
    {
        return array_map(function ($item, $index) use ($data) {
            if (empty($item)) {
                return $item;
            }

            $item->cart_item = clone $item;
            unset($item->applied_coupon, $item->final_price, $item->is_coupon_applicable, $item->taxable_amount);
            $item->order_id   = 14;
            $item->product_id = $data[$index]['product_id'];
            $item->variant_id = $data[$index]['variant_id'];
            $item->quantity   = $data[$index]['quantity'];
            $item->options    = [];
            return $item;
        }, $items, array_keys($data));
    }

    private function updateOrCreateOrderItem($items)
    {
        //@TODO: will be implemented later.
    }

    private function syncQuantity($items, $quantities)
    {
        return array_map(function ($item) use ($quantities) {
            $item->quantity = $quantities[$item->product_id];
            return $item;
        }, $items);
    }

    private function getOrderProducts($orderId)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('product_id')
            ->from($db->quoteName('#__easystore_order_product_map'))
            ->where($db->quoteName('order_id') . ' = ' . $orderId);
        $db->setQuery($query);

        return $db->loadColumn() ?? [];
    }

    private function getOrderQuantities($data)
    {
        return array_reduce($data, function ($result, $item) {
            $result[$item['product_id']] = $item['quantity'];
            return $result;
        }, []);
    }

    private function getColumns()
    {
        return [
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
            'product.is_taxable',
            'variant.id AS sku_id',
            'variant.combination_value',
            'variant.price AS price',
            'variant.weight AS sku_weight',
            'variant.image_id',
            'variant.inventory_status AS sku_inventory_status',
            'variant.inventory_amount AS sku_inventory_amount',
            'variant.is_taxable AS is_taxable_variant',
        ];
    }

    private function getProducts($data)
    {
        if (empty($data)) {
            return [];
        }

        $productIds = array_column($data, 'product_id');

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select($this->getColumns())
            ->from($db->quoteName('#__easystore_products', 'product'))
            ->join('LEFT', $db->quoteName('#__easystore_product_skus', 'variant') . ' ON ' . $db->quoteName('product.id') . ' = ' . $db->quoteName('variant.product_id'))
            ->whereIn($db->quoteName('product.id'), $productIds);

        $condition = array_map(function ($item) use ($db) {
            if (!is_null($item['variant_id'])) {
                return '(' . $db->quoteName('product.id') . ' = ' . (int) $item['product_id'] . ' AND ' . $db->quoteName('variant.id') . ' = ' . (int) $item['variant_id'] . ')';
            }

            return '(' . $db->quoteName('product.id') . ' = ' . (int) $item['product_id'] . ' AND ' . $db->quoteName('variant.id') . ' IS NULL)';
        }, $data);

        $query->where('(' . implode(' OR ', $condition) . ')');

        $db->setQuery($query);
        $products = $db->loadObjectList();

        if (empty($products)) {
            return [];
        }

        return array_map(function ($item) {
            $item->quantity = 1;
            $item->id       = $item->product_id;
            $item->image    = $this->getProductImage($item);
            $item->options  = EasyStoreHelperSite::detectProductOptionFromCombination(
                EasyStoreHelperSite::getProductOptionsById($item->product_id),
                $item->combination_value
            );
            return $item;
        }, $products);
    }

    private function getProductImage($item)
    {
        $orm = new EasyStoreDatabaseOrm();
        $db  = Factory::getContainer()->get(DatabaseInterface::class);

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

        if (!isset($item->image->src)) {
            return null;
        }

        return $item->image->src;
    }
}
