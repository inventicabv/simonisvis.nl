<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Traits;

use JoomShaper\Component\EasyStore\Administrator\Email\CustomerNameProvider;
use JoomShaper\Component\EasyStore\Administrator\Email\EmailManager;
use JoomShaper\Component\EasyStore\Administrator\Email\EmailService;
use JoomShaper\Component\EasyStore\Administrator\Email\OrderLinkGenerator;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;

/**
 * Notifiable trait
 * @since 1.3.4
 */
trait Notifiable
{
    /**
     * Creates an instance of the EmailManager for sending emails related to the order.
     *
     * @param object $order The order object to pass to the EmailManager.
     *
     * @return EmailManager An instance of the EmailManager configured with the order, email service, link generator, and customer name provider.
     *
     * @since 1.3.0
     */
    private function getEmailManager($order)
    {
        return new EmailManager($order, new EmailService(), new OrderLinkGenerator(), new CustomerNameProvider());
    }

    /**
     * Sends an order confirmation email to the customer.
     *
     * This method prepares the necessary email variables based on the order details,
     * including order summary, customer information, payment details, shipping address,
     * and store information. It checks if the 'order_confirmation' email template is enabled
     * in the settings before dispatching the email event.
     *
     * @param object $order The order object containing details of the order to be confirmed.
     *                     Expected properties include:
     *                     - id (int): The ID of the order.
     *                     - creation_date (string): The creation date of the order.
     *                     - payment_status (string): The payment status of the order.
     *                     - payment_method (string): The payment method used for the order.
     *                     - shipping_address (object|string): The shipping address object or JSON string.
     *                     - company_name (string|null): The company name associated with the order.
     *                     - vat_information (string|null): VAT information for the order.
     *                     - customer_note (string|null): Customer's note for the order.
     *                     - customer_email (string): The email address of the customer.
     *
     * @throws \Throwable Throws an exception if there is any error during the email dispatch process.
     *
     * @since 1.2.0
     * @since 1.3.0 Improve the business logic
     */
    public function sendOrderConfirmationEmail($order)
    {
        $emailManager = $this->getEmailManager($order);
        $emailManager->sendEmail('order_confirmation', 'order_confirmation', $order->customer_email, 'onOrderPlaced');
        $emailManager->sendEmail('order_confirmation_admin', 'order_confirmation_admin', SettingsHelper::getSettings()->get('general.storeEmail', ''), 'onOrderPlaced');
    }

    /**
     * Sends a payment success email to the customer.
     *
     * @param object $order The order object containing customer and order details.
     *
     * @return void
     *
     * @since 1.3.0
     */
    public function sendPaymentSuccessEmail($order)
    {
        $emailManager = $this->getEmailManager($order);
        $emailManager->sendEmail('payment_success', 'payment_success', $order->customer_email, 'onSuccessfulPayment');
        $emailManager->sendEmail('payment_success_admin', 'payment_success_admin', SettingsHelper::getSettings()->get('general.storeEmail', ''), 'onSuccessfulPayment');
    }

    /**
     * Sends a payment failure email to the customer.
     *
     * @param object $order The order object containing customer and order details.
     *
     * @return void
     *
     * @since 1.3.0
     */
    public function sendPaymentFailedEmail($order)
    {
        $emailManager = $this->getEmailManager($order);
        $emailManager->sendEmail('payment_error', 'payment_error', $order->customer_email, 'onFailedPayment');
    }

    /**
     * Sends an order refund email to the customer.
     *
     * @param object $order The order object containing customer and order details.
     * @param array $additionalData Additional data to be passed to the email template.
     *
     * @return void
     *
     * @since 1.4.4
     */
    public function sendOrderRefundEmail($order, $additionalData)
    {
        $emailManager = $this->getEmailManager($order);
        $emailManager->setAdditionalData($additionalData);
        $emailManager->sendEmail('order_refund', 'order_refund', $order->customer_email, 'onOrderRefund');
    }

    /**
     * Sends a tracking email to the customer.
     *
     * @param object $order The order object containing customer and order details.
     * @param array $additionalData Additional data to be passed to the email template.
     *
     * @return void
     *
     * @since 1.4.4
     */
    public function sendTrackingEmail($order, $additionalData)
    {
        $emailManager = $this->getEmailManager($order);
        $emailManager->setAdditionalData($additionalData);
        $emailManager->sendEmail('shipping_add_tracking', 'shipping_add_tracking', $order->customer_email, 'onOrderTracking');
    }
}
