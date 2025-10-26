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


use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Date\Date;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseInterface;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Site\Lib\Email;
use JoomShaper\Component\EasyStore\Administrator\Constants\Status;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use stdClass;

class CustomerModel extends AdminModel
{
    /**
     * @var    string  The prefix to use with controller messages.
     * @since  1.0.0
     */
    protected $text_prefix = 'COM_EASYSTORE';

    /**
     * @var    string  The type alias for this content type.
     * @since  1.0.0
     */
    public $typeAlias = 'com_easystore.customer';

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
        if (empty($record->id) || (int) $record->published !== Status::TRASHED) {
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
     * Method to get a customer.
     *
     * @param   int  $pk  An optional id of the object to get, otherwise the id from the model state is used.
     *
     * @return  mixed     Customer data object on success, false on failure.
     *
     * @since   1.0.0
     */
    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        $joomlaUser  = EasyStoreHelper::getUserByCustomerId($item->id);

        $item->name  = !is_null($joomlaUser) ? $joomlaUser->name : null;
        $item->email = !is_null($joomlaUser) ? $joomlaUser->email : null;

        if ($item->shipping_address) {
            $shipping_address = json_decode($item->shipping_address);

            $item->shipping_customer_name  = isset($shipping_address->name) ? $shipping_address->name : '';
            $item->shipping_country        = isset($shipping_address->country) ? $shipping_address->country : '';
            $item->shipping_state          = isset($shipping_address->state) ? $shipping_address->state : '';
            $item->shipping_city           = isset($shipping_address->city) ? $shipping_address->city : '';
            $item->shipping_zip_code       = isset($shipping_address->zip_code) ? $shipping_address->zip_code : '';
            $item->shipping_address_1      = isset($shipping_address->address_1) ? $shipping_address->address_1 : '';
            $item->shipping_address_2      = isset($shipping_address->address_2) ? $shipping_address->address_2 : '';
        }

        if ($item->billing_address) {
            $billing_address = json_decode($item->billing_address);

            $item->billing_customer_name  = isset($billing_address->name) ? $billing_address->name : '';
            $item->billing_country        = isset($billing_address->country) ? $billing_address->country : '';
            $item->billing_state          = isset($billing_address->state) ? $billing_address->state : '';
            $item->billing_city           = isset($billing_address->city) ? $billing_address->city : '';
            $item->billing_zip_code       = isset($billing_address->zip_code) ? $billing_address->zip_code : '';
            $item->billing_address_1      = isset($billing_address->address_1) ? $billing_address->address_1 : '';
            $item->billing_address_2      = isset($billing_address->address_2) ? $billing_address->address_2 : '';
        }

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
        $form = $this->loadForm('com_easystore.customer', 'customer', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        $asset = 'com_easystore' . $input->get('id');

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
        /** @var CMSApplication $app */
        $app = Factory::getApplication();

        // Check the session for previously entered form data.
        $data = $app->getUserState('com_easystore.edit.customer.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_easystore.customer', $data);

        return $data;
    }

    /**
     * Save customer data
     *
     * @param array $data
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function save($data)
    {
        if (!$data['id']) {
            $userData = (object)[
                'id'        => null,
                'name'      => $data['name'],
                'username'  => $data['email'],
                'email'     => $data['email'],
                'password'  => UserHelper::genRandomPassword(32),
                'registerDate' => new Date(),
                'activation' => 1,
                'params' => '{}',
            ];

            $user            = $this->updateOrCreateUser($userData);
            $data['user_id'] = $user->id;
        }

        $customer = new stdClass();

        $customer->id = (int) $data['id'];
        $customer->user_id = $data['user_id'];
        $customer->image = $data['image'];
        $customer->user_type = 'customer';
        $customer->phone = $data['phone'];
        $customer->company_name = $data['company_name'];
        $customer->company_id = $data['company_id'];
        $customer->vat_information = $data['vat_information'];
        $customer->shipping_address = $this->convertAddressToJson($data, 'shipping');
        $customer->billing_address = (!$data['is_billing_and_shipping_address_same']) ? $this->convertAddressToJson($data, 'billing') : $this->convertAddressToJson($data, 'shipping');

        $result        = EasyStoreDatabaseOrm::updateOrCreate('#__easystore_users', $customer, 'id');

        if ($result) {
            $settings          = SettingsHelper::getSettings();
            $storeName         = $settings->get('general.storeName', '');
            $storeEmail        = $settings->get('general.storeEmail', '');
            $storePhone        = $settings->get('general.storePhone', '');
            $storeAddress      = SettingsHelper::getAddress();

            $variables = [
                'account_login_link' => Route::_(Uri::root() . 'index.php?option=com_users&view=reset'),
                'customer_name'      => $data['name'],
                'store_name'         => $storeName,
                'store_email'        => $storeEmail,
                'store_phone'        => $storePhone,
                'store_address'      => LayoutHelper::render('emails.address', $storeAddress),
            ];

            if (!$data['id']) {
                $email = new Email($data['email'], 'new_account');
                $email->bind($variables)->send();
            }

            return true;
        }

        return false;
    }

    /**
     * Convert address data to a JSON-encoded string.
     *
     * @param array  $data   An array containing address data.
     * @param string $prefix A prefix to identify the type of address (e.g., 'shipping' or 'billing').
     *
     * @return string A JSON-encoded string representing the address data.
     * @since  1.0.0
     */
    public function convertAddressToJson($data, $prefix)
    {
        $address = [];

        $address['name']       = isset($data[$prefix . '_customer_name']) ? $data[$prefix . '_customer_name'] : '';
        $address['country']    = isset($data[$prefix . '_country']) ? $data[$prefix . '_country'] : '';
        $address['state']      = isset($data[$prefix . '_state']) ? $data[$prefix . '_state'] : '';
        $address['city']       = isset($data[$prefix . '_city']) ? $data[$prefix . '_city'] : '';
        $address['zip_code']   = isset($data[$prefix . '_zip_code']) ? $data[$prefix . '_zip_code'] : '';
        $address['address_1']  = isset($data[$prefix . '_address_1']) ? $data[$prefix . '_address_1'] : '';
        $address['address_2']  = isset($data[$prefix . '_address_2']) ? $data[$prefix . '_address_2'] : '';

        return json_encode($address);
    }

    private function updateOrCreateUser($userData, $isUpdate = false)
    {
        try {
            $user = EasyStoreDatabaseOrm::updateOrCreate('#__users', $userData, 'id');

            if ($user->id && !$isUpdate) {
                $this->addToUserGroupMap($user->id);
            }
        } catch (\Throwable $error) {
            throw $error;
        }

        return $user;
    }

    private function addToUserGroupMap($userId)
    {
        $data = (object) [
            'user_id'  => $userId,
            'group_id' => 2,
        ];

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            $db->insertObject('#__user_usergroup_map', $data);
        } catch (\Throwable $error) {
            throw $error;
        }
    }
}
