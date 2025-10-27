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

use Joomla\CMS\MVC\Model\ListModel;

/**
 * Orders List Model
 *
 * @since  1.0.0
 */
class OrdersModel extends ListModel
{
    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @since   1.0.0
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'order_number', 'a.order_number',
                'customer_email', 'a.customer_email',
                'order_status', 'a.order_status',
                'tracking_number', 'a.tracking_number',
                'creation_date', 'a.creation_date',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function populateState($ordering = 'a.creation_date', $direction = 'DESC')
    {
        // List state information.
        parent::populateState($ordering, $direction);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  \Joomla\Database\QueryInterface
     *
     * @since   1.0.0
     */
    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        // Select from EasyStore orders table
        $query->select('a.*')
            ->from($db->quoteName('#__easystore_orders', 'a'));

        // Join with PostNL shipments (left join - not all orders have shipments)
        $query->select('p.barcode AS postnl_barcode, p.status AS postnl_status, p.created_date AS postnl_created')
            ->join('LEFT', $db->quoteName('#__postnl_shipments', 'p') . ' ON p.order_id = a.id');

        // Filter by search
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where($db->quoteName('a.id') . ' = ' . (int) substr($search, 3));
            } else {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
                $query->where(
                    '(' . $db->quoteName('a.order_number') . ' LIKE ' . $search
                    . ' OR ' . $db->quoteName('a.customer_email') . ' LIKE ' . $search
                    . ' OR ' . $db->quoteName('a.tracking_number') . ' LIKE ' . $search . ')'
                );
            }
        }

        // Filter by order status
        $status = $this->getState('filter.order_status');
        if (!empty($status)) {
            $query->where($db->quoteName('a.order_status') . ' = ' . $db->quote($status));
        }

        // Filter by tracking (has tracking number or not)
        $hasTracking = $this->getState('filter.has_tracking');
        if ($hasTracking === '1') {
            $query->where($db->quoteName('a.tracking_number') . ' IS NOT NULL');
            $query->where($db->quoteName('a.tracking_number') . ' != ' . $db->quote(''));
        } elseif ($hasTracking === '0') {
            $query->where('(' . $db->quoteName('a.tracking_number') . ' IS NULL OR ' . $db->quoteName('a.tracking_number') . ' = ' . $db->quote('') . ')');
        }

        // Add the list ordering clause
        $orderCol  = $this->state->get('list.ordering', 'a.creation_date');
        $orderDirn = $this->state->get('list.direction', 'DESC');

        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}
