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
use Joomla\CMS\Form\Form;
use Joomla\CMS\User\User;
use Joomla\CMS\Access\Access;
use Joomla\Registry\Registry;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Profile model class for Users.
 *
 * @since  1.0.0
 */
class ProfileModel extends FormModel
{
    /**
     * @var     object  The user profile data.
     * @since   1.0.0
     */
    protected $data;

    /**
     * Constructor.
     *
     * @param   array                 $config       An array of configuration options (name, state, dbo, table_path, ignore_request).
     * @param   MVCFactoryInterface   $factory      The factory.
     * @param   FormFactoryInterface  $formFactory  The form factory.
     *
     * @see     \Joomla\CMS\MVC\Model\BaseDatabaseModel
     * @since   1.0.0
     */
    public function __construct($config = [], MVCFactoryInterface $factory = null, FormFactoryInterface $formFactory = null)
    {
        $config = array_merge(
            [
                'events_map' => ['validate' => 'user'],
            ],
            $config
        );

        parent::__construct($config, $factory, $formFactory);
    }

    /**
     * Method to get the profile form data.
     *
     * The base form data is loaded and then an event is fired
     * for users plugins to extend the data.
     *
     * @return  User
     *
     * @since   1.0.0
     * @throws  \Exception
     */
    public function getData()
    {
        if ($this->data === null) {
            $userId = $this->getState('user.id');

            // Initialise the table with Joomla\CMS\User\User.
            $this->data = new User($userId);

            // Set the base user data.
            $this->data->email1 = $this->data->get('email');

            // Override the base user data with any data in the session.
            $temp = (array) Factory::getApplication()->getUserState('com_easystore.edit.profile.data', []);

            foreach ($temp as $k => $v) {
                $this->data->$k = $v;
            }

            // Unset the passwords.
            unset($this->data->password1, $this->data->password2);

            $registry            = new Registry($this->data->params);
            $this->data->params  = $registry->toArray();
            $this->data->address = '[]';

            // Create a new query object.
            $db    = $this->getDatabase();
            $query = $db->getQuery(true);

            $query->select(['*'])
                ->from($db->quoteName('#__easystore_users', 'eu'))
                ->where($db->quoteName('eu.user_id') . ' = ' . $this->data->id);

            $db->setQuery($query);

            try {
                $orm             = new EasyStoreDatabaseOrm();
                $customerDetails = $db->loadObject();

                $this->data->phone           = '';
                $this->data->company_name    = '';
                $this->data->company_id      = '';
                $this->data->vat_information = '';
                $this->data->image           = '';

                $this->data->shipping_customer_name = '';
                $this->data->shipping_country_code  = '';
                $this->data->shipping_country       = '';
                $this->data->shipping_state_code    = '';
                $this->data->shipping_state         = '';
                $this->data->shipping_city          = '';
                $this->data->shipping_zip_code      = '';
                $this->data->shipping_address_1     = '';
                $this->data->shipping_address_2     = '';

                $this->data->billing_customer_name = '';
                $this->data->billing_country_code  = '';
                $this->data->billing_country       = '';
                $this->data->billing_state_code    = '';
                $this->data->billing_state         = '';
                $this->data->billing_city          = '';
                $this->data->billing_zip_code      = '';
                $this->data->billing_address_1     = '';
                $this->data->billing_address_2     = '';

                $this->data->is_billing_same = false;

                if (!empty($customerDetails)) {
                    $joomlaUser                  = $orm->hasOne($customerDetails->user_id, '#__users', 'id')->loadObject();
                    $this->data->name            = $joomlaUser->name;
                    $this->data->phone           = $customerDetails->phone;
                    $this->data->company_name    = $customerDetails->company_name ?? '';
                    $this->data->company_id      = $customerDetails->company_id ?? '';
                    $this->data->vat_information = $customerDetails->vat_information ?? '';
                    $this->data->image           = $customerDetails->image;
                    $this->data->email           = $joomlaUser->email;

                    $shippingAddress                    = json_decode($customerDetails->shipping_address ?? '');
                    $shippingCountryState               = !empty($shippingAddress->country) ? EasyStoreHelper::getCountryStateFromJson($shippingAddress->country, $shippingAddress->state) : [];
                    $this->data->shipping_country_state = $shippingCountryState;
                    $this->data->shipping_customer_name = $shippingAddress->name ?? '';
                    $this->data->shipping_country       = $shippingAddress->country ?? '';
                    $this->data->shipping_state         = $shippingAddress->state ?? '';
                    $this->data->shipping_city          = $shippingAddress->city ?? '';
                    $this->data->shipping_zip_code      = $shippingAddress->zip_code ?? '';
                    $this->data->shipping_address_1     = $shippingAddress->address_1 ?? '';
                    $this->data->shipping_address_2     = $shippingAddress->address_2 ?? '';

                    $billingAddress                    = json_decode($customerDetails->billing_address ?? '');
                    $billingCountryState               = !empty($billingAddress->country) ? EasyStoreHelper::getCountryStateFromJson($billingAddress->country, $billingAddress->state) : [];
                    $this->data->billing_country_state = $billingCountryState;
                    $this->data->billing_customer_name = $billingAddress->name ?? '';
                    $this->data->billing_country       = $billingAddress->country ?? '';
                    $this->data->billing_state         = $billingAddress->state ?? '';
                    $this->data->billing_city          = $billingAddress->city ?? '';
                    $this->data->billing_zip_code      = $billingAddress->zip_code ?? '';
                    $this->data->billing_address_1     = $billingAddress->address_1 ?? '';
                    $this->data->billing_address_2     = $billingAddress->address_2 ?? '';

                    $this->data->is_billing_same = $customerDetails->is_billing_and_shipping_address_same;
                }
            } catch (\RuntimeException $e) {
                Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            }
        }

        return $this->data;
    }

    /**
     * Method to get the profile form.
     *
     * The base form is loaded from XML and then an event is fired
     * for users plugins to extend the form with extra fields.
     *
     * @param   array    $data      An optional array of data for the form to interrogate.
     * @param   bool  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  Form|bool  A Form object on success, false on failure
     *
     * @since   1.0.0
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_easystore.profile', 'profile', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        // Check for username compliance and parameter set
        $isUsernameCompliant = true;
        $username            = $loadData ? $form->getValue('username') : $this->loadFormData()->username;

        if ($username) {
            $isUsernameCompliant = !(preg_match('#[<>"\'%;()&\\\\]|\\.\\./#', $username)
                || strlen(mb_convert_encoding($username, 'ISO-8859-1', 'UTF-8')) < 2
                || trim($username) !== $username);
        }

        $this->setState('user.username.compliant', $isUsernameCompliant);

        if ($isUsernameCompliant && !ComponentHelper::getParams('com_easystore')->get('change_login_name')) {
            $form->setFieldAttribute('username', 'class', '');
            $form->setFieldAttribute('username', 'filter', '');
            $form->setFieldAttribute('username', 'description', 'com_easystore_PROFILE_NOCHANGE_USERNAME_DESC');
            $form->setFieldAttribute('username', 'validate', '');
            $form->setFieldAttribute('username', 'message', '');
            $form->setFieldAttribute('username', 'readonly', 'true');
            $form->setFieldAttribute('username', 'required', 'false');
        }

        // When multilanguage is set, a user's default site language should also be a Content Language
        if (Multilanguage::isEnabled()) {
            $form->setFieldAttribute('language', 'type', 'frontend_language', 'params');
        }

        // If the user needs to change their password, mark the password fields as required
        if ($this->getCurrentUser()->requireReset) {
            $form->setFieldAttribute('password1', 'required', 'true');
            $form->setFieldAttribute('password2', 'required', 'true');
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
        $data = $this->getData();

        $this->preprocessData('com_easystore.profile', $data, 'user');

        return $data;
    }

    /**
     * Override preprocessForm to load the user plugin group instead of content.
     *
     * @param   Form    $form   A Form object.
     * @param   mixed   $data   The data expected for the form.
     * @param   string  $group  The name of the plugin group to import (defaults to "content").
     *
     * @return  void
     *
     * @throws  \Exception if there is an error in the form event.
     *
     * @since   1.0.0
     */
    protected function preprocessForm(Form $form, $data, $group = 'user')
    {
        if (ComponentHelper::getParams('com_easystore')->get('frontend_userparams')) {
            $form->loadFile('frontend', false);

            if ($this->getCurrentUser()->authorise('core.login.admin')) {
                $form->loadFile('frontend_admin', false);
            }
        }

        parent::preprocessForm($form, $data, $group);
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @return  void
     *
     * @since   1.0.0
     * @throws  \Exception
     */
    protected function populateState()
    {
        // Get the application object.
        $params = Factory::getApplication()->getParams('com_easystore');

        // Get the user id.
        $userId = Factory::getApplication()->getUserState('com_easystore.edit.profile.id');
        $userId = !empty($userId) ? $userId : (int) $this->getCurrentUser()->get('id');

        // Set the user id.
        $this->setState('user.id', $userId);

        // Load the parameters.
        $this->setState('params', $params);
    }

    /**
     * Method to save the form data.
     *
     * @param   array  $data  The form data.
     *
     * @return  mixed  The user id on success, false on failure.
     *
     * @since   1.0.0
     * @throws  \Exception
     */
    public function save($data)
    {
        $userId = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('user.id');

        $user = new User($userId);

        // Prepare the data for the user object.
        $data['email']    = PunycodeHelper::emailToPunycode($data['email1']);
        $data['password'] = $data['password1'];

        // Unset the username if it should not be overwritten
        $isUsernameCompliant = $this->getState('user.username.compliant');

        if ($isUsernameCompliant && !ComponentHelper::getParams('com_easystore')->get('change_login_name')) {
            unset($data['username']);
        }

        // Unset block and sendEmail so they do not get overwritten
        unset($data['block'], $data['sendEmail']);

        // Bind the data.
        if (!$user->bind($data)) {
            $this->setError($user->getError());

            return false;
        }

        // Load the users plugin group.
        PluginHelper::importPlugin('user');

        // Retrieve the user groups so they don't get overwritten
        unset($user->groups);
        $user->groups = Access::getGroupsByUser($user->id, false);

        // Store the data.
        if (!$user->save()) {
            $this->setError($user->getError());

            return false;
        }

        // Destroy all active sessions for the user after changing the password
        if ($data['password']) {
            UserHelper::destroyUserSessions($user->id, true);
        }

        return $user->id;
    }

    /**
     * Function to check if user detail exists
     *
     * @param int $userId
     * @return int|bool
     */
    public function detailExists($userId)
    {
        // Create a new query object.
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select(['*'])
            ->from($db->quoteName('#__easystore_users', 'cd'))
            ->where($db->quoteName('cd.user_id') . ' = ' . $userId);

        $db->setQuery($query);

        try {
            $customerDetails = $db->loadObject();

            return $customerDetails->id;
        } catch (\RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }

        return false;
    }
}
