<?php

/**
 * @package     COM_POSTNL
 * @subpackage  Model
 *
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace Simonisvis\Component\PostNL\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Order Model
 *
 * @since  1.0.0
 */
class OrderModel extends BaseDatabaseModel
{
    /**
     * Get order data
     *
     * @return  object|false  Order object or false on failure
     *
     * @since   1.0.0
     */
    public function getItem()
    {
        $app = \Joomla\CMS\Factory::getApplication();
        $orderId = $app->input->getInt('id', 0);

        if (!$orderId) {
            return false;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        // Get order from EasyStore
        $query->select('a.*')
            ->from($db->quoteName('#__easystore_orders', 'a'))
            ->where($db->quoteName('a.id') . ' = ' . (int) $orderId);

        $db->setQuery($query);
        $order = $db->loadObject();

        if (!$order) {
            return false;
        }

        // Get PostNL shipments for this order
        $query = $db->getQuery(true);
        $query->select('*')
            ->from($db->quoteName('#__postnl_shipments'))
            ->where($db->quoteName('order_id') . ' = ' . (int) $orderId)
            ->order($db->quoteName('created_date') . ' DESC');

        $db->setQuery($query);
        $order->postnl_shipments = $db->loadObjectList();

        // Parse JSON fields
        $order->shipping_address_data = json_decode($order->shipping_address ?? '{}');
        $order->billing_address_data = json_decode($order->billing_address ?? '{}');

        return $order;
    }

    /**
     * Get order items
     *
     * @param   int  $orderId  Order ID
     *
     * @return  array  Order items
     *
     * @since   1.0.0
     */
    public function getOrderItems(int $orderId): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        // Select from order_product_map and join with products for title
        $query->select('opm.*, p.title as product_title, ps.combination_name as variant_title, ps.sku as variant_sku')
            ->from($db->quoteName('#__easystore_order_product_map', 'opm'))
            ->join('LEFT', $db->quoteName('#__easystore_products', 'p') . ' ON p.id = opm.product_id')
            ->join('LEFT', $db->quoteName('#__easystore_product_skus', 'ps') . ' ON ps.id = opm.variant_id')
            ->where($db->quoteName('opm.order_id') . ' = ' . (int) $orderId);

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }
}
