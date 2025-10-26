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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Controller\BaseController;
use JoomShaper\Component\EasyStore\Site\Traits\Api;
use JoomShaper\Component\EasyStore\Site\Model\CartModel;
use JoomShaper\Component\EasyStore\Site\Traits\Checkout;
use JoomShaper\Component\EasyStore\Site\Model\CouponModel;
use JoomShaper\Component\EasyStore\Site\Model\ProductModel;
use JoomShaper\Component\EasyStore\Administrator\Supports\Shop;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Model\OrderModel;
use JoomShaper\Component\EasyStore\Administrator\Traits\Notifiable;
use JoomShaper\Component\EasyStore\Administrator\Checkout\OrderManager;
use JoomShaper\Component\EasyStore\Administrator\Helper\CustomInvoiceHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Site\Model\OrderModel as SiteOrderModel;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Default Controller of Order view in EasyStore component
 *
 * @since  1.0.0
 */
class OrderController extends BaseController
{
    use Api;
    use Checkout;
    use Notifiable;

    /**
     * Renders active payment methods for order payments.
     *
     * This method retrieves active payment methods, prepares them in a stdClass object,
     * and renders the payment methods using a specific layout template. It sends the rendered
     * content as a response.
     *
     * @return void
     *
     * @since 1.0.7
     */
    public function renderPayments()
    {
        $payment_methods       = new \stdClass();
        $payment_methods->list = $this->getActivePayments();

        $this->sendResponse(LayoutHelper::render('order.payment_methods', ['payment_methods' => $payment_methods]));
    }

    /**
     * Creates a new order in the EasyStore database and sends an order confirmation email.
     *
     * This method processes the provided order data, calculates shipping and tax details,
     * applies any available coupons, creates the order in the database, adds the order items,
     * logs the order activity, updates coupon usage, and sends an order confirmation email.
     *
     * @param object $data An object containing the order data to be created. Expected to include:
     *                     - email (string): The customer's email address.
     *                     - company_name (string): The company name associated with the order.
     *                     - vat_information (string): VAT information for the order.
     *                     - shipping_address (object): Shipping address details.
     *                     - billing_address (object): Billing address details.
     *                     - customer_note (string): Customer's note for the order.
     *                     - order_status (string): Status of the order.
     *                     - is_guest_checkout (bool): Indicates if the order is a guest checkout.
     *
     * @return object Returns the created order object.
     *
     * @throws \Exception Throws an exception if there is any error during the order creation process,
     *                    including if the user has exceeded the coupon usage limit.
     * @throws \Throwable Re-throws any other throwable errors encountered.
     *
     * @since 1.2.0
     */
    public function createOrder($data)
    {
        list($defaultCountry, $defaultState) = $this->getDefaultCountryState();

        $shippingAddress = $this->app->input->get('shipping_address', '', 'RAW');
        $shippingMethod  = $this->app->input->get('shipping_method', '', 'RAW');

        if (empty($shippingAddress)) {
            throw new Exception('Invalid shipping address', 400);
        }

        if (empty($shippingMethod)) {
            throw new Exception('Invalid shipping method', 400);
        }

        if (is_string($shippingAddress)) {
            $shippingAddress = json_decode($shippingAddress);
        }

        if (is_string($shippingMethod)) {
            $shippingMethod = json_decode($shippingMethod);
        }

        $country = $shippingAddress->country ?? $defaultCountry;
        $state   = $shippingAddress->state ?? $defaultState;

        $user               = $this->app->getIdentity();
        $currentDateTime    = Factory::getDate('now')->toSql(true);
        $customer           = EasyStoreHelper::getCustomerByUserId($user->id);
        $shippingId         = $shippingMethod ? $shippingMethod->uuid : null;

        /** @var CartModel $cartModel */
        $cartModel = $this->getModel('Cart', 'Site');
        $cart  = $cartModel->getItem($shippingId, $country, $state);

        $settings            = SettingsHelper::getSettings();
        $isCouponCodeEnabled = $settings->get('checkout.enable_coupon_code', true);
        $guestOrderToken     = $this->getUniqueGuestOrderToken();

        if (empty($cart->shipping_method)) {
            throw new Exception('Invalid shipping method', 400);
        }

        $order = (object) [
            'id'               => null,
            'creation_date'    => $currentDateTime,
            'customer_id'      => $customer->id ?? null,
            'customer_email'   => $data->email,
            'company_name'     => $data->company_name,
            'company_id'       => $data->company_id,
            'vat_information'  => $data->vat_information,
            'shipping_address' => $data->shipping_address,
            'billing_address'  => $data->billing_address,
            'customer_note'    => $data->customer_note,
            'payment_status'   => 'unpaid',
            'fulfilment'       => 'unfulfilled',
            'order_status'     => $data->order_status,
            'is_guest_order'   => $data->is_guest_checkout ? 1 : 0,
            'discount_type'    => 'percent',
            'discount_value'   => 0,
            'discount_reason'  => '',
            'shipping'         => !is_string($cart->shipping_method) ? json_encode($cart->shipping_method) : $cart->shipping_method,
            'payment_method'   => $cart->payment_method,
            'created'          => $currentDateTime,
            'modified'         => $currentDateTime,
            'created_by'       => $user->id,
            'order_token'      => $guestOrderToken,
            'sale_tax'         => 0,
            'shipping_tax'     => 0,
            'shipping_tax_rate' => 0,
        ];

        $order->sale_tax = $cart->taxable_amount ?? 0;
        $order->shipping_tax = $cart->shipping_tax ?? 0;
        $order->shipping_tax_rate = $cart->shipping_tax_rate ?? 0;
        $order->is_tax_included_in_price = Shop::isTaxEnabled() ? 0 : 1;
        $order->custom_invoice_id = CustomInvoiceHelper::generateCustomInvoiceId();

        /** @var CouponModel $couponModel */
        $couponModel = $this->getModel('Coupon', 'Site');

        if ($isCouponCodeEnabled && !empty($cart->coupon_code)) {
            $couponData  = $couponModel->getCouponByCode($cart->coupon_code);

            $order->coupon_id       = !empty($couponData) ? $couponData->id : null;
            $order->coupon_code     = $cart->coupon_code ?? null;
            $order->coupon_type     = $cart->coupon_type ?? null;
            $order->coupon_amount   = $cart->coupon_amount ?? 0.00;
            $order->coupon_category = $cart->coupon_category ?? null;
        }

        $orm = new EasyStoreDatabaseOrm();

        try {
            if ($isCouponCodeEnabled && !empty($order->coupon_code) && !$this->checkUserCouponUsageLimit($order->customer_email, $order->coupon_code)) {
                throw new \Exception(Text::_('COM_EASYSTORE_CART_USER_COUPON_LIMIT_EXCEEDED'));
            }

            $orm->create('#__easystore_orders', $order, 'id');
            $this->addOrderItems($cart->items, $order->id);

            /**  @var OrderModel $adminOrderModel */
            $adminOrderModel = $this->getModel('Order', 'Administrator');
            $adminOrderModel->addOrderActivity($order->id, 'order_created');

            if ($isCouponCodeEnabled && !empty($cart->coupon_code)) {
                $couponData              = new \stdClass();
                $couponData->user_id     = $order->customer_id;
                $couponData->email       = $order->customer_email;
                $couponData->coupon_code = $order->coupon_code;
                $couponModel->updateCouponUsageCount($couponData);
            }

            $this->onOrderPlacementCompletion();

            $orderSummery  = OrderManager::createWith($order->id, $customer, $order->order_token)->getOrderItemWithCalculation();
            // Send order confirmation email
            $this->sendOrderConfirmationEmail($orderSummery);

            return $order;
        } catch (\Throwable $error) {
            throw $error;
        }
    }

    /**
     * Adds order items to the EasyStore database and updates the product quantities.
     *
     * This method processes the provided items, creates order item records in the database,
     * and updates the corresponding product or SKU quantities.
     *
     * @param array $items  An array of item objects to be added to the order. Each item object is expected to include:
     *                      - product_id (int): The ID of the product.
     *                      - sku_id (int, optional): The SKU ID of the product variant.
     *                      - discount (object, optional): Discount details for the item, with properties:
     *                          - type (string): The type of discount (default: 'percent').
     *                          - value (float): The discount value (default: 0).
     *                      - quantity (int): The quantity of the item.
     *                      - item_price (float): The price of the item.
     * @param int $orderId  The ID of the order to which the items are being added.
     *
     * @return bool Returns true if the items are successfully added to the order.
     *
     * @throws \Throwable Throws an exception if there is any error during the process.
     *
     * @since 1.2.0
     */
    public function addOrderItems($items, $orderId)
    {
        if (empty($items)) {
            return;
        }

        $orm = new EasyStoreDatabaseOrm();

        foreach ($items as $item) {
            $orderRecord = (object) [
                'order_id'        => $orderId,
                'product_id'      => $item->product_id,
                'variant_id'      => $item->sku_id ?? null,
                'discount_type'   => $item->discount->type ?? 'percent',
                'discount_value'  => $item->discount->value ?? 0,
                'discount_reason' => '',
                'quantity'        => $item->quantity,
                'price'           => $item->item_price,
                'cart_item'       => json_encode($item)
            ];

            try {
                $orm->create('#__easystore_order_product_map', $orderRecord);

                // Update product quantity.
                /** @var ProductModel $productModel */
                $productModel = $this->getModel('Product', 'Site');
                $productModel->deductFromProductOrSkuTable($orderRecord);
            } catch (\Throwable $error) {
                throw $error;
            }
        }

        return true;
    }

    /**
     * Updates an order in the EasyStore database and sends notification emails based on the payment status.
     *
     * This method updates the order details in the database using the provided data, retrieves the updated order,
     * and prepares variables for email templates. It sends an appropriate email based on the payment status of the order,
     * either 'failed', 'canceled', or 'successful', if the email template is enabled in the settings.
     *
     * @param object $data An object containing the order data to be updated. Expected to include at least:
     *                     - id (int): The ID of the order.
     *                     - payment_status (string): The payment status of the order (e.g., 'failed', 'canceled', 'successful').
     *
     * @return bool Returns true if the order is successfully updated and the emails are processed.
     *
     * @throws \Throwable Throws an exception if there is any error during the update or email processing.
     *
     * @since 1.2.0
     */
    public function updateOrder($data)
    {
        try {
            $orm = new EasyStoreDatabaseOrm();
            $orm->update('#__easystore_orders', $data, 'id');
            $order = $orm->get('#__easystore_orders', 'id', $data->id)->loadObject();

            $customer = EasyStoreHelper::getCustomerById($order->customer_id);

            $orderSummery  = OrderManager::createWith($order->id, $customer, $order->order_token)->getOrderItemWithCalculation();

            // For `Failed` or `Canceled` Payment Status
            if (in_array($data->payment_status, ['failed', 'canceled'])) {
                $this->sendPaymentFailedEmail($orderSummery);
            }

            // For `Paid` Payment Status
            if ($data->payment_status === 'paid') {
                $this->sendPaymentSuccessEmail($orderSummery);
            }

            return true;
        } catch (\Throwable $error) {
            throw $error;
        }
    }

    /**
     * Handles the completion of an order placement.
     *
     * This method creates a CartController instance and removes cart data upon the completion
     * of an order placement.
     *
     * @return void
     *
     * @since 1.2.0
     */
    public function onOrderPlacementCompletion()
    {
        /**
         * @var CartController $cartController Instance of the CartController used to manage cart operations.
         */
        $cartController = $this->factory->createController('Cart', 'Site', [], $this->app, $this->input);

        // Remove cart data
        $cartController->removeCartData();
    }

    /**
     * Retrieves the default country and state for the current customer.
     *
     * This method fetches the default shipping address country and state from the customer's profile.
     * If the customer's shipping address is available and valid JSON, it extracts the country and state
     * from it; otherwise, it returns null values for both.
     *
     * @return array Returns an array containing default country and state:
     *               - Index 0: The default country code (ISO 3166-1 alpha-2) or null if not available.
     *               - Index 1: The default state code or null if not available.
     *
     * @since 1.2.0
     */
    protected function getDefaultCountryState()
    {
        $customer = EasyStoreHelper::getCustomerByUserId($this->app->getIdentity()->id);

        $country = null;
        $state   = null;

        if (!empty($customer->shipping_address)) {
            $shippingAddress = is_string($customer->shipping_address) ? json_decode($customer->shipping_address) : $customer->shipping_address;

            if ($shippingAddress) {
                $country = $shippingAddress->country ?? null;
                $state   = $shippingAddress->state ?? null;
            }
        }

        return [$country, $state];
    }

    /**
     * Checks if a user has exceeded the usage limit for a specific coupon code.
     *
     * This method retrieves the coupon details by its code using the CouponModel,
     * then checks if the user's coupon usage is within the allowed limit.
     *
     * @param string $email      The email address of the user associated with the coupon.
     * @param string $couponCode The coupon code to check.
     *
     * @return bool Returns true if the user's coupon usage is within the limit, false otherwise.
     *
     * @throws \Throwable Throws an exception if there is any error during the process.
     *
     * @since 1.2.0
     */
    private function checkUserCouponUsageLimit($email, $couponCode)
    {
        /** @var CouponModel $couponModel */
        $couponModel = $this->getModel('Coupon', 'Site');
        $couponData  = $couponModel->getCouponByCode($couponCode);

        if (empty($couponData)) {
            return false;
        }

        return $couponModel->isUserCouponUsageWithinLimit($email, $couponData);
    }

    /**
     * Generates a random token for guest orders.
     *
     * @param int $length The length of the token to be generated. Default is 64.
     *
     * @return string The generated token.
     * @throws RuntimeException If random_bytes fails to generate.
     *
     * @since 1.2.2
     */
    private function generateGuestOrderToken($length = 64)
    {
        try {
            return bin2hex(random_bytes($length / 2));
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to generate a random token: ' . $e->getMessage());
        }
    }


    /**
     * Generates a unique guest order token by checking against existing tokens in the database.
     *
     * @param int $length The length of the token to be generated. Default is 64.
     * @param int $maxAttempts The maximum number of attempts to generate a unique token. Default is 10.
     *
     * @return string The generated unique token.
     * @throws RuntimeException If it fails to generate a unique token after the maximum number of attempts.
     *
     * @since 1.2.2
     */
    public function getUniqueGuestOrderToken($length = 64, $maxAttempts = 10)
    {
        $attempt = 0;

        /** @var SiteOrderModel $orderModel */
        $orderModel = $this->getModel('Order', 'Site');

        while ($maxAttempts > $attempt) {
            try {
                $token    = $this->generateGuestOrderToken($length);
                $isUnique = $orderModel->isTokenUnique($token);

                if ($isUnique) {
                    return $token;
                }

                $attempt++;
            } catch (\RuntimeException $e) {
                // Log the error and continue attempting
                Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            }
        }

        throw new \RuntimeException("Failed to generate a unique guest order token after {$maxAttempts} attempts.");
    }
}
