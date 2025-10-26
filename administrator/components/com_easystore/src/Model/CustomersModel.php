<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Model;

use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Filesystem\Path;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


class CustomersModel extends ListModel
{
    /**
     * Constructor.
     *
     *
     * @param   array   $config   An optional associative array of configuration settings.
     *
     * @since   1.0.0
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id',
                'a.id',
                'name',
                'u.name',
                'created',
                'a.created',
                'created_by',
                'a.created_by',
                'user_type',
                'a.user_type',
                'phone',
                'a.phone',
                'user_id',
                'a.user_id',
                'orders',
                'total_spend',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function populateState($ordering = 'a.id', $direction = 'asc')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        // List state information.
        parent::populateState($ordering, $direction);
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string  $id  A prefix for the store id.
     *
     *
     * @since   1.0.0
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');

        return parent::getStoreId($id);
    }

    /**
     * Method to create a query for a list of items.
     *
     * @return  DatabaseQuery
     *
     * @since  1.0.0
     */
    protected function getListQuery()
    {
        // Create a new query object.
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        // Select the required fields from the table.
        $query->select(
            [
            $this->getState(
                'list.select',
                'a.id, u.name, a.user_type, u.username' .
                ', a.created_by, u.email, a.phone, a.image, a.user_id'
            ),
            'COUNT(DISTINCT o.id) as orders', 'SUM(opm.quantity * opm.price) AS total_spend', ]
        );

        $query->from($db->quoteName('#__easystore_users', 'a'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('a.user_id') . ' = ' . $db->quoteName('u.id'))
            ->join('LEFT', $db->quoteName('#__easystore_orders', 'o') . ' ON ' . $db->quoteName('a.id') . ' = ' . $db->quoteName('o.customer_id'))
            ->join('LEFT', $db->quoteName('#__easystore_order_product_map', 'opm') . ' ON ' . $db->quoteName('opm.order_id') . ' = ' . $db->quoteName('o.id'));
        $query->group($db->quoteName('a.id'));


        // Filter by search in title
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $ids = (int) substr($search, 3);
                $query->where($db->quoteName('a.id') . ' = :id')
                ->bind(':id', $ids, ParameterType::INTEGER);
            } else {
                $search = '%' . str_replace(' ', '%', trim($search)) . '%';
                // Add the clauses to the query.
                $query->where(
                    '(' . $db->quoteName('u.name') . ' LIKE :name)'
                )
                    ->bind(':name', $search);
            }
        }


        // Add the list ordering clause
        $listOrdering = $this->getState('list.ordering', 'a.id');
        $listDirn     = $db->escape($this->getState('list.direction', 'ASC'));

        $query->order($db->escape($listOrdering) . ' ' . $listDirn);

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

        foreach ($items as $item) {
            $item->image       = ($item->image) ? Uri::root(true) . '/' . Path::clean($item->image) : '';
            $item->total_spend = ($item->total_spend) ?: 0;
        }
        return $items;
    }
}
