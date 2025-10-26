<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Traits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Path;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseInterface;
use JoomShaper\Component\EasyStore\Administrator\Model\UserModel;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper as EasyStoreHelperSite;
use JoomShaper\Component\EasyStore\Site\Lib\Email;

trait User
{
    public function users()
    {
        $search = $this->getInput('search', '', 'STRING');
        $sortBy = $this->getInput('sortBy', '', 'STRING');
        $limit  = $this->getInput('limit', 10, 'INT');
        $offset = $this->getInput('offset', 0, 'INT');
        $all    = (bool) $this->getInput('all', 0);

        $model = new UserModel();

        $params = (object) [
            'search' => $search,
            'sortBy' => $sortBy,
            'limit'  => $limit,
            'offset' => $offset,
            'all'    => $all,
        ];

        $ordering = null;

        if (!empty($params->sortBy)) {
            $ordering = EasyStoreHelper::sortBy($params->sortBy);

            if (\in_array($ordering->field, ['orders', 'spent'])) {
                unset($params->sortBy);
            }
        }

        try {
            $users = $model->getUsers($params);
        } catch (\Exception $error) {
            $this->sendResponse(['message' => $error->getMessage()], 500);
        }

        if (!empty($users->results)) {
            foreach ($users->results as &$user) {
                $user->orders = $model->calculateNumberOfOrders($user->id);
                $user->spent  = $model->calculateUserTotalExpenditure($user->id);
            }

            unset($user);

            if (!empty($ordering) && \in_array($ordering->field, ['orders', 'spent'])) {
                usort($users->results, function ($first, $second) use ($ordering) {
                    $field = $ordering->field;
                    return $ordering->direction === 'ASC' ? (float) $first->$field - (float) $second->$field
                    : (float) $second->$field - (float) $first->$field;
                });
            }
        }

        $this->sendResponse($users);
    }

    public function createUser()
    {
        $acl           = AccessControl::create();
        $hasPermission = $acl->canCreate() || $acl->canEdit();

        if (!$hasPermission) {
            $this->sendResponse(['message' => Text::_("COM_EASYSTORE_PERMISSION_ERROR_MSG")], 403);
        }

        $name     = $this->getInput('name', '', 'STRING');
        $email    = $this->getInput('email', '', 'STRING');
        $phone    = $this->getInput('phone', '', 'STRING');
        $userType = $this->getInput('user_type', 'customer', 'STRING');
        $company_name = $this->getInput('company_name', '', 'STRING');
        $company_id = $this->getInput('company_id', '', 'STRING');
        $vat_information = $this->getInput('vat_information', '', 'STRING');
        $isBillingSameAsShipping = (bool) $this->getInput('is_billing_and_shipping_address_same', 0);

        $shippingAddress = $this->getInput('shipping_address', '', 'RAW');
        $billingAddress  = $this->getInput('billing_address', '', 'RAW');

        // Process User image
        $input = Factory::getApplication()->input;
        $file  = $input->files->get('image');

        $imageObject = (object) [
            'name' => null,
            'src'  => null,
        ];

        if (!empty($file)) {
            $isValid = EasyStoreHelper::isValid($file);

            if ($isValid->status) {
                $imageObject = EasyStoreHelper::uploadFile($file, $this->createUserUploadFolder());
            }
        }

        $response = (object) [
            'status'  => false,
            'message' => '',
            'id'      => null,
        ];

        $payload = (object) [
            'user_id'                              => null,
            'phone'                                => $phone,
            'company_name'                         => $company_name,
            'company_id'                           => $company_id,
            'vat_information'                      => $vat_information,
            'image'                                => $imageObject->src ?? '',
            'user_type'                            => $userType,
            'shipping_address'                     => $shippingAddress,
            'is_billing_and_shipping_address_same' => (int) $isBillingSameAsShipping,
            'billing_address'                      => $isBillingSameAsShipping ? $shippingAddress : $billingAddress,
            'created'                              => Factory::getDate()->toSql(),
            'created_by'                           => Factory::getApplication()->getIdentity()->id,
        ];

        $password = UserHelper::genRandomPassword(32);

        $userData = (object)[
            'id'        => null,
            'name'      => $name,
            'username'  => $email,
            'email'     => $email,
            'password'  => md5($password),
            'registerDate' => new Date(),
            'activation' => 1,
            'params' => '{}',
        ];

        try {
            $user = $this->updateOrCreateUser($userData);
        } catch (\Exception $error) {
            $this->sendResponse(['message' => $error->getMessage()], 500);
        }

        $payload->user_id = $user->id;

        try {
            $result            = EasyStoreDatabaseOrm::updateOrCreate('#__easystore_users', $payload, 'id');

            $this->sendCustomerCreationEmail($result, $user);
            
            $response->status  = true;
            $response->message = Text::_('COM_EASYSTORE_APP_USER_STORE_SUCCESSFULLY');
            $response->id      = $result->id;

            $this->sendResponse($response, 201);
        } catch (\Exception $e) {
            $response->status  = false;
            $response->message = $e->getMessage();

            $this->sendResponse($response, 500);
        }
    }

    public function updateUser()
    {
        $acl           = AccessControl::create();
        $hasPermission = $acl->canCreate() || $acl->canEdit();

        if (!$hasPermission) {
            $this->sendResponse(['message' => Text::_("COM_EASYSTORE_PERMISSION_ERROR_MSG")], 403);
        }

        $id = $this->getInput('id', null, 'INT');

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('user_id');
        $query->from($db->quoteName('#__easystore_users'));
        $query->where($db->quoteName('id') . " = " . $id);

        $db->setQuery($query);
        $userId = $db->loadResult();

        $name     = $this->getInput('name', '', 'STRING');
        $email    = $this->getInput('email', '', 'STRING');
        $phone    = $this->getInput('phone', '', 'STRING');
        $userType = $this->getInput('user_type', 'customer', 'STRING');
        $company_name = $this->getInput('company_name', '', 'STRING');
        $company_id = $this->getInput('company_id', '', 'STRING');
        $vat_information = $this->getInput('vat_information', '', 'STRING');

        $isBillingSameAsShipping = (bool) $this->getInput('is_billing_and_shipping_address_same', 0);


        $shippingAddress = $this->getInput('shipping_address', '', 'raw');
        $billingAddress  = $this->getInput('billing_address', '', 'raw');

        // Process User image
        $input = Factory::getApplication()->input;
        $file = $input->files->get('image');
        $image = '';
        if ($input->exists('image')) {
            if (is_null($file) || !is_array($file)) {
                $image = $input->get('image', '', 'STRING');
            }
        }

        // Initialize the image object with default values
        $imageObject = (object) [
            'name' => null,
            'src'  => null,
        ];

        // Fetch the existing image from the database
        $orm = new EasyStoreDatabaseOrm();
        $oldImage = $orm->setColumns(['image'])
            ->hasOne($id, '#__easystore_users', 'id')
            ->loadResult();

        $imageObject->src = $oldImage ?? null;

        if (empty($image) || $image === 'null') {
            if (!empty($file) && is_array($file)) {
                // Validate the uploaded file
                $isValid = EasyStoreHelper::isValid($file);
    
                if ($isValid->status) {
                    // Upload the file and update the image object
                    $uploadFolder = $this->createUserUploadFolder();
                    $imageObject = EasyStoreHelper::uploadFile($file, $uploadFolder);
    
                    // Remove the old image if it is different from the newly uploaded one
                    if (!empty($imageObject->src) && !empty($oldImage) && $oldImage !== $imageObject->src) {
                        $this->removeImageByPath($oldImage);
                    }
                } else {
                    // Handle invalid file scenario
                    throw new \Exception('The uploaded file is not valid.');
                }
            } elseif (empty($file)) {
                // Remove the old image if no file is uploaded and the file input is explicitly null
                if ($file === null && !empty($oldImage)) {
                    $this->removeImageByPath($oldImage);
                    $imageObject->src = null;
                }
            }
        }

        $response = (object) [
            'status'  => false,
            'message' => '',
            'id'      => null,
        ];

        $userData = (object) [
            'id'    => $userId,
            'name'  => $name,
            'email' => $email,
        ];

        try {
            $this->updateOrCreateUser($userData, true);
        } catch (\Exception $error) {
            $this->sendResponse(['message' => $error->getMessage()], 500);
        }

        $payload = (object) [
            'id'                                   => $id,
            'phone'                                => $phone,
            'company_name'                         => $company_name,
            'company_id'                           => $company_id,
            'vat_information'                      => $vat_information,
            'image'                                => $imageObject->src ?? '',
            'user_type'                            => $userType,
            'shipping_address'                     => $shippingAddress,
            'is_billing_and_shipping_address_same' => (int) $isBillingSameAsShipping,
            'billing_address'                      => $isBillingSameAsShipping ? $shippingAddress : $billingAddress,
        ];

        try {
            $result = EasyStoreDatabaseOrm::updateOrCreate('#__easystore_users', $payload, 'id');

            $response->status  = true;
            $response->message = Text::_('COM_EASYSTORE_APP_USER_UPDATE_SUCCESSFULLY');
            $response->id      = $result->id;

            $this->sendResponse($response, 201);
        } catch (\Exception $e) {
            $response->status  = false;
            $response->message = $e->getMessage();

            $this->sendResponse($response, 500);
        }
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

    public function userById()
    {
        $input = Factory::getApplication()->input;
        $id    = $input->get('id', null, 'INT');
        $model = new UserModel();

        if (is_null($id)) {
            throw new \Exception('The ID is missing!');
        }

        $result = $model->getUserById($id);

        if (isset($result->shipping_address)) {
            $result->shipping_address = $this->generateAddress($result->shipping_address);
        }

        if (isset($result->billing_address)) {
            $result->billing_address = $this->generateAddress($result->billing_address);
        }

        $this->sendResponse($result);
    }

    /**
     * Structure the address object.
     *
     * @param mixed $address
     *
     * @return object
     * @since 1.4.0
     */
    private function generateAddress($address)
    {
        if (empty($address)) {
            return [];
        }

        $address          = is_string($address) ? json_decode($address) : $address;
        $countryState     = EasyStoreHelperSite::getCountryStateFromJson($address->country, $address->state);
        
        $address->country_name = $countryState->country;

        $address->state_name   = $countryState->state;
        return $address;
    }

    /**
     * Create Upload Folder for User image upload.
     *
     * @param string $uniqueId  Unique Id
     * @param int $productId    Product Id
     *
     * @return string
     */
    protected function createUserUploadFolder()
    {
        $mediaParams = ComponentHelper::getParams('com_media');
        $directory   = '/user-image';
        $folder      = $mediaParams->get('file_path', 'images') . '/easystore' . $directory;
        $imagePath   = JPATH_ROOT . '/' . Path::clean($folder);

        if (!is_dir($imagePath)) {
            Folder::create($imagePath, 0755);
        }

        return $folder;
    }

    protected function removeImageByPath(string $src)
    {
        $src = JPATH_ROOT . '/' . $src;
        if (\file_exists($src)) {
            File::delete($src);
        }
    }

    /**
    * Retrieves a paginated list of Joomla users and sends them as a response.
    *
    * This function fetches pagination parameters (`limit` and `offset`) from the input,
    * creates a `params` object, and delegates the task of fetching users to the `UserModel` class.
    * The `UserModel` retrieves the users based on the provided pagination parameters.
    * Finally, the function sends the retrieved user data as a response.

    * @return mixed The response, typically a JSON or XML representation of the user data.
    *
    * @since 1.4.0
    */
    public function getJoomlaUsers()
    {
        $model = new UserModel();
        $result = $model->getJoomlaUsers();

        return  $this->sendResponse($result);
    }

    /**
     * Converts an input to a customer.
     *
     * This function takes an ID as input, creates or updates a user with these details,
     * and returns the updated user information.
     *
     * @return mixed
     * 
     * @since 1.4.0
     */
    public function convertToCustomer()
    {
        $acl           = AccessControl::create();
        $hasPermission = $acl->canCreate();

        if (!$hasPermission) {
            $this->sendResponse(['message' => Text::_("COM_EASYSTORE_PERMISSION_ERROR_MSG")], 403);
        }

        $ids  = $this->getInput('id', [], 'ARRAY');

        $response = (object) [
            'status'  => false,
            'message' => 'No user selected.',
            'id'      => null,
        ];

        if (empty($ids)) {
            $this->sendResponse($response, 404);
        }

        foreach ($ids as $id) {
            $payload = (object) [
                'user_id'                              => $id,
                'created'                              => Factory::getDate()->toSql(),
                'created_by'                           => Factory::getApplication()->getIdentity()->id,
            ];

            try {
                $result            = EasyStoreDatabaseOrm::updateOrCreate('#__easystore_users', $payload, 'id');
                $response->status  = true;
                $response->message = Text::_('COM_EASYSTORE_APP_USER_STORE_SUCCESSFULLY');
                $response->id      = $result->id;
                $response->code = 201;
            } catch (\Exception $e) {
                $response->status  = false;
                $response->message = $e->getMessage();
                $response->code = 500;
            }
        }

        $this->sendResponse($response, 201);
    }

    /**
     * Deletes users based on provided IDs.
     *
     * This function retrieves user IDs from the input, checks if any IDs are provided,
     * and attempts to delete each user from the database. If no IDs are provided,
     * it sends a 404 response. On successful deletion, it sends a 200 response.
     * If an error occurs during deletion, it sends a 500 response with the error message.
     *
     * @return void
     * 
     * @since 1.4.2
     */
    public function deleteUsers()
    {
        $acl           = AccessControl::create();
        $hasPermission = $acl->canDelete();

        if (!$hasPermission) {
            $this->sendResponse(['message' => Text::_("COM_EASYSTORE_PERMISSION_ERROR_MSG")], 403);
        }

        $ids = $this->getInput('ids', '', 'ARRAY');

        if (empty($ids)) {
            $this->sendResponse(['message' => 'No user selected.'], 404);
        }

        try {

            foreach ($ids as $id) {
                $result = EasyStoreDatabaseOrm::get('#__easystore_users', 'id', $id);
                $user   = $result->loadObject();
                if ($user && !empty($user->image)) {
                    $this->removeImageByPath($user->image);
                }
                EasyStoreDatabaseOrm::removeByIds('#__easystore_users', [$id], 'id');
            }

            
            $this->sendResponse(['message' => 'User deleted successfully.', 'status' => true], 200);
        } catch (\Exception $e) {
            $this->sendResponse(['message' => $e->getMessage()], 500);
        }

    }

    private function sendCustomerCreationEmail($result, $user)
    {
        if (!$result) {
           return false;
        }

        $settings          = SettingsHelper::getSettings();
        $storeName         = $settings->get('general.storeName', '');
        $storeEmail        = $settings->get('general.storeEmail', '');
        $storePhone        = $settings->get('general.storePhone', '');
        $storeAddress      = SettingsHelper::getAddress();

        $variables = [
            'account_login_link' => Route::_(Uri::root() . 'index.php?option=com_users&view=reset'),
            'customer_name'      => $user->name,
            'store_name'         => $storeName,
            'store_email'        => $storeEmail,
            'store_phone'        => $storePhone,
            'store_address'      => LayoutHelper::render('emails.address', $storeAddress),
        ];

        if (!$user->id) {
            $email = new Email($user->email, 'new_account');
            $email->bind($variables)->send();
        }

        return true;
    }
}
