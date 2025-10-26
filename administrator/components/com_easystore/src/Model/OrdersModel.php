<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Model;

use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;
use Joomla\CMS\MVC\Model\ListModel;
use JoomShaper\Component\EasyStore\Administrator\Constants\Status;
use JoomShaper\Component\EasyStore\Administrator\Checkout\OrderManager;
use JoomShaper\Component\EasyStore\Administrator\Helper\CustomInvoiceHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Site\Helper\OrderHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * This models supports retrieving a list of orders.
 *
 * @since  1.0.0
 */
class OrdersModel extends ListModel
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
                'customer_name',
                'payment_status',
                'a.payment_status',
                'fulfilment',
                'a.fulfilment',
                'published',
                'a.published',
                'a.access',
                'access_title',
                'a.created',
                'created',
                'order_status',
                'a.order_status',
                'custom_invoice_id',
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
    protected function populateState($ordering = 'a.id', $direction = 'desc')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);

        $paymentStatus = $this->getUserStateFromRequest($this->context . '.filter.payment_status', 'filter_payment_status', '');
        $this->setState('filter.payment_status', $paymentStatus);

        $fulfilment = $this->getUserStateFromRequest($this->context . '.filter.fulfilment', 'filter_fulfilment', '');
        $this->setState('filter.fulfilment', $fulfilment);

        $access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
        $this->setState('filter.access', $access);

        $customInvoiceId = $this->getUserStateFromRequest($this->context . '.filter.custom_invoice_id', 'filter_custom_invoice_id', '');
        $this->setState('filter.custom_invoice_id', $customInvoiceId);

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
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.payment_status');
        $id .= ':' . $this->getState('filter.fulfilment');
        $id .= ':' . $this->getState('filter.access');
        $id .= ':' . $this->getState('filter.custom_invoice_id');

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
        $user  = $this->getCurrentUser();

        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                'a.*'
            )
        );
        $query->from($db->quoteName('#__easystore_orders', 'a'));

        // Join over the users for the checked out user.
        $query->select($db->quoteName('uc.name', 'editor'))
            ->join('LEFT', $db->quoteName('#__users', 'uc'), $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));

        // Join over the users for the author.
        $query->select([$db->quoteName('ua.name', 'customer_name'), $db->quoteName('ug.title', 'access_title')])
            ->join('LEFT', $db->quoteName('#__easystore_users', 'eu'), $db->quoteName('eu.id') . ' = ' . $db->quoteName('a.customer_id'))
            ->join('LEFT', $db->quoteName('#__users', 'ua'), $db->quoteName('ua.id') . ' = ' . $db->quoteName('eu.user_id'))
            ->join('LEFT', $db->quoteName('#__viewlevels', 'ug'), $db->quoteName('ug.id') . ' = ' . $db->quoteName('a.access'));

        // Filter by access level.
        if ($access = (int) $this->getState('filter.access')) {
            $query->where($db->quoteName('a.access') . ' = :access')
                ->bind(':access', $access, ParameterType::INTEGER);
        }

        // Filter by published state
        $published = (string) $this->getState('filter.published');

        if (is_numeric($published)) {
            $published = (int) $published;
            $query->where($db->quoteName('a.published') . ' = :published')
                ->bind(':published', $published, ParameterType::INTEGER);
        } elseif ($published === '') {
            $query->whereIn($db->quoteName('a.published'), [Status::UNPUBLISHED, Status::PUBLISHED]);
        }

        // Filter by payment status
        $paymentStatus = (string) $this->getState('filter.payment_status');
        if (!empty($paymentStatus)) {
            $query->where($db->quoteName('a.payment_status') . ' = :payment_status')
                ->bind(':payment_status', $paymentStatus, ParameterType::STRING);
        }

        // Filter by fulfilment
        $fulfilment = (string) $this->getState('filter.fulfilment');
        if (!empty($fulfilment)) {
            $query->where($db->quoteName('a.fulfilment') . ' = :fulfilment')
                ->bind(':fulfilment', $fulfilment, ParameterType::STRING);
        }

        // Filter by search in title
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $id = substr($search, 3);
                $id = OrderHelper::parseOrderNumber($id);
                $query->where($db->quoteName('a.id') . ' = :id')
                    ->bind(':id', $id, ParameterType::STRING);
            } else {
                $search = '%' . str_replace(' ', '%', trim($search)) . '%';
                $query->where('(' . $db->quoteName('a.fulfilment') . ' LIKE :search1 OR ' . $db->quoteName('a.payment_status') . ' LIKE :search2 OR ' . $db->quoteName('a.order_status') . ' LIKE :search3 OR ' . $db->quoteName('ua.name') . 'LIKE :search4)')
                    ->bind([':search1', ':search2', ':search3', ':search4'], $search);
            }
        }

        // Filter by custom invoice ID
        $customInvoiceId = (string) $this->getState('filter.custom_invoice_id');
        if (!empty($customInvoiceId)) {
            $customInvoiceId = '%' . trim($customInvoiceId) . '%';
            $query->where($db->quoteName('a.custom_invoice_id') . ' LIKE :custom_invoice_id')
                ->bind(':custom_invoice_id', $customInvoiceId, ParameterType::STRING);
        }

        // Add the list ordering clause
        $listOrdering = $this->getState('list.ordering', 'a.ordering');
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
            $orderManager = OrderManager::createWith($item->id);
            $item->total = $orderManager->calculateTotal();
            $item->total_with_currency = EasyStoreHelper::formatCurrency($item->total);

            if ($item->is_guest_order) {
                $guestUserText       = Text::_("COM_EASYSTORE_GUEST_USER");
                $customerName        = !empty($item->shipping_address) ? json_decode($item->shipping_address)->name : null;
                $item->customer_name = !is_null($customerName) ? $customerName . ' (' . $guestUserText . ')' : $guestUserText;
            }

            if (isset($item->custom_invoice_id) && !empty($item->custom_invoice_id)) {
                $item->custom_invoice_id = CustomInvoiceHelper::getGeneratedCustomInvoiceId($item->custom_invoice_id);
            }
        }

        return $items;
    }
}
