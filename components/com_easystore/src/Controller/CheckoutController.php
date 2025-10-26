<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Controller;

use Exception;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use JoomShaper\Component\EasyStore\Administrator\Constants\CountryCodes;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Site\Helper\ArrayHelper;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Site\Model\CartModel;
use JoomShaper\Component\EasyStore\Site\Model\CheckoutModel;
use JoomShaper\Component\EasyStore\Site\Model\SettingsModel;
use JoomShaper\Component\EasyStore\Site\Traits\Api;
use JoomShaper\Component\EasyStore\Site\Traits\Checkout;
use JoomShaper\Component\EasyStore\Site\Traits\Token;
use JoomShaper\Component\EasyStore\Site\Validators\FieldValidator;
use Throwable;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Checkout Controller of EasyStore component
 *
 * @since  1.0.0
 */
class CheckoutController extends BaseController
{
    use Api;
    use Checkout;
    use Token;

    /**
     * Email address of the customer.
     *
     * @var string $customerEmail
     * @since 1.2.0
     */
    private $customerEmail;

    /**
     * Note provided by the customer.
     *
     * @var string $customerNote
     * @since 1.2.0
     */
    private $customerNote;

    /**
     * Name of the company associated with the order.
     *
     * @var string $companyName
     * @since 1.2.0
     */
    private $companyName;

    /**
     * ID of the company associated with the order.
     *
     * @var string $companyId
     * @since 1.3.0
     */
    private $companyId;

    /**
     * VAT information for the order.
     *
     * @var string $vatInformation
     * @since 1.2.0
     */
    private $vatInformation;

    /**
     * Flag indicating if the guest customer's information should be saved.
     *
     * @var bool $saveGuestInformation
     * @since 1.2.0
     */
    private $saveGuestInformation;

    /**
     * Error message from a plugin, if any.
     *
     * @var string $pluginError
     * @since 1.2.0
     */
    private $pluginError;

    /**
     * URL to navigate to after order placement.
     *
     * @var string $navigationUrl
     * @since 1.2.0
     */
    private $navigationUrl;

    /**
     * Place an Order for guest and shop customer.
     *
     * This method handles the entire process of placing an order, including initialization,
     * validation, processing cart information, populating address, order, customer, and guest records,
     * and saving the appropriate customer information based on whether the user is a guest or a registered customer.
     * It also creates the order, processes manual payment records, and sends order confirmation emails.
     *
     * @return void
     * @throws Exception If an error occurs during order creation or processing.
     * @since 1.0.0
     * @since 1.2.0 Update the method implementation
     */
    public function placeOrder()
    {
        // Initialize order variables
        $this->initializeOrderVariables();

        // Validate all data
        $this->validateAllInformation();

        // Process cart information
        $processedCartInformation = $this->updateCartInformation();

        // Populate address information
        $address = $this->populateAddressInformation();

        // Populate records
        $orderRecord    = $this->populateOrderRecord($address);
        $customerRecord = $this->populateCustomerRecord($address);
        $guestRecord    = $this->populateGuestRecord($address);

        /**  @var CheckoutModel $checkoutModel */
        $checkoutModel = $this->getModel('Checkout', 'Site');

        if (SettingsHelper::isGuestCheckoutEnable()) {
            if ($this->saveGuestInformation) {
                $checkoutModel->saveGuestCustomerInformation($guestRecord);
            }
        } else {
            $checkoutModel->saveCustomerInformation($customerRecord);
        }

        try {
            /** @var OrderController $orderController */
            $orderController = $this->factory->createController('Order', 'Site', [], $this->app, $this->input);

            // Create order
            $order = $orderController->createOrder($orderRecord);

            // Process manual payment record
            $this->processManualPaymentRecord($processedCartInformation->payment_method);

            // Populate payment record
            $this->populatePaymentRecord($processedCartInformation->payment_method, $address, $order);

            // Send success response
            $this->sendResponse([
                'success'       => true,
                'navigationUrl' => $this->navigationUrl,
                'pluginError'   => $this->pluginError,
            ]);
        } catch (Exception $error) {
            // Send error response
            $this->sendResponse([
                'message' => $error->getMessage(),
                'code'    => 500,
            ]);
        }
    }

    /**
     * Repays an existing order by initiating payment process.
     *
     * This method processes a request to repay an existing order. It retrieves necessary data
     * from the request input, including order details and customer information. Depending on the
     * payment method associated with the order, it prepares data required by the payment plugin
     * and validates the presence and status of the payment plugin.
     *
     * If the payment method is missing or disabled, it sets an appropriate error message.
     * It then checks and verifies the required fields of the payment plugin.
     *
     * If the payment method requires manual processing (e.g., bank transfer), it prepares a navigation
     * URL to guide the user through the payment completion process.
     *
     * If the payment method does not require manual processing, it prepares a navigation URL to
     * redirect the user to the payment gateway.
     *
     * After processing, it sends a response containing the success status, navigation URL, and any
     * plugin error encountered during the process.
     *
     * @return void
     *
     * @throws \Exception Throws any exception encountered during the order repayment process.
     *
     * @since 1.0.7
     */
    public function orderRepay()
    {
        $app           = Factory::getApplication();
        $user          = $app->getIdentity();
        $input         = $app->input;
        $data          = json_decode($input->get('data', '[]', 'RAW'));
        $customerEmail = $data->user->email;
        $pluginError   = null;
        $navigationUrl = null;

        $order = (object) [
            'id'               => $data->id,
            'creation_date'    => $data->creation_date,
            'customer_id'      => $data->customer_id,
            'customer_email'   => $data->customer_email,
            'shipping_address' => $data->shipping_address,
            'billing_address'  => $data->billing_address,
            'customer_note'    => $data->customer_note ?? "",
            'payment_status'   => $data->payment_status,
            'fulfilment'       => $data->fulfilment,
            'order_status'     => $data->order_status,
            'is_guest_order'   => $data->is_guest_order ? 1 : 0,
            'discount_type'    => $data->discount_type,
            'discount_value'   => $data->discount_value,
            'discount_reason'  => $data->discount_reason,
            'shipping'         => $data->shipping,
            'payment_method'   => $data->payment_method,
            'created'          => $data->created,
            'created_by'       => $data->created_by,
        ];

        // Data for payment plugins
        $paymentData = (object) [
            'customer_email'   => $customerEmail,
            'shipping_address' => $data->shipping_address,
            'order_id'         => $order->id,
            'payment_method'   => $data->payment_method,
            'order_type'       => 'reorder',
            'billing_address'  => $data->billing_address,
        ];

        if (empty($data->payment_method) || !PluginHelper::isEnabled('easystore', $data->payment_method)) {
            if (empty($data->payment_method)) {
                $pluginError = Text::sprintf('COM_EASYSTORE_NO_PAYMENT_METHOD_FOUND', ucfirst($data->payment_method), 'error');
            }

            if (!PluginHelper::isEnabled('easystore', $data->payment_method)) {
                $pluginError = Text::sprintf('COM_EASYSTORE_CART_PLUGIN_IS_DISABLE', ucfirst($data->payment_method), 'error');
            }

            $this->sendResponse(['success' => true, 'navigationUrl' => $navigationUrl, 'pluginError' => $pluginError]);
        }

        // Load payment plugin to check the plugin required fields
        PluginHelper::importPlugin('easystore', $data->payment_method);

        // Verify all the required plugin fields are filled in or not.
        $requiredFieldsStatus = $this->checkRequiredFields();

        if (!$requiredFieldsStatus) {
            $pluginError   = Text::_('COM_EASYSTORE_PAYMENT_PLUGIN_ERROR');
            $navigationUrl = null;
        }

        if (!in_array($data->payment_method, EasyStoreHelper::getManualPaymentLists()) && is_null($pluginError)) {
            $navigationUrl = Route::_(Uri::base() . 'index.php?option=com_easystore&task=payment.navigateToPaymentGateway&data=' . base64_encode(json_encode($paymentData)), false);
        }

        if (in_array($data->payment_method, EasyStoreHelper::getManualPaymentLists()) && is_null($pluginError)) {
            $navigationUrl = Route::_(Uri::base() . 'index.php?option=com_easystore&task=payment.onPaymentSuccess&order_id=' . $order->id . '&type=' . $data->payment_method, false);
            /**
             * @var OrderController $orderController
             */
            $orderController = $this->factory->createController('Order', 'Site', [], $this->app, $this->input);
            $orderController->updateOrder($order);
        }

        $this->sendResponse(['success' => true, 'navigationUrl' => $navigationUrl, 'pluginError' => $pluginError]);
    }

    /**
     * Retrieves the default country and state of the customer's shipping address.
     *
     * This method fetches the customer details based on the current user ID obtained from the application identity.
     * It retrieves the shipping address of the customer and parses it to obtain the default country and state.
     * If the customer's shipping address is not empty and valid, it extracts the country and state values.
     *
     * @return array An array containing the default country and state. Returns [null, null] if no valid
     *               shipping address is found.
     *
     * @since 1.0.0
     */
    protected function getDefaultCountryState()
    {
        $customer = EasyStoreHelper::getCustomerByUserId(Factory::getApplication()->getIdentity()->id);

        $defaultCountry = '';
        $defaultState   = '';
        $defaultZipCode = '';
        $defaultCity    = '';

        if (!empty($customer->shipping_address)) {
            $shippingAddress = is_string($customer->shipping_address) ? json_decode($customer->shipping_address) : '';

            if ($shippingAddress) {
                $defaultCountry = $shippingAddress->country ?? '';
                $defaultState   = $shippingAddress->state ?? '';
                $defaultCity    = $shippingAddress->city ?? '';
                $defaultZipCode = $shippingAddress->zip_code ?? '';
            }
        }

        return [$defaultCountry, $defaultState, $defaultCity, $defaultZipCode];
    }

    /**
     * Get all the cart items for showing cart summary in checkout view.
     *
     * @return void
     * @since 1.0.0
     */
    public function getCartData()
    {
        [$defaultCountry, $defaultState, $defaultCity, $defaultZipCode] = $this->getDefaultCountryState();
        /**
         * @var CartModel $cartModel
         */
        $cartModel  = $this->getModel('Cart', 'Site');
        $shippingId = Factory::getApplication()->input->get('shipping_id', null, 'STRING');
        $country    = Factory::getApplication()->input->get('country', $defaultCountry, 'STRING');
        $state      = Factory::getApplication()->input->get('state', $defaultState, 'STRING');
        $city       = Factory::getApplication()->input->get('city', $defaultCity, 'STRING');
        $zipCode    = Factory::getApplication()->input->get('zip_code', $defaultZipCode, 'STRING');
        $cart       = $cartModel->getItem($shippingId, $country, $state, $city, $zipCode);

        $this->sendResponse($cart);
    }

    /**
     * Get shipping and billing address with customer information.
     *
     * @return void
     * @since 1.0.0
     */
    public function getInformation()
    {
        /**
         * @var CheckoutModel $checkoutModel
         */
        $checkoutModel = $this->getModel('Checkout', 'Site');
        $information   = $checkoutModel->getInformation();

        $this->sendResponse($information);
    }

    /**
     * Get store default checkout settings values.
     *
     * @return void
     * @since 1.0.0
     */
    public function getCheckoutSettings()
    {
        $settings        = SettingsHelper::getSettings();
        $checkoutSetting = $settings->get('checkout', null) ?? (object) [];

        $this->sendResponse($checkoutSetting);
    }

    /**
     * Get store default shipping address
     *
     * @return void
     * @since 1.0.0
     */
    public function getShipping()
    {
        list($defaultCountry, $defaultState, $defaultCity, $defaultZipCode) = $this->getDefaultCountryState();

        $input    = $this->app->input;
        $country  = $input->get('country', $defaultCountry, 'STRING');
        $state    = $input->get('state', $defaultState, 'STRING');
        $city     = $input->get('city', $defaultCity, 'STRING');
        $zipCode  = $input->get('zip_code', $defaultZipCode, 'STRING');
        $subtotal = $input->get('subtotal', null);
        /**
         * @var SettingsModel $settingModel
         */
        $settingModel    = $this->getModel('Settings', 'Site');
        $shippingMethods = $settingModel->getShipping($country, $state, $subtotal) ?? [];

        /** @var CartModel $cartModel */
        $cartModel = $this->getModel('Cart', 'Site');

        $carriers = $settingModel->getShippingCarriers($country);

        $shippingCarrier = [];

        if (empty($carriers)) {
            $cartModel->setShippingMethods($shippingMethods);
            $this->sendResponse($shippingMethods);
        }

        if (!empty($country) && !empty($zipCode) && !empty($city) && !empty($state)) {
            if (!empty($carriers)) {
                foreach ($carriers as $carrier) {
                    if (PluginHelper::isEnabled('easystoreshipping', $carrier)) {
                        PluginHelper::importPlugin('easystoreshipping', $carrier);

                        try {
                            $shipping = $this->getShippingCarriers($country, $state, $city, $zipCode);
                        } catch (\Exception $exception) {
                            $this->sendResponse([
                                'message' => $exception->getMessage(),
                                'code'    => 400,
                            ]);
                        }

                        if (!empty($shipping)) {
                            $shippingCarrier = array_merge($shippingCarrier, $shipping);
                        }

                    }
                }
            }
        }

        $shippingMethods = array_map(
            function ($method) {return (object) (is_array($method) ? $method : (array) $method);},
            array_merge($shippingMethods, $shippingCarrier)
        );

        // Remove Colissimo if total weight exceeds 30kg
        if ($cartModel->calculateTotalWeight() > 30) {
            $shippingMethods = array_filter($shippingMethods, function ($method) {
                return $method->name !== 'Colissimo';
            });
        }

        if (!empty($shippingMethods)) {
            $cartModel->setShippingMethods(array_values($shippingMethods));
        }

        $this->sendResponse(array_values($shippingMethods));
    }

    public function getShippingCarriers($country, $state, $city, $zipCode)
    {
        $address               = new \stdClass();
        $address->state        = $state;
        $address->city         = $city;
        $address->postcode     = $zipCode;
        $address->country_code = EasyStoreHelper::getCountryIsoNames($country)->iso2;

        $event      = AbstractEvent::create('onEasyStoreGetShippingMethods', ['subject' => (object) ['shipping_address' => $address]]);
        $dispatcher = Factory::getApplication()->getDispatcher();
        $dispatcher->dispatch('onEasyStoreGetShippingMethods', $event);

        $shippingMethods = $event->getArgument('shippingMethods');

        return $shippingMethods;
    }

    public function getPaymentMethods()
    {
        $this->sendResponse($this->getActivePayments());
    }

    public function saveShipping($data)
    {
        $orm = new EasyStoreDatabaseOrm();

        try {
            $orm->update('#__easystore_cart', $data, 'token');

            return true;
        } catch (Throwable $error) {
            throw $error;
        }
    }

    /**
     * Search Guest User Record
     *
     * @return void
     */
    public function searchGuestUser()
    {
        $input = Factory::getApplication()->input;
        $email = $input->get('email', '', 'STRING');

        $model    = new CheckoutModel();
        $shipping = $model->getGuestShippingAddress($email);

        $this->sendResponse($shipping);
    }

    private function loadCountriesData()
    {
        $jsonPath  = JPATH_ROOT . '/media/com_easystore/data/countries.json';
        $countries = [];

        if (file_exists($jsonPath)) {
            $countries = file_get_contents($jsonPath);

            if (!empty($countries) && is_string($countries)) {
                $countries = json_decode($countries);
            }
        }

        return $countries;
    }

    public function getCountries()
    {
        $countriesData = $this->loadCountriesData();

        $settingsModel     = new SettingsModel();
        $countryWithStates = $settingsModel->getCountriesWithStates();

        $codes = array_keys($countryWithStates);

        $countries = ArrayHelper::findByArray(function ($haystack, $item) {
            return ArrayHelper::find(function ($value) use ($item) {
                return $value->numeric_code == $item;
            }, $haystack);
        }, $countriesData, $codes);

        $countries = ArrayHelper::toOptions(function ($country) {
            return (object) [
                'label' => $country->name,
                'value' => $country->numeric_code,
            ];
        }, $countries);

        $this->sendResponse($countries);
    }

    protected function isAllStatesAllowed($countryCode)
    {
        $settings = SettingsHelper::getSettings();
        $shipping = $settings->get('shipping', []);

        $isAllowed = false;

        foreach ($shipping as $item) {
            if (!empty($item->regions)) {
                foreach ($item->regions as $region) {
                    if ($region->country == $countryCode && empty($region->states)) {
                        $isAllowed = true;
                        break;
                    }
                }
            }

            if ($isAllowed) {
                break;
            }
        }

        return $isAllowed;
    }

    public function getStates()
    {
        $input       = Factory::getApplication()->input;
        $countryCode = $input->get('country_code', '');

        $settingsModel     = new SettingsModel();
        $countryWithStates = $settingsModel->getCountriesWithStates();

        if (empty($countryCode) || empty($countryWithStates)) {
            $this->sendResponse([]);
        }

        $countriesData = $this->loadCountriesData();
        $country       = ArrayHelper::find(function ($item) use ($countryCode) {
            return $item->numeric_code == $countryCode;
        }, $countriesData);

        if (is_null($country)) {
            $this->sendResponse([]);
        }

        $states = $country->states ?? [];

        if (empty($states)) {
            $this->sendResponse([]);
        }

        if ($this->isAllStatesAllowed($countryCode)) {
            $options = ArrayHelper::toOptions(function ($item) {
                return (object) [
                    'label' => $item->name,
                    'value' => $item->id,
                ];
            }, $states);

            $this->sendResponse($options);
        }

        $stateCodes = $countryWithStates[$countryCode] ?? [];

        if (empty($stateCodes)) {
            $this->sendResponse([]);
        }

        $states = ArrayHelper::findByArray(function ($haystack, $item) {
            return ArrayHelper::find(function ($value) use ($item) {
                return $value->id == $item;
            }, $haystack);
        }, $states, $stateCodes);

        $options = ArrayHelper::toOptions(function ($item) {
            return (object) [
                'label' => $item->name,
                'value' => $item->id,
            ];
        }, $states);

        $this->sendResponse($options);
    }

    /**
     * Check if all the required fields for the plugin are filled.
     *
     * @return bool  The result of the check, indicating whether the required fields are filled.
     * @since 1.0.3
     */

    public function checkRequiredFields()
    {
        $event = AbstractEvent::create(
            'onBeforePayment',
            [
                'subject' => new \stdClass(),
            ]
        );

        try {
            $eventResult = Factory::getApplication()->getDispatcher()->dispatch($event->getName(), $event);

            return $eventResult->getArgument('result');
        } catch (Throwable $error) {
            Factory::getApplication()->enqueueMessage($error->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Initializes order-related variables.
     *
     * This function sets various order-related variables using input from the application
     * and the current user identity. It handles guest checkout, customer notes, company
     * name, VAT information, and other relevant details.
     *
     * @return void
     *
     * @since 1.2.0
     */
    private function initializeOrderVariables()
    {
        $user                       = $this->app->getIdentity();
        $input                      = $this->app->input;
        $this->saveGuestInformation = (bool) $input->get('save_address', false);
        $this->customerEmail        = SettingsHelper::isGuestCheckoutEnable() ? $input->get('email', '', 'STRING') : $user->email;
        $this->customerNote         = $input->get('customer_note', '', 'text');
        $this->companyName          = $input->get('company_name', null, 'text');
        $this->companyId            = $input->get('company_id', null, 'text');
        $this->vatInformation       = $input->get('vat_information', null, 'text');
        $this->pluginError          = null;
        $this->navigationUrl        = null;

        // Set customer email for login customer
        if (!empty($this->customerEmail)) {
            $input->set('email', $this->customerEmail);
        }
    }

    /**
     * Validate all information before placing the order.
     * @return void
     *
     * @since 1.2.0
     */
    private function validateAllInformation()
    {
        $input                     = $this->app->input;
        $isPhoneFieldEnabled       = SettingsHelper::getSettings()->get('checkout.phone_number');
        $isAddressTwoFieldEnabled  = SettingsHelper::getSettings()->get('checkout.address_line_two');
        $isCompanyNameFieldEnabled = SettingsHelper::getSettings()->get('checkout.allow_company_name');
        $isCompanyIdFieldEnabled   = SettingsHelper::getSettings()->get('checkout.allow_company_id');
        $isVatFieldEnabled         = SettingsHelper::getSettings()->get('checkout.allow_vat_information');
        $validator                 = new FieldValidator();

        $this->addCommonFieldsValidation($validator, $input);
        $this->addAddressValidation($validator, json_decode($input->get('shipping_address', '', 'RAW')), $isPhoneFieldEnabled, $isAddressTwoFieldEnabled, 'shipping');

        if ($isCompanyNameFieldEnabled === "required") {
            $validator->addField($input->get('company_name', '', 'STRING'), ['company_name' => ['required']], Text::_('COM_EASYSTORE_CHECKOUT_ERROR_MESSAGE_COMPANY_NAME'));
        }

        if ($isCompanyIdFieldEnabled === "required") {
            $validator->addField($input->get('company_id', '', 'STRING'), ['company_id' => ['required']], Text::_('COM_EASYSTORE_CHECKOUT_ERROR_MESSAGE_COMPANY_ID'));
        }

        if ($isVatFieldEnabled === 'required') {
            $validator->addField($input->get('vat_information', '', 'STRING'), ['vat_information' => ['required']], Text::_('COM_EASYSTORE_CHECKOUT_ERROR_MESSAGE_VAT_INFORMATION'));
        }

        if (!$input->get('is_billing_and_shipping_address_same', 0, 'INT')) {
            $this->addAddressValidation($validator, json_decode($input->get('billing_address', '', 'RAW')), $isPhoneFieldEnabled, $isAddressTwoFieldEnabled, 'billing');
        }

        $errors = $validator->validate();

        if (!empty($errors)) {
            $this->sendResponse(['errors' => $errors, 'code' => 403]);
        }
    }

    /**
     * Adds common fields validation to the given validator.
     *
     * @param $validator    The validator instance to which the fields and rules are added.
     * @param $input        The input instance from which the field values are retrieved.
     *
     * @return void
     *
     * @since 1.2.0
     */
    private function addCommonFieldsValidation($validator, $input)
    {
        $validator->addField($input->get('email', '', 'STRING'), ['email' => ['required', 'email']], Text::_('COM_EASYSTORE_CHECKOUT_ERROR_MESSAGE_EMAIL'));
        $validator->addField($input->get('payment_method', '', 'RAW'), ['payment_method' => ['required']], Text::_('COM_EASYSTORE_CHECKOUT_ERROR_MESSAGE_PAYMENT_METHOD'));
        $validator->addField($input->get('shipping_method', '', 'RAW'), ['shipping_method' => ['required']], Text::_('COM_EASYSTORE_CHECKOUT_ERROR_MESSAGE_SHIPPING_METHOD'));
    }

    /**
     * Adds common fields validation to the given validator.
     *
     * @param  $validator                   The validator instance to which the fields and rules are added.
     * @param  $address                     Customer address object
     * @param  $isPhoneFieldEnabled         Check customer phone field is enabled.
     * @param  $isAddressTwoFieldEnabled    Check address two field is enabled.
     * @param  $type                        Address type
     *
     * @return void
     * @since 1.2.0
     */
    private function addAddressValidation($validator, $address, $isPhoneFieldEnabled, $isAddressTwoFieldEnabled, $type)
    {
        if (!empty($address)) {
            $validator->addField($address->name, ["{$type}_customer_name" => ['required']], Text::sprintf('COM_EASYSTORE_CHECKOUT_ERROR_MESSAGE_CUSTOMER_NAME', ucfirst($type)));
            $validator->addField($address->country, ["{$type}_country" => ['required']], Text::sprintf('COM_EASYSTORE_CHECKOUT_ERROR_MESSAGE_COUNTRY', ucfirst($type)));

            if (!empty($address->country) && !$this->checkStateLessCountry($address->country)) {
                $validator->addField($address->state, ["{$type}_state" => ['required']], Text::sprintf('COM_EASYSTORE_CHECKOUT_ERROR_MESSAGE_STATE', ucfirst($type)));
            }

            $validator->addField($address->city, ["{$type}_city" => ['required']], Text::sprintf('COM_EASYSTORE_CHECKOUT_ERROR_MESSAGE_CITY', ucfirst($type)));
            $validator->addField($address->zip_code, ["{$type}_zip_code" => ['required']], Text::sprintf('COM_EASYSTORE_CHECKOUT_ERROR_MESSAGE_ZIP_CODE', ucfirst($type)));
            $validator->addField($address->address_1, ["{$type}_address_line_1" => ['required']], Text::sprintf('COM_EASYSTORE_CHECKOUT_ERROR_MESSAGE_ADDRESS_LINE_1', ucfirst($type)));

            if ($isAddressTwoFieldEnabled === "required") {
                $validator->addField($address->address_2, ["{$type}_address_line_2" => ['required']], Text::sprintf('COM_EASYSTORE_CHECKOUT_ERROR_MESSAGE_ADDRESS_LINE_2', ucfirst($type)));
            }

            if ($isPhoneFieldEnabled === "required") {
                $validator->addField($address->phone, ["{$type}_phone" => ['required']], Text::sprintf('COM_EASYSTORE_CHECKOUT_ERROR_MESSAGE_PHONE_NUMBER', ucfirst($type)));
            }
        }
    }
    /**
     * Populate Cart Information
     * @return mixed
     *
     * @since 1.2.0
     */
    private function updateCartInformation()
    {
        try {
            /**
             * @var CartModel $cart
             */
            $cart = $this->getModel('Cart', 'Site');

            // Get cart items
            // $cart->getItem();

            // Check cart items
            $cart->checkCartStatus();

            $cartInformation                  = new \stdClass();
            $cartInformation->shipping_method = $this->app->input->get('shipping_method', '', 'RAW');
            $cartInformation->payment_method  = $this->app->input->get('payment_method', '', 'RAW');

            $cart->update($cartInformation);

            return $cartInformation;
        } catch (Exception $e) {
            $this->sendResponse(['message' => $e->getMessage(), 'code' => $e->getCode()]);
        }
    }

    /**
     * Populates the payment record with user and order information for the given payment method.
     *
     * This method creates a new payment record and fills it with user ID, guest checkout status,
     * customer email, shipping address, billing address, order ID, and payment method.
     * It also verifies if all required plugin fields are filled in and sets the navigation URL
     * based on the payment method.
     *
     * @param string $paymentMethod The payment method for the order.
     * @param \stdClass $address Object containing address information.
     * @param \stdClass $order Object containing order details.
     * @return void
     * @throws Throwable If an error occurs while importing the plugin or checking required fields.
     * @since 1.2.0
     */
    private function populatePaymentRecord($paymentMethod, $address, $order)
    {
        $paymentRecord                   = new \stdClass();
        $paymentRecord->customer_email   = $this->customerEmail;
        $paymentRecord->shipping_address = $address->shipping_address;
        $paymentRecord->billing_address  = $address->billing_address;
        $paymentRecord->order_id         = $order->id;
        $paymentRecord->payment_method   = $paymentMethod;

        try {
            // Load payment plugin to check the plugin required fields
            PluginHelper::importPlugin('easystore', $paymentMethod);

            // Verify all the required plugin fields are filled in or not.
            $requiredFieldsStatus = $this->checkRequiredFields();

            if (!$requiredFieldsStatus) {
                $this->pluginError   = Text::_('COM_EASYSTORE_PAYMENT_PLUGIN_ERROR');
                $this->navigationUrl = null;
            }

            if (!in_array($paymentMethod, EasyStoreHelper::getManualPaymentLists()) && is_null($this->pluginError)) {
                $this->navigationUrl = Route::_(Uri::base() . 'index.php?option=com_easystore&task=payment.navigateToPaymentGateway&data=' . base64_encode(json_encode($paymentRecord)), false);
            }
            if (in_array($paymentMethod, EasyStoreHelper::getManualPaymentLists()) && is_null($this->pluginError)) {
                $this->navigationUrl = Route::_(Uri::base() . 'index.php?option=com_easystore&task=payment.onPaymentSuccess&order_id=' . $order->id . '&type=' . $paymentMethod, false);
            }
        } catch (Throwable $th) {
            throw $th;
        }
    }

    /**
     * Processes the manual payment record for the given payment method.
     *
     * This method checks if the provided payment method is empty or disabled,
     * and returns an error message accordingly. If the payment method is valid,
     * it proceeds with processing the manual payment record.
     *
     * @param string $paymentMethod The payment method to be processed.
     * @return string|null Error message if payment method is invalid or disabled, null otherwise.
     * @since 1.2.0
     */
    private function processManualPaymentRecord($paymentMethod)
    {
        if (empty($paymentMethod)) {
            return Text::sprintf('COM_EASYSTORE_NO_PAYMENT_METHOD_FOUND', ucfirst($paymentMethod), 'error');
        }

        if (!PluginHelper::isEnabled('easystore', $paymentMethod)) {
            return Text::sprintf('COM_EASYSTORE_CART_PLUGIN_IS_DISABLE', ucfirst($paymentMethod), 'error');
        }

        return null; // No error occurred
    }

    /**
     * Populates the order record with provided address and additional order information.
     *
     * This method creates a new order record and fills it with the provided address details
     * along with customer note, company name, VAT information, guest checkout status, customer email,
     * and sets the order status to active.
     *
     * @param \stdClass $address Object containing address information.
     * @return \stdClass Populated order record object.
     * @since 1.2.0
     */
    private function populateOrderRecord($address)
    {
        $orderRecord = new \stdClass();

        $orderRecord->shipping_address  = $address->shipping_address;
        $orderRecord->billing_address   = $address->billing_address;
        $orderRecord->customer_note     = $this->customerNote;
        $orderRecord->company_name      = $this->companyName;
        $orderRecord->company_id        = $this->companyId;
        $orderRecord->vat_information   = $this->vatInformation;
        $orderRecord->is_guest_checkout = SettingsHelper::isGuestCheckoutEnable();
        $orderRecord->email             = $this->customerEmail;
        $orderRecord->order_status      = 'active';

        return $orderRecord;
    }

    /**
     * Populates the customer record with provided address information.
     *
     * This method creates a new customer record and fills it with the provided
     * address details, including user ID, phone number, shipping address, billing address,
     * and a flag indicating if the billing and shipping addresses are the same.
     *
     * @param \stdClass $address Object containing address information.
     * @return \stdClass Populated customer record object.
     * @since 1.2.0
     */
    private function populateCustomerRecord($address)
    {
        $customerRecord = new \stdClass();

        $shippingAddress = json_decode($address->shipping_address);

        $customerRecord->user_id                              = $this->app->getIdentity()->id ?? 0;
        $customerRecord->phone                                = $shippingAddress->phone;
        $customerRecord->shipping_address                     = $address->shipping_address;
        $customerRecord->billing_address                      = $address->billing_address;
        $customerRecord->is_billing_and_shipping_address_same = $address->is_billing_and_shipping_address_same;

        return $customerRecord;
    }

    /**
     * Populates the guest record with provided address information and guest email.
     *
     * This method creates a new guest record and fills it with the provided address details
     * and the guest's email address.
     *
     * @param \stdClass $address Object containing address information.
     * @return \stdClass Populated guest record object.
     * @since 1.2.0
     */
    private function populateGuestRecord($address)
    {
        $guestInformation                   = new \stdClass();
        $guestInformation->email            = $this->app->input->get('email', '', 'STRING');
        $guestInformation->shipping_address = $address->shipping_address;

        return $guestInformation;
    }

    /**
     * Populates the address information from user input.
     *
     * This method retrieves billing and shipping address information from the user input,
     * checks if the billing address is the same as the shipping address, and populates
     * the address information accordingly.
     *
     * @return \stdClass Populated address information object.
     * @since 1.2.0
     */
    private function populateAddressInformation()
    {
        $isBillingSameAsShippingAddress = $this->app->input->get('is_billing_and_shipping_address_same', 0, 'INT');

        $addressInformation                                       = new \stdClass();
        $addressInformation->is_billing_and_shipping_address_same = $isBillingSameAsShippingAddress;
        $addressInformation->shipping_address                     = $this->app->input->get('shipping_address', '', 'RAW');

        $pickupAddress = $this->app->input->get('shipping_pickup_address', '', 'RAW');

        if (!empty($pickupAddress)) {
            $pickupAddress                                   = json_decode($pickupAddress);
            $address                                         = $pickupAddress->name ?? '' . ', ' . $pickupAddress->address ?? '';
            $addressInformation->shipping_address            = json_decode($addressInformation->shipping_address);
            $addressInformation->shipping_address->address_1 = $address;
            $addressInformation->shipping_address->zip_code  = $pickupAddress->zip_code ?? '';
            $addressInformation->shipping_address->city      = $pickupAddress->city ?? '';
            $addressInformation->shipping_address            = json_encode($addressInformation->shipping_address);
        }

        $addressInformation->billing_address = $isBillingSameAsShippingAddress ? $addressInformation->shipping_address : $this->app->input->get('billing_address', '', 'RAW');

        return $addressInformation;
    }

    /**
     * List of state less country code.
     *
     * @param  mixed $countryCode
     * @return bool
     */
    public function checkStateLessCountry($countryCode)
    {
        $stateLessCountryList = CountryCodes::getStateLessCountries();
        return in_array($countryCode, $stateLessCountryList);
    }
}
