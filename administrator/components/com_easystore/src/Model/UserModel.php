<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Throwable;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use JoomShaper\Component\EasyStore\Administrator\Traits\User;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;

class UserModel extends AdminModel
{
    use User;

    /**
     * @var    string  The prefix to use with controller messages.
     * @since  1.0.0
     */
    protected $text_prefix = 'COM_EASYSTORE';

    /**
     * @var    string  The type alias for this content type.
     * @since  1.0.0
     */
    public $typeAlias = 'com_easystore.user';

    /**
     * Method to test whether a record can be deleted.
     *
     * @param   object  $record  A record object.
     *
     * @return  bool  True if allowed to delete the record. Defaults to the permission set in the component.
     *
     * @since   1.0.0
     */
    protected function canDelete($record)
    {
        if (empty($record->id) || $record->published != -2) {
            return false;
        }

        return parent::canDelete($record);
    }

    /**
     * Auto-populate the model state.
     *
     * @note Calling getState in this method will result in recursion.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function populateState()
    {
        $app = Factory::getApplication();

        // Load the User state.
        $pk = $app->getInput()->getInt('id');
        $this->setState($this->getName() . '.id', $pk);

        // Load the parameters.
        $params = ComponentHelper::getParams('com_easystore');
        $this->setState('params', $params);
    }

    /**
     * Method to get a user.
     *
     * @param   int  $pk  An optional id of the object to get, otherwise the id from the model state is used.
     *
     * @return  mixed     User data object on success, false on failure.
     *
     * @since   1.0.0
     */
    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);
        return $item;
    }

    /**
     * Method to get the row form.
     *
     * @param   array    $data      Data for the form.
     * @param   bool  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  bool|\Joomla\CMS\Form\Form  A Form object on success, false on failure
     *
     * @since   1.0.0
     */
    public function getForm($data = [], $loadData = true)
    {
        $input = Factory::getApplication()->getInput();
        $acl   = AccessControl::create();

        // Get the form.
        $form = $this->loadForm('com_easystore.user', 'user', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        $asset = $this->typeAlias . '.' . $input->get('id');

        if (!$acl->setAsset($asset)->canEditState()) {
            // Disable fields for display.
            $form->setFieldAttribute('ordering', 'disabled', 'true');
            $form->setFieldAttribute('published', 'disabled', 'true');

            // Disable fields while saving.
            // The controller has already verified this is a record you can edit.
            $form->setFieldAttribute('ordering', 'filter', 'unset');
            $form->setFieldAttribute('published', 'filter', 'unset');
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   1.0.0
     */
    protected function loadFormData()
    {
        /**
         * @var CMSApplication
         */
        $app = Factory::getApplication();

        // Check the session for previously entered form data.
        $data = $app->getUserState('com_easystore.edit.user.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_easystore.user', $data);

        return $data;
    }
    /**
     * Get the users list.
     *
     * @param   object|null     $params
     * @return  object
     */
    public function getUsers(?object $params = null)
    {
        $columns = ['eu.id', 'u.name', 'u.username', 'u.email', 'user_id', 'image', 'shipping_address', 'billing_address'];

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select($columns)
            ->from($db->quoteName('#__easystore_users', 'eu'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('eu.user_id') . ' = ' . $db->quoteName('u.id'));

        if (!empty($params->search)) {
            $search = preg_replace("@\s+@", ' ', $params->search);
            $search = explode(' ', $search);
            $search = array_filter($search, function ($word) {
                return !empty($word);
            });
            $search = implode('|', $search);
            $query->where(
                $db->quoteName('name') . $query->regexp($db->quote($search))
                . ' OR ' . $db->quoteName('username') . $query->regexp($db->quote($search))
                . ' OR ' . $db->quoteName('email') . $query->regexp($db->quote($search))
                . ' OR ' . $db->quoteName('phone') . $query->regexp($db->quote($search))
            );
        }

        if (!empty($params->sortBy)) {
            $ordering = EasyStoreHelper::sortBy($params->sortBy);
            $query->order($db->quoteName($ordering->field) . ' ' . $ordering->direction);
        } else {
            $query->order($db->quoteName('created') . ' DESC');
        }

        if (!empty($params->all)) {
            $db->setQuery($query);

            try {
                $users = $db->loadObjectList();

                if (!empty($users)) {
                    foreach ($users as &$user) {
                        $user->image = !empty($user->image) ? Uri::root(true) . '/' . $user->image : '';
                        $user->shipping_address = EasyStoreHelper::parseJson($user->shipping_address);
                        $user->billing_address  = EasyStoreHelper::parseJson($user->billing_address);
                    }

                    unset($user);
                }

                return $users;
            } catch (Throwable $error) {
                throw $error;
            }
        }

        $countQuery = $db->getQuery(true);
        $countQuery = $query->__toString();

        if (!empty($params->limit)) {
            $query->setLimit($params->limit, $params->offset);
        }

        $users = [];

        try {
            $db->setQuery($query);
            $users = $db->loadObjectList();

            if (!empty($users)) {
                foreach ($users as &$user) {
                    $user->image = !empty($user->image) ? Uri::root(true) . '/' . $user->image : '';
                    $user->shipping_address = EasyStoreHelper::parseJson($user->shipping_address);
                    $user->billing_address  = EasyStoreHelper::parseJson($user->billing_address);
                }

                unset($user);
            }
        } catch (Throwable $error) {
            throw $error;
        }

        $db->setQuery($countQuery);
        $db->execute();
        $allUsers = $db->getNumRows();

        $response = (object) [
            'totalItems' => $allUsers,
            'totalPages' => ceil($allUsers / $params->limit),
            'results'    => $users,
        ];

        return $response;
    }

    /**
     * Function to get User by Id
     *
     * @param int $id
     * @return object
     */
    public function getUserById(int $id)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select(
            [
                'eu.id',
                'u.email',
                'u.name',
                'u.username',
                'user_id',
                'user_type',
                'phone',
                'company_name',
                'company_id',
                'vat_information',
                'image',
                'shipping_address',
                'is_billing_and_shipping_address_same',
                'billing_address',
            ]
        );

        $query->from($db->quoteName('#__easystore_users', 'eu'));
        $query->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('eu.user_id') . ' = ' . $db->quoteName('u.id'));
        $query->where($db->quoteName('eu.id') . " = " . $id);

        try {
            $db->setQuery($query);
            $item                                       = $db->loadObject();
            $item->is_billing_and_shipping_address_same = (bool) $item->is_billing_and_shipping_address_same;
            $item->shipping_address                     = !empty($item->shipping_address) && is_string($item->shipping_address) ? json_decode($item->shipping_address) : $item->shipping_address;
            $item->billing_address                      = !empty($item->billing_address) && is_string($item->billing_address) ? json_decode($item->billing_address) : $item->billing_address;
            $item->image = !empty($item->image) ? Uri::root(true) . '/' . $item->image : '';

            return $item;
        } catch (Throwable $error) {
            throw $error;
        }
    }

    /**
     * Calculate the total expenditure of a user.
     *
     * @param   int $userId     The customer user ID.
     * @return  float   The total expenditure.
     */
    public function calculateUserTotalExpenditure(int $userId)
    {
        $orm = new EasyStoreDatabaseOrm();

        $customersQuery = $orm->setColumns(['id'])
            ->hasMany($userId, '#__easystore_orders', 'customer_id');

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('SUM(price) as total')
            ->from($db->quoteName('#__easystore_order_product_map'))
            ->where($db->quoteName('order_id') . ' IN (' . $customersQuery->__toString() . ')');

        try {
            $db->setQuery($query);

            return (float) $db->loadResult();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate the total number of orders placed by a customer.
     *
     * @param   int     $userId     The customer user ID.
     * @return  int     The total orders count.
     */
    public function calculateNumberOfOrders(int $userId)
    {
        $orm = new EasyStoreDatabaseOrm();

        return $orm->setColumns([
            $orm->aggregateQuoteName('COUNT', 'id'),
        ])
            ->useRawColumns(true)
            ->hasMany($userId, '#__easystore_orders', 'customer_id')
            ->loadResult() ?? 0;
    }

    /**
     * Retrieves a paginated list of Joomla users meeting specific criteria.
     *
     * This function retrieves a list of users from the `#__users` table that are
     * not blocked (block = 0) and are activated (activation = 1). It performs an
     * inner join with the `#__easystore_users` table on a non-matching condition
     * (`id != user_id`).
     *
     * Pagination parameters can be provided through an `object` argument (`$params`).
     * This object can have the following properties:
     *  - limit (integer): The number of users to retrieve per page.
     *  - offset (integer): The starting index of the user list (for pagination).
     *
     * The function returns a response object containing information about the
     * retrieved users and total count:
     *  - totalItems (integer): The total number of users matching the criteria.
     *  - totalPages (integer): The total number of pages based on the provided limit.
     *  - results (array of objects): An array containing the user data objects.
     *
     * @param object $params Pagination parameters.
     * @return object The response object containing user data and pagination information.
     * @throws \Exception If an error occurs during database interaction.
     *
     * @since 1.4.0
     */
    public function getJoomlaUsers()
    {
        try {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);
            $query->select(
                [
                    $db->quoteName('user.id'),
                    $db->quoteName('user.name'),
                    $db->quoteName('user.email'),
                ]
            );
            $query->from($db->quoteName('#__users', 'user'));

            $query->where(
                [
                    $db->quoteName('user.block') . ' = 0',
                ]
            );

            $query->order($db->quoteName('user.name') . ' DESC');

            $db->setQuery($query);

            $users = $db->loadObjectList();
   
            $customer = $this->getAllCustomer();

            foreach ($users as $index => $user) {
                if (in_array($user->id, $customer)) {
                    unset($users[$index]);
                }
            }

            $users = array_values($users);
          
            $response = (object) [
                'totalItems' => count($users),
                'results'    => $users,
            ];

            return $response;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getAllCustomer()
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select("*");
        $query->from($db->quoteName('#__easystore_users', 'user'));
        $db->setQuery($query);
        $users = $db->loadObjectList();

        $ids = array_map(function($user) {
            return $user->user_id;
        }, $users);

        return !empty($ids) ? $ids : [];
    }
}
