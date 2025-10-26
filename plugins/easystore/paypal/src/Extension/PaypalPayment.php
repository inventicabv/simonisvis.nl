<?php

/**
 * @package     EasyStore.Site
 * @subpackage  EasyStore.paypal
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Plugin\EasyStore\Paypal\Extension;

use RuntimeException;
use Joomla\CMS\Log\Log;
use Joomla\Event\Event;
use Joomla\CMS\Language\Text;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Application\CMSApplication;
use JoomShaper\Plugin\EasyStore\Paypal\Utils\PaypalApi;
use JoomShaper\Plugin\EasyStore\Paypal\Helper\PaypalHelper;
use JoomShaper\Plugin\EasyStore\Paypal\Utils\PaypalConstants;
use JoomShaper\Component\EasyStore\Administrator\Plugin\PaymentGatewayPlugin;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


class PaypalPayment extends PaymentGatewayPlugin implements SubscriberInterface
{
    /** @var CMSApplication */
    protected $app;

    /**
     * Check if all the required fields for the plugin are filled.
     *
     * @return  void The result of the check, indicating whether the required fields are filled.
     * @since   1.0.3
     */
    public function onBeforePayment(Event $event)
    {
        $constant = new PaypalConstants();

        $isRequiredFieldsFilled = !empty($constant->getMerchantEmail()) && !empty($constant->getClientID()) && !empty($constant->getClientSecretKey()) && !empty($constant->getWebhookID());
        
        $event->setArgument('result', $isRequiredFieldsFilled);
    }

    /**
     * Handle the payment process for the event.
     * Redirects the user to PayPal for payment processing.
     *
     * @param Event $event The payment event containing necessary information.
     * @since 1.0.0
     */
    public function onPayment(Event $event)
    {
        $arguments      = $event->getArguments();
        $paymentData    = $arguments['subject'] ?: new \stdClass();

        try {
            $headers                = PaypalHelper::getHeader();
            $preparedCheckoutData   = PaypalHelper::prepareData($paymentData);
            $orderDetails           = PaypalApi::createOrder($headers,$preparedCheckoutData, $paymentData->order_id);

            $statusCode                 = json_decode((string) $orderDetails->getStatusCode());
            $createdOrderInformation    = json_decode((string) $orderDetails->getBody());

            if (in_array($statusCode, PaypalHelper::$errorResponseCode)) {               
                $message = PaypalHelper::getLogMessage($createdOrderInformation);

                $this->app->enqueueMessage($message);
                $this->app->redirect($paymentData->back_to_checkout_page);
            }

            $checkoutUrl = isset($createdOrderInformation->links) ? PaypalHelper::getUrl($createdOrderInformation->links, 'payer-action') : null;

            $this->app->redirect($checkoutUrl);

        } catch (RuntimeException $error) {
            
            Log::add($error->getMessage(), Log::ERROR, 'paypal.easystore');
            $this->app->enqueueMessage($error->getMessage(), 'error');
            $this->app->redirect($paymentData->back_to_checkout_page);
        }
    }

    /**
     * Handles PayPal payment notifications. Validates the webhook signature, processes the order status, and updates the order 
     * based on the notification data.
     *
     * @param  Event        $event  The event containing payment notification data.
     * @throws \Throwable           If any validation or processing fails.
     * @since  1.0.0
     */
    public function onPaymentNotify(Event $event)
    {
        // Event Arguments
        $arguments          = $event->getArguments();
        $constant           = new PaypalConstants();
        $paymentNotifyData  = $arguments['subject'] ?: new \stdClass();
        $order              = $paymentNotifyData->order;
        $errorReason        = null;
        $rawPayload         = json_decode($paymentNotifyData->raw_payload);
        
        try {
            PaypalHelper::validateMerchantEmail($rawPayload);
            PaypalHelper::webhookSignatureValidation($paymentNotifyData);
            $capturedOrderDetails = PaypalHelper::processApprovedOrder($rawPayload);
            
            $statusCode                 = json_decode((string) $capturedOrderDetails->getStatusCode());
            $capturedOrderInformation   = json_decode((string) $capturedOrderDetails->getBody());

            if (in_array($statusCode, PaypalHelper::$errorResponseCode)) {
                $logMessage     = PaypalHelper::getLogMessage($capturedOrderInformation);
                $errorReason    = strlen($logMessage) > 255 ? substr($logMessage, 0, 255) : $logMessage;
                $status         = 'failed';
                $orderID        = $rawPayload->resource->purchase_units[0]->custom_id;

            } else {
                $transactionInfo = $capturedOrderInformation->purchase_units[0]->payments->captures[0];
                $orderID         = $transactionInfo->custom_id;
                $transactionID   = $transactionInfo->id;
            }

            $data = (object) [
                'id'                   => $orderID,
                'payment_status'       => $status ?? PaypalHelper::$statusMap[$transactionInfo->status],
                'payment_error_reason' => $errorReason,
                'transaction_id'       => $transactionID ?? null,
                'payment_method'       => $constant->getName(),
            ];

            $order->updateOrder($data);

            exit();
        } catch (\Throwable $error) {
            Log::add($error->getMessage(), Log::ERROR, 'paypal.easystore');
            $this->app->enqueueMessage($error->getMessage(), 'error');
        }
    }

    /**
     * Creates a PayPal webhook if not already registered.
     * Checks existing webhooks to avoid duplicates and, if necessary, registers a new webhook.
     *
     * @throws RuntimeException If the webhook setup encounters errors.
     * @since  2.0.0
     */
    public function createWebhook()
    {
        try {
            $paypalApi    = new PaypalApi();
            $webhookLists = $paypalApi->getWebhookLists();
            $webhookUrl   = (new PaypalConstants())->getWebHookUrl();

            if (isset($webhookLists->webhooks) && is_array($webhookLists->webhooks)) {

                $registeredWebhookUrls  = array_column($webhookLists->webhooks, 'url');
                $isWebhookUrlRegistered = in_array($webhookUrl, $registeredWebhookUrls);

                return !$isWebhookUrlRegistered ? $paypalApi->createNewWebhook() : $paypalApi->sendResponse(Text::_('PLG_EASYSTORE_PAYPAL_WEBHOOK_ENDPOINT_EXISTS'));
            }

            $paypalApi->sendResponse(Text::_('PLG_EASYSTORE_PAYPAL_WEBHOOK_ERROR_INFORMATION_NOT_FOUND'));

        } catch (RuntimeException $error) {
            $errorMessage = PaypalHelper::handleErrorResponse($error) ?? $error->getMessage();
            $paypalApi->sendResponse($errorMessage);
        }
    }
    /**
     * function for getSubscribedEvents : new Joomla 4 feature
     *
     * @return array
     *
     * @since   2.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onAjaxPaypal'    => 'createWebhook',
            'onBeforePayment' => 'onBeforePayment',
            'onPayment'       => 'onPayment',
            'onPaymentNotify' => 'onPaymentNotify'
        ];
    }
}