<?php

/**
 * @package     EasyStore.Site
 * @subpackage  EasyStore.Paypal
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Plugin\EasyStore\Paypal\Utils;


use Exception;
use Throwable;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Application\CMSApplication;
use JoomShaper\Plugin\EasyStore\Paypal\Helper\PaypalHelper;


class PaypalApi 
{
    /** @var CMSApplication */
    protected $app;

    /**
     * @var string $accessToken
     * This property holds the access token required for authenticating API requests.
     * @since 2.0.0
     */
    protected $accessToken;

    
    /**
     * Retrieves an access token from PayPal using client credentials.
     *
     * @return string The OAuth2 access token.
     * @since  2.0.0
     */
    public function getAccessToken(): string
    {
        if (empty($this->accessToken)) {
            
            $constants = new PaypalConstants();
            $url = "{$constants->getApiURL()}/v1/oauth2/token";
            $data = ['grant_type' => 'client_credentials'];
            $header = [
                "Content-Type" => "application/x-www-form-urlencoded",
                "Authorization" => 'Basic ' . base64_encode($constants->getClientID() . ':' . $constants->getClientSecretKey())
            ];

            $responseResult = HttpFactory::getHttp()->post($url, $data, $header, 30);
            $response = json_decode((string) $responseResult->getBody());

            if ($response->token_type && $response->access_token) {
                $this->accessToken = "{$response->token_type} {$response->access_token}";
            }
        }

        return $this->accessToken;
    }

    /**
     * Creates a new PayPal order by sending checkout data to the API.
     *
     * @param  array  $headers      The HTTP headers required for the request.
     * @param  array  $checkoutData The data for creating the order.
     * @param  string $orderID      A unique identifier for the order.
     * @since  2.0.0
     */
    public static function createOrder($headers, $checkoutData, $orderID)
    {
        $headers['PayPal-Request-Id'] = "order-id-{$orderID}";
        $url = (new PaypalConstants())->getApiURL() . '/v2/checkout/orders';

        return HttpFactory::getHttp()->post($url, json_encode($checkoutData), $headers, 30);
    }

    /**
     * Captures an authorized PayPal payment.
     *
     * @param object $payloadStream The webhook payload containing payment details.
     * @since 2.0.0
     */
    public function capturePayment($payloadStream)
    {
        $links             = $payloadStream->resource->links;
        $capturePaymentUrl = PaypalHelper::getUrl($links, 'capture');
        
        $headers           = [
            'Content-Type'      => 'application/json',
            'Authorization'     => static::getAccessToken(),
            'PayPal-Request-Id' => "paypal-order-id-" . $payloadStream->resource->id,
            'Prefer'            => 'return=representation',
        ];

        return HttpFactory::getHttp()->post($capturePaymentUrl, '', $headers, 30);
    }

    
    /**
     * Sends a JSON response back to the client.
     *
     * @param string $message           The message to include in the response.
     * @param string|null $webhookID    Optional webhook ID to include in the response.
     * @since 2.0.0
     */
    public function sendResponse($message, $webhookID = null)
    {
        $this->app = Factory::getApplication();
        $this->app->setHeader('Content-Type', 'application/json');
        $this->app->setHeader('status', 200, true);
        $this->app->sendHeaders();

        echo new JsonResponse(['webhook_id' => $webhookID,'message' => $message]);

        $this->app->close();
    }

    /**
     * Retrieves the list of PayPal webhooks for the current account.
     * @since 2.0.0
     */
    public function getWebhookLists() {

        $constant       = new PaypalConstants();
        $webhookApiUrl  = $constant->getApiURL() . '/v1/notifications/webhooks';
        $headers        = PaypalHelper::getHeader();

        $responseResult = HttpFactory::getHttp()->get($webhookApiUrl, $headers, 30);
        return json_decode((string) $responseResult->getBody());
    }

    /**
     * Creates a new webhook for PayPal event notifications.
     *
     * @throws Exception If the webhook creation fails or returns an error.
     * @since  2.0.0
     */
    public function createNewWebhook(){
        
        try {

            $constant      = new PaypalConstants();
            $webhookApiUrl = $constant->getApiURL() . '/v1/notifications/webhooks';
            $headers       = PaypalHelper::getHeader();

            $body = (object) [
                'url'         => $constant->getWebHookUrl(),
                'event_types' => [ 
                    [
                        'name' => 'PAYMENT.CAPTURE.COMPLETED',
                    ],
                    [
                        'name' => 'CHECKOUT.ORDER.APPROVED'
                    ]
                ],
            ];

            $responseResult = HttpFactory::getHttp()->post($webhookApiUrl, json_encode($body), $headers, 30);
            $statusCode     = json_decode((string) $responseResult->getStatusCode());
            $responseData   = json_decode((string) $responseResult->getBody());
            
            if (in_array($statusCode, PaypalHelper::$errorResponseCode)) {
                $errorMessage = PaypalHelper::handleErrorResponse($responseData);
                $this->sendResponse(Text::_($errorMessage));
            }
            
            if ($responseData->url === $constant->getWebHookUrl() && $responseData->id) {
                $this->sendResponse(Text::_('PLG_EASYSTORE_PAYPAL_WEBHOOK_ENDPOINT_CREATED'), $responseData->id);
            }

            $this->sendResponse(Text::_('PLG_EASYSTORE_PAYPAL_WEBHOOK_ERROR_INVALID_INFORMATION'));

        } catch (Throwable $error) {
           $this->sendResponse(Text::_($error->getMessage()));
        }
    }
}