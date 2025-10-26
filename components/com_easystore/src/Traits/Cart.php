<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Traits;

use Throwable;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper as AdministratorEasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

trait Cart
{
    /**
     * Add items to the cart
     *
     * @return void
     */
    public function addToCart()
    {
        $requestMethod = $this->getInputMethod();
        $this->checkNotAllowedMethods(['GET', 'PATCH', 'PUT', 'DELETE'], $requestMethod);
        $app     = Factory::getApplication();
        $input     = $app->input;
        $items     = $input->get('items', '[]', 'RAW');
        $items     = !empty($items) && is_string($items) ? json_decode($items) : [];
        $initiator = $input->get('initiator', '', 'STR');

        if (empty($initiator)) {
            $this->sendResponse(['message' => 'Invalid initiator value!'], 400);
        }

        $instance = $this->getCartInstance();
        $cart     = $instance[0];
        $isMerged = $instance[1];

        $result = $this->addCartItems($cart->id, $items, $initiator, $isMerged);

        if ($initiator == 'mini-cart') {
            $settings   = SettingsHelper::getSettings();
            $shopPage   = $settings->get('products.shopPage', 'index.php');
            $menuId = EasyStoreHelper::getMenuItemId($shopPage);

            if (!empty($menuId)) {
                $shopPage .= '&Itemid=' . $menuId;
            }

            $result = ['link' => Route::_($shopPage)];
        }

        if (!is_array($result)) {
            $this->sendResponse(['message' => $result], 422);
        }

        $this->sendResponse($result);
    }

    /**
     * Creates a new cart.
     *
     * @param object|null $customer The customer object. If null, it creates a cart for a guest user.
     * @return object The object of the newly created cart.
     * @throws Throwable If an error occurs during cart creation.
     */
    protected function createNewCart($customer = null)
    {
        $cartData = (object) [
            'id'          => 0,
            'customer_id' => $customer ? $customer->id : null,
            'token'       => $this->createToken(),
            'created'     => Factory::getDate('now')->toSql(true),
            'modified'    => Factory::getDate('now')->toSql(true),
            'created_by'  => $customer ? $customer->user_id : 0,
            'modified_by' => $customer ? $customer->user_id : 0,
        ];

        $orm = new EasyStoreDatabaseOrm();

        try {
            return $orm->create('#__easystore_cart', $cartData, 'id');
        } catch (Throwable $error) {
            throw $error;
        }
    }

    /**
     * Update existing cart items
     *
     * @param object $data
     * @return object
     * @throws Throwable
     */
    protected function updateExistingCart(object $data)
    {
        $orm = new EasyStoreDatabaseOrm();

        try {
            return $orm->update('#__easystore_cart', $data, 'id');
        } catch (Throwable $error) {
            throw $error;
        }
    }

    /**
     * Sync carts if two carts are sync-able
     *
     * @param object $customer
     * @return void
     */
    public function syncCarts($customer)
    {
        $customerCart = $this->getCartByCustomerId($customer->id);
        $tokenCart    = $this->getCartByToken($this->getToken());

        if (!$customerCart || !$tokenCart) {
            return;
        }

        if ($customerCart->token === $tokenCart->token) {
            return;
        }

        try {
            $this->mergeCarts($customerCart->id, $tokenCart->id);
            $this->removeCart($tokenCart->id);
        } catch (Throwable $error) {
            throw $error;
        }
    }

    /**
     * Get the cart instance and the isMerged carts status.
     *
     * @return array
     */
    public function getCartInstance()
    {
        $user     = Factory::getApplication()->getIdentity();
        $customer = EasyStoreHelper::getCustomerByUserId($user->id);
        $isMerged = false;

        if (!$customer) {
            if (!$this->hasToken()) {
                return [$this->createNewCart(), $isMerged];
            }

            $tokenCart = $this->getCartByToken($this->getToken());

            if ($tokenCart) {
                return [$tokenCart, $isMerged];
            }

            $this->removeToken();

            return [$this->createNewCart(), $isMerged];
        } else {
            $customerCart = $this->getCartByCustomerId($customer->id);

            if ($customerCart) {
                if ($customerCart->token !== $this->getToken()) {
                    $this->setToken($customerCart->token);
                    $this->syncCarts($customer);
                    $isMerged = true;
                }

                return [$customerCart, $isMerged];
            }

            $tokenCart = $this->getCartByToken($this->getToken());

            if ($tokenCart) {
                $tokenCart->customer_id = $customer->id;

                return [$this->updateExistingCart($tokenCart), $isMerged];
            }

            return [$this->createNewCart($customer), $isMerged];
        }
    }

    /**
     * Get the cart instance by (token|customer_id) and the respective value.
     *
     * @param string $key
     * @param string $value
     * @return object | null
     */
    protected function getCart($key, $value)
    {
        /** @var DatabaseInterface */
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('*')
            ->from($db->quoteName('#__easystore_cart'))
            ->where($db->quoteName($key) . ' = ' . $db->quote($value));

        $db->setQuery($query);

        try {
            return $db->loadObject() ?? null;
        } catch (Throwable $error) {
            return null;
        }
    }

    /**
     * Remove cart by id
     *
     * @param int $cartId
     * @return bool
     * @throws Throwable
     */
    protected function removeCart($cartId)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->delete('#__easystore_cart')
            ->where($db->quoteName('id') . ' = ' . (int) $cartId);

        $db->setQuery($query);

        try {
            $db->execute();

            return true;
        } catch (Throwable $error) {
            throw $error;
        }
    }

    /**
     * Merge two carts by cart ids
     *
     * @param int $toCartId
     * @param int $fromCartId
     * @return bool
     * @throws Throwable
     */
    protected function mergeCarts(int $toCartId, int $fromCartId)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->update($db->quoteName('#__easystore_cart_items'))
            ->set([
                $db->quoteName('cart_id') . ' = ' . $toCartId,
            ])
            ->where($db->quoteName('cart_id') . ' = ' . (int) $fromCartId);

        $db->setQuery($query);

        try {
            $db->execute();

            return true;
        } catch (Throwable $error) {
            throw $error;
        }
    }

    /**
     * Get cart by customer ID
     *
     * @param int $value
     * @return object
     */
    protected function getCartByCustomerId($value)
    {
        return $this->getCart('customer_id', $value);
    }

    /**
     * Get cart by token
     *
     * @param string $value
     * @return object
     */
    protected function getCartByToken($value)
    {
        return $this->getCart('token', $value);
    }

    protected function addCartItems(int $cartId, array $items, string $initiator, bool $isMerged = false)
    {
        $orm  = new EasyStoreDatabaseOrm();
        $user = Factory::getApplication()->getIdentity();
        $db   = Factory::getContainer()->get(DatabaseInterface::class);

        if (empty($items)) {
            $this->removeExistingItems($cartId);
        }

        $ids = array_map(function ($item) {
            return $item->id ?? null;
        }, $items);

        $ids = array_values(array_filter($ids, function ($id) {
            return !is_null($id);
        }));

        $db->transactionStart();

        try {
            $removedIds = $this->dropRemovedItems($ids, $cartId);

            if (!empty($removedIds) && ($initiator === 'cart-page' || $initiator === 'mini-cart') && !$isMerged) {
                $orm->removeByIds('#__easystore_cart_items', $removedIds, 'id');
            }
        } catch (\Exception $error) {
            $db->transactionRollback();
            return [];
        }

        $result = [];

        foreach ($items as $item) {
            $cartItemData = (object) [
                'id'          => $item->id ?? 0,
                'cart_id'     => $cartId,
                'product_id'  => $item->product_id,
                'sku_id'      => $item->sku_id ?? null,
                'quantity'    => $item->quantity ?? 1,
                'modified'    => Factory::getDate('now')->toSql(),
                'modified_by' => $user->id ?? null,
            ];

            $product = $this->getProductInformation($item->product_id, $item->sku_id ?? null);

            if (empty($item->id)) {
                $cartItemData->created    = Factory::getDate('now')->toSql();
                $cartItemData->created_by = $user->id ?? null;
            }

            $cartItem = $this->getCartItem($cartId, $item->product_id, $item->sku_id ?? null);

            if (!is_null($cartItem)) {
                if ($initiator === 'cart-button') {
                    $cartItemData->quantity += (int) $cartItem->quantity;
                }

                $cartItemData->id = $cartItem->id;
            }

            if (!$product->enable_out_of_stock_sell) {
                if ($product->is_tracking_inventory) {
                    if ($cartItemData->quantity > $product->inventory_amount) {
                        $db->transactionRollback();

                        return Text::sprintf(
                            !empty($product->combination_name) ? 'COM_EASYSTORE_CART_OUT_OF_INVENTORY_AMOUNT_WITH_VARIANTS' : 'COM_EASYSTORE_CART_OUT_OF_INVENTORY_AMOUNT',
                            $product->title,
                            $product->combination_name ?? ''
                        );
                    }
                } else {
                    if (!$product->inventory_status) {
                        $db->transactionRollback();
                        return Text::_('COM_EASYSTORE_CART_OUT_OF_STOCK');
                    }
                }
            }

            try {
                $orm->updateOrCreate('#__easystore_cart_items', $cartItemData, 'id');
            } catch (\Exception $error) {
                $db->transactionRollback();
                $this->sendResponse(['message' => $error->getMessage()], 500);
            }

            $result[] = AdministratorEasyStoreHelper::formatCurrency((float) $product->discounted_price * (int) $cartItemData->quantity);
        }

        $db->transactionCommit();

        return $result;
    }

    protected function removeExistingItems($cartId)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__easystore_cart_items'))->where($db->quoteName('cart_id') . ' = ' . (int) $cartId);
        $db->setQuery($query);

        try {
            $db->execute();
        } catch (Throwable $error) {
            throw $error;
        }
    }

    protected function getProductInformation($productId, $skuId)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select([
            'product.title',
            'product.regular_price',
            'product.has_sale',
            'product.discount_type',
            'product.discount_value',
            'product.has_variants',
            'product.is_tracking_inventory',
            'product.quantity as inventory_amount',
            'product.inventory_status',
            'product.enable_out_of_stock_sell',
            'product.is_taxable'
        ])->from($db->quoteName('#__easystore_products', 'product'))
            ->where($db->quoteName('product.id') . ' = ' . (int) $productId);

        if (!is_null($skuId)) {
            $query->select([
                'sku.price as sku_price',
                'sku.inventory_status as sku_inventory_status',
                'sku.inventory_amount as sku_inventory_amount',
                'sku.combination_name',
                'sku.is_taxable as is_taxable_variant'
            ])->join('LEFT', $db->quoteName('#__easystore_product_skus', 'sku') . ' ON (' . $db->quoteName('sku.product_id') . ' = ' . $db->quoteName('product.id') . ')')
                ->where($db->quoteName('sku.id') . ' = ' . (int) $skuId);
        }

        $db->setQuery($query);

        $product = $db->loadObject();

        if (!empty($product->has_variants)) {
            $product->price = $product->sku_price ?? 0;
        } else {
            $product->price = $product->regular_price;
        }

        $product->price            = (float) $product->price;
        $product->discounted_price = $product->price;

        if ($product->has_sale) {
            $product->discounted_price = AdministratorEasyStoreHelper::calculateDiscountedPrice($product->discount_type, $product->discount_value, $product->price);
        }

        if ($product->has_variants && isset($product->sku_inventory_status, $product->sku_inventory_amount)) {
            $product->inventory_status = $product->sku_inventory_status;
            $product->inventory_amount = $product->sku_inventory_amount;
        }

        unset(
            $product->sku_inventory_status,
            $product->sku_inventory_amount
        );

        return $product;
    }

    protected function getCartItem($cartId, $productId, $skuId)
    {
        /** @var DatabaseInterface */
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select(['id', 'quantity'])
            ->from($db->quoteName('#__easystore_cart_items'))
            ->where($db->quoteName('cart_id') . ' = ' . (int) $cartId)
            ->where($db->quoteName('product_id') . ' = ' . (int) $productId);

        if (!is_null($skuId)) {
            $query->where($db->quoteName('sku_id') . ' = ' . (int) $skuId);
        }

        try {
            $db->setQuery($query);

            return $db->loadObject() ?? null;
        } catch (\Exception $error) {
            return null;
        }
    }

    protected function dropRemovedItems(array $ids, int $cartId)
    {
        if (empty($ids)) {
            return [];
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('id')->from($db->quoteName('#__easystore_cart_items'))
            ->where($db->quoteName('cart_id') . ' = ' . (int) $cartId);
        $query->where($db->quoteName('id') . ' NOT IN (' . implode(',', $ids) . ')');

        $db->setQuery($query);

        try {
            return $db->loadColumn() ?? [];
        } catch (\Throwable $error) {
            throw $error;
        }
    }
}

// workflow
// Guest user:
// 1. Check token exists locally
// 2.   If not exists
// 3.       create a new token and create a new cart instance and return it
// 4.   If exists
// 5.       Check if the token exists in the database
// 6.           If exists
// 7.               Get the cart instance from Database
// 8.           If not exists
// 9.               create a new cart instance by this token

// Logged in customer:
// 0. Sync cart instances
// 1. Check any cart instance found by the customer id
// 2.   If exists
// 3.       Get the cart instance from the database
// 4.           If token mismatched
// 5.               Set the database token to the local environment
// 6.               Sync carts created by same customer
// 7.   If not exists
// 8.       Check cart instance exists by the token
// 9.           If exists
// 10.               Update the cart instance and set the customer id and get the instance
// 11.          If not exists
// 12.              Create a new cart instance by a new token (or existing token) and return it
