<?php

/**
 * @package     EasyStore.Site
 * @subpackage  EasyStore.Mollie
 *
 * @copyright   Copyright (C) 2023 - 2024 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Plugin\EasyStore\Mollie\Extension;

require_once __DIR__ . '/../../vendor/autoload.php';

use Joomla\CMS\Log\Log;
use Joomla\Event\Event;
use Joomla\CMS\Language\Text;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Exceptions\ApiException;
use Joomla\CMS\Application\CMSApplication;
use JoomShaper\Plugin\EasyStore\Mollie\Utils\MollieConstants;
use JoomShaper\Component\EasyStore\Administrator\Plugin\PaymentGatewayPlugin;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class  MolliePayment extends PaymentGatewayPlugin
{
    /** @var CMSApplication */
    protected $app;

    /**
     * Handles payment events.
     *
     * @param Event $event -- The event object that contains information about the payment.
     * @since 1.0.0
     */

    public function onPayment(Event $event)
    {
        $mollie      = new MollieApiClient();
        $constants   = new MollieConstants();
        $secretKey   = $constants->getSecretKey();
        $arguments   = $event->getArguments();
        $paymentData = $arguments['subject'] ?: new \stdClass();
        $totalPrice  = number_format($paymentData->total_price, 2, '.', '');

        $mollie->setApiKey($secretKey);

        try {
            $payment = $mollie->payments->create([
                "amount" => [
                    "currency" => strtoupper($paymentData->currency),
                    "value"    => $totalPrice,
                ],
                "description" => $paymentData->store_name,
                "redirectUrl" => $constants->getSuccessUrl($paymentData->order_id),
                "cancelUrl"   => $constants->getCancelUrl($paymentData->order_id),
                "webhookUrl"  => $constants->getWebhookUrl(),
                "metadata" => [
                    "order_id" => $paymentData->order_id,
                ],
            ]);

            $this->app->redirect($payment->getCheckoutUrl(), 303);
        } catch (ApiException $e) {
            Log::add($e->getMessage(), Log::ERROR, 'mollie.easystore');
            $this->app->enqueueMessage(Text::_('COM_EASYSTORE_MOLLIE_ERROR') . ' ' . $e->getMessage(), 'error');
            $this->app->redirect($paymentData->back_to_checkout_page);
        }
    }

    /**
     * Handles payment notifications.
     *
     * @param Event $event -- The event object that contains information about the payment.
     * @since 1.0.0
     */

    public function onPaymentNotify(Event $event)
    {
        $constants         = new MollieConstants();
        $secretKey         = $constants->getSecretKey();
        $arguments         = $event->getArguments();
        $paymentNotifyData = $arguments['subject'] ?: new \stdClass();
        $mollie            = new MollieApiClient();

        $mollie->setApiKey($secretKey);

        try {
            $payment = $mollie->payments->get($paymentNotifyData->post_data['id']);
            $orderId = $payment->metadata->order_id;

            http_response_code(200);

            if ($payment->isPaid() || $payment->isCanceled() || $payment->isExpired() || $payment->isFailed()) {
                $payment->status = ($payment->status === 'expired') ? 'canceled' : $payment->status;

                $data = (object) [
                    'id'                   => $orderId,
                    'payment_status'       => $payment->status,
                    'payment_error_reason' => null,
                    'transaction_id'       => $payment->id,
                    'payment_method'       => $constants->getName(),
                ];

                $paymentNotifyData->order->updateOrder($data);
            }
        } catch (ApiException $e) {
            Log::add($e->getMessage(), Log::ERROR, 'mollie.easystore');
            http_response_code(404);
        }
    }

    /**
     * Checks if the Mollie secret key is set before processing a payment.
     *
     * @return void -- Returns true if the secret key is not empty, otherwise, returns false.
     * @since 1.0.0
     */

    public function onBeforePayment(Event $event)
    {
        $secretKey              = (new MollieConstants())->getSecretKey();
        $isRequiredFieldsFilled = !empty($secretKey);

        $event->setArgument('result', $isRequiredFieldsFilled);
    }
}
