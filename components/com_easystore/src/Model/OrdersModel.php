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
use Joomla\CMS\MVC\Model\ListModel;
use JoomShaper\Component\EasyStore\Administrator\Checkout\OrderManager;
use JoomShaper\Component\EasyStore\Administrator\Helper\CustomInvoiceHelper;
use JoomShaper\Component\EasyStore\Administrator\Model\ProductModel;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper as EasystoreAdminHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class OrdersModel extends ListModel
{
    /**
     * Model context string.
     *
     * @var    string
     * @since  1.0.0
     */
    public $_context = 'com_easystore.orders';

    /**
     * Method to build an SQL query to load the list data.
     *
     * @return  DatabaseQuery  An SQL query
     *
     * @since   1.0.0
     */
    protected function getListQuery()
    {
        $app         = Factory::getApplication();
        $loginUserId = $app->getIdentity()->id;

        $easystoreUser = EasyStoreDatabaseOrm::get('#__easystore_users', 'user_id', $loginUserId)->loadObject();

        // Create a new query object.
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select(['*'])
            ->from($db->quoteName('#__easystore_orders', 'o'))
            ->where($db->quoteName('o.customer_id') . ' = ' . $db->quote($easystoreUser->id ?? ''))
            ->where($db->quoteName('o.published') . ' = 1')
            ->order($db->quoteName('o.id') . 'DESC');

        return $query;
    }

    /**
     * Method to get an array of data items.
     *
     * @return  mixed  An array of data items on success, false on failure.
     *
     * @since   1.0.0
     */
    public function getItems()
    {
        $items = parent::getItems();
        $items = $this->getProductsOfItems($items);

        foreach ($items as $item) {
            $orderManager = OrderManager::createWith($item->id);
            $item->total = $orderManager->calculateTotal();
            $item->total_with_currency = EasystoreAdminHelper::formatCurrency($item->total);
            $item->published  = EasystoreAdminHelper::getOrderStatusName($item->order_status);
            
            if (isset($item->custom_invoice_id) && !empty($item->custom_invoice_id)) {
                $item->custom_invoice_id = CustomInvoiceHelper::getGeneratedCustomInvoiceId($item->custom_invoice_id);
            }
        }

        return $items;
    }

    /**
     * Function to get Products info of every Item
     *
     * @param Object $items
     * @return object
     */
    private function getProductsOfItems($items)
    {
        $orm = new EasyStoreDatabaseOrm();

        foreach ($items as &$item) {
            $products = $orm->hasMany($item->id, '#__easystore_order_product_map', 'order_id')
                ->loadObjectList();
            $item->products = $products;

            foreach ($products as &$product) {
                $productData    = ProductModel::getProductDataWithImage($product->product_id, $item->id);
                
                if (!empty($productData)) {
                    $product->image = $productData->image;
                }

                if (!empty($product->cart_item) && is_string($product->cart_item)) {
                    $product->cart_item = json_decode($product->cart_item);
                }
            }

            unset($product);
        }

        unset($item);

        return $items;
    }
}
