<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Site\Model;

use Exception;
use Throwable;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;
use JoomShaper\Component\EasyStore\Site\Traits\ProductMedia;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Concerns\Taxable;
use JoomShaper\Component\EasyStore\Administrator\Checkout\OrderManager;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper as EasyStoreAdminHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class OrderModel extends ItemModel
{
    use ProductMedia;
    use Taxable;

    /**
     * Model context string.
     *
     * @var    string
     * @since  1.0.0
     */
    public $_context = 'com_easystore.order';

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @since   1.6
     *
     * @return void
     */
    protected function populateState()
    {
        $app = Factory::getApplication();

        // Load state from the request.
        $pk = $app->getInput()->getInt('id');
        $this->setState('order.id', $pk);
    }

    /**
     * Method to get a single record.
     *
     * @param   int  $pk  The id of the primary key.
     *
     * @return  mixed
     *
     * @since   1.0.0
     */
    public function getItem($pk = null)
    {
        $loggedUser     = $this->getCurrentUser();
        $customer       = EasyStoreHelper::getCustomerByUserId($loggedUser->id);
        $pk             = (int) ($pk ?: $this->getState('order.id'));

        /** @var CMSApplication */
        $app = Factory::getApplication();
        $input = $app->getInput();
        $token = $input->getString('guest_token', '');

        $orderManager = OrderManager::createWith($pk, $customer, $token);

        try {
            $item = $orderManager->getOrderItemWithCalculation();

            if ($orderManager->isGuestOrder() && empty($token)) {
                throw new Exception("Order with ID $pk not found. Check if you are landing at the right place.", 404);
            }
        } catch (Throwable $error) {
            throw $error;
        }

        if (SettingsHelper::getSellerTaxId()) {
            $item->seller_tax_id = SettingsHelper::getSellerTaxId();
        }   

        return $item;
    }


    /**
     * Retrieves the items for reordering based on a given order ID and optional location parameters.
     *
     * This function fetches the details of an order, including the items in the order, and prepares the data
     * for reordering. It considers the settings for weight unit, tax, and coupon code enabled states. It also
     * calculates various attributes such as total weight, discounted prices, and formatted currency values.
     *
     * @param int $orderId The ID of the order for which items are to be retrieved.
     *
     * @return object|null The order object with item details and additional calculated attributes, or null if the order is not found.
     */
    public function getOrderItemForPayment($orderId)
    {
        $orderManager = OrderManager::createWith($orderId);
        $order = $orderManager->getOrderItemWithCalculation();

        if (isset($order->products)) {
            $order->items = array_map(function ($item) {
                return $item->cart_item;
            }, $order->products);

            unset($order->products);
        }

        return $order;
    }

    /**
     * Checks if a given token is unique in the `#__easystore_orders` table.
     *
     * @param string $token The token to be checked for uniqueness.
     *
     * @return bool True if the token is unique, false otherwise.
     * @throws RuntimeException If there is a database error.
     *
     * @since 1.2.2
     */
    public function isTokenUnique($token)
    {
        try {
            // Get the Joomla database object
            $db = $this->getDatabase();

            // Build the query to check for token uniqueness
            $query = $db->getQuery(true);
            $query->select('COUNT(*)')
                ->from($db->quoteName('#__easystore_orders'))
                ->where($db->quoteName('order_token') . ' = ' . $db->quote($token));

            // Set the query and execute it
            $db->setQuery($query);
            $count = $db->loadResult();

            // Return true if the token is unique (count is 0), false otherwise
            return $count == 0;
        } catch (\RuntimeException $e) {
            // Handle the exception by logging the error and rethrowing it
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            throw new \RuntimeException('Database error occurred while checking token uniqueness.');
        }
    }

    /**
     * Get product details of an order
     *
     * @param  int  $id     Order ID
     * @return CMSObject|bool  Object on success, false on failure.
     * @since  1.0.0
     */
    public function getProducts($id)
    {
        // Create a new query object.
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select($db->quoteName(['p.id', 'p.title', 'p.has_variants']))
            ->from($db->quoteName('#__easystore_products', 'p'));

        $query->select($db->quoteName(['ord_pro_map.quantity', 'ord_pro_map.discount_type', 'ord_pro_map.discount_value', 'ord_pro_map.price', 'ord_pro_map.variant_id', 'ord_pro_map.cart_item']))
            ->join('LEFT', $db->quoteName('#__easystore_order_product_map', 'ord_pro_map'), $db->quoteName('ord_pro_map.product_id') . ' = ' . $db->quoteName('p.id'))
            ->where($db->quoteName('ord_pro_map.order_id') . ' = ' . $id);

        $db->setQuery($query);

        try {
            $result = $db->loadObjectList();

            foreach ($result as &$product) {
                $media          = $this->getMedia($product->id);
                $product->media = $media;

                if (!empty($product->cart_item) && is_string($product->cart_item)) {
                    $product->cart_item = json_decode($product->cart_item);
                }

                if (!empty($product->variant_id)) {
                    $query->clear();

                    $query->select($db->quoteName(['prod_sku.combination_name', 'prod_sku.combination_value', 'prod_sku.price', 'prod_sku.inventory_status', 'prod_sku.inventory_amount', 'prod_sku.sku', 'prod_sku.weight', 'prod_sku.unit']))
                        ->from($db->quoteName('#__easystore_product_skus', 'prod_sku'))
                        ->where($db->quoteName('prod_sku.id') . ' = ' . $product->variant_id)
                        ->where($db->quoteName('prod_sku.product_id') . ' = ' . $product->id);

                    $db->setQuery($query);
                    $variantData = $db->loadObject();

                    if (!empty($variantData)) {
                        $product->variant_data                      = $variantData;
                        $product->variant_data->price_with_currency = EasyStoreAdminHelper::formatCurrency($variantData->price);
                        $product->variant_data->weight_with_unit    = !empty($variantData->weight) ? SettingsHelper::getWeightWithUnit($variantData->weight, $variantData->unit) : '';

                        $product->variant_data->options = EasyStoreHelper::detectProductOptionFromCombination(
                            EasyStoreHelper::getProductOptionsById($product->id),
                            $variantData->combination_value
                        );
                    }
                }
            }

            unset($product);

            return $result;
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
