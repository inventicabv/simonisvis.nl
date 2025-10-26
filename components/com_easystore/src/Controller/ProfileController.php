<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use JoomShaper\Component\EasyStore\Site\Model\ProfileModel;
use JoomShaper\Component\EasyStore\Administrator\Traits\User;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper as AdminEasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Profile Controller of EasyStore component
 *
 * @since  1.0.0
 */
class ProfileController extends BaseController
{
    use User;

    /**
     * Method to check out a user for editing and redirect to the edit form.
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    public function edit()
    {
        $app         = $this->app;
        $user        = $this->app->getIdentity();
        $loginUserId = (int) $user->get('id');

        // Get the current user id.
        $userId = $this->input->getInt('user_id');

        // Check if the user is trying to edit another users profile.
        if ($userId != $loginUserId) {
            $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $app->setHeader('status', 403, true);

            return false;
        }

        $cookieLogin = $user->get('cookieLogin');

        // Check if the user logged in with a cookie
        if (!empty($cookieLogin)) {
            // If so, the user must login to edit the password and other data.
            $app->enqueueMessage(Text::_('JGLOBAL_REMEMBER_MUST_LOGIN'), 'message');
            $this->setRedirect(Route::_('index.php?option=com_easystore&view=login', false));

            return false;
        }

        // Set the user id for the user to edit in the session.
        $app->setUserState('com_easystore.edit.profile.id', $userId);

        // Redirect to the edit screen.
        $this->setRedirect(Route::_('index.php?option=com_easystore&view=profile&layout=edit', false));

        return true;
    }

    /**
     * Method to save a user's profile data.
     *
     * @return  void|bool
     *
     * @since   1.0.0
     * @throws  \Exception
     */
    public function save()
    {
        // Check for request forgeries.
        $this->checkToken();

        $app = $this->app;

        /** @var \JoomShaper\Component\EasyStore\Site\Model\ProfileModel $model */
        $model  = $this->getModel('Profile', 'Site');
        $user   = $this->app->getIdentity();
        $userId = (int) $user->get('id');

        // Get the user data.
        $requestData = $app->getInput()->post->get('jform', [], 'array');

        // Force the ID to this user.
        $requestData['id'] = $userId;

        // Validate the posted data.
        $form = $model->getForm();

        if (!$form) {
            throw new \Exception($model->getError(), 500);
        }

        // Send an object which can be modified through the plugin event
        $objData = (object) $requestData;
        $app->triggerEvent(
            'onContentNormaliseRequestData',
            ['com_easystore.user', $objData, $form]
        );
        $requestData = (array) $objData;

        // Validate the posted data.
        $data = $model->validate($form, $requestData);

        // Check for errors.
        if ($data === false) {
            // Get the validation messages.
            $errors = $model->getErrors();

            // Push up to three validation messages out to the user.
            for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++) {
                if ($errors[$i] instanceof \Exception) {
                    $app->enqueueMessage($errors[$i]->getMessage(), 'warning');
                } else {
                    $app->enqueueMessage($errors[$i], 'warning');
                }
            }

            // Unset the passwords.
            unset($requestData['password1'], $requestData['password2']);

            // Save the data in the session.
            $app->setUserState('com_easystore.edit.profile.data', $requestData);

            // Redirect back to the edit screen.
            $userId = (int) $app->getUserState('com_easystore.edit.profile.id');
            $this->setRedirect(Route::_('index.php?option=com_easystore&view=profile&layout=edit&user_id=' . $userId, false));

            return false;
        }

        $usersData = [
            'id'        => $data['id'],
            'name'      => $data['name'],
            'password1' => $data['password1'],
            'password2' => $data['password2'],
            'email1'    => $data['email1'],
        ];

        // Attempt to save the data.
        $return = $model->save($usersData);

        // Check for errors.
        if ($return === false) {
            // Save the data in the session.
            $app->setUserState('com_easystore.edit.profile.data', $data);

            // Redirect back to the edit screen.
            $userId = (int) $app->getUserState('com_easystore.edit.profile.id');
            $this->setMessage(Text::sprintf('COM_EASYSTORE_PROFILE_SAVE_FAILED', $model->getError()), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_easystore&view=profile&layout=edit&user_id=' . $userId, false));

            return false;
        }

        $shippingAddress = json_encode([
            'name'      => $data['shipping_customer_name'],
            'country'   => $data['shipping_country'],
            'state'     => $data['shipping_state'] ?? '',
            'city'      => $data['shipping_city'],
            'zip_code'  => $data['shipping_zip_code'],
            'address_1' => $data['shipping_address_1'],
            'address_2' => $data['shipping_address_2'] ?? '',
        ]);

        if ($data['is_billing_same']) {
            $billingAddress = $shippingAddress;
        } else {
            $billingAddress = json_encode([
                'name'      => $data['billing_customer_name'],
                'country'   => $data['billing_country'],
                'state'     => $data['billing_state'] ?? '',
                'city'      => $data['billing_city'],
                'zip_code'  => $data['billing_zip_code'],
                'address_1' => $data['billing_address_1'],
                'address_2' => $data['billing_address_2'] ?? '',
            ]);
        }

        $imageObject = $this->processUserImage($userId);

        $customerData = (object) [
            'phone'                                => $data['phone'],
            'company_name'                         => $data['company_name'],
            'company_id'                           => $data['company_id'],
            'vat_information'                      => $data['vat_information'],
            'shipping_address'                     => $shippingAddress,
            'is_billing_and_shipping_address_same' => $data['is_billing_same'] ?? 0,
            'billing_address'                      => $billingAddress,
        ];

        if (!empty($imageObject->src)) {
            $customerData->image = $imageObject->src;
        }

        // Storing the detailed values to #__easystore_customer_details table
        $this->storeProfileDetails($userId, $customerData);

        // Check out the profile.
        $app->setUserState('com_easystore.edit.profile.id', $return);

        // Redirect back to the edit screen.
        $this->setMessage(Text::_('COM_EASYSTORE_PROFILE_SAVE_SUCCESS'));

        $redirect = $app->getUserState('com_easystore.edit.profile.redirect');

        // Don't redirect to an external URL.
        if (!Uri::isInternal($redirect)) {
            $redirect = null;
        }

        if (!$redirect) {
            $redirect = 'index.php?option=com_easystore&view=profile&layout=edit&hidemainmenu=1';
        }

        $this->setRedirect(Route::_($redirect, false));

        // Flush the data from the session.
        $app->setUserState('com_easystore.edit.profile.data', null);
    }

    /**
     * Process User image functionality
     * @param int $userId
     *
     * @return object
     */
    private function processUserImage($userId)
    {
        $input    = Factory::getApplication()->input;
        $fileInfo = $input->files->get('jform', [], 'raw');
        $file     = $fileInfo['image'] ?: [];

        $imageObject = (object) [
            'name' => null,
            'src'  => null,
        ];

        if (!empty($file)) {
            $isValid = AdminEasyStoreHelper::isValid($file);

            if ($isValid->status) {
                $fileName    = 'user-' . $userId;
                $imageObject = AdminEasyStoreHelper::uploadFile($file, $this->createUserUploadFolder(), $fileName);
            }
        }

        return $imageObject;
    }

    /**
     * Function to store Customer profile details
     *
     * @return bool
     */
    private function storeProfileDetails($userId, $data)
    {
        $model           = new ProfileModel();
        $profileDetailId = $model->detailExists($userId);

        if ($profileDetailId) {
            $data->id = $profileDetailId;
        }

        $data->user_id = $userId;
        $result        = EasyStoreDatabaseOrm::updateOrCreate('#__easystore_users', $data, 'id');

        if (!$result) {
            return false;
        }

        return true;
    }

    /**
     * Method to cancel an edit.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function cancel()
    {
        // Check for request forgeries.
        $this->checkToken();

        // Flush the data from the session.
        $this->app->setUserState('com_easystore.edit.profile', null);

        // Redirect to user profile.
        $this->setRedirect(Route::_('index.php?option=com_easystore&view=profile', false));
    }

    public function getStatesByCountry()
    {
        $input     = Factory::getApplication()->input;
        $countryId = $input->get('countryId', '', 'STRING');
        $states    = EasyStoreHelper::getOptionsFromJson('state', $countryId);

        $states = json_encode($states);

        echo $states;
        exit;
    }
}
