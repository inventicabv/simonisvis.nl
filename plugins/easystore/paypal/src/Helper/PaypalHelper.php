<?php

/**
 * @package     EasyStore.Site
 * @subpackage  EasyStore.Paypal
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Plugin\EasyStore\Paypal\Helper;


use Joomla\CMS\Log\Log;
use Joomla\CMS\Http\Http;
use Joomla\CMS\Language\Text;
use JoomShaper\Plugin\EasyStore\Paypal\Utils\PaypalApi;
use JoomShaper\Plugin\EasyStore\Paypal\Utils\PaypalConstants;

class PaypalHelper 
{

    /**
     * Prepares the data for initiating a PayPal payment request.
     *
     * @param  object       $paymentData    The payment data containing order details and user information.
     * @return array                        Structured data formatted for PayPal's payment request.
     * @throws \Exception                   if payment data is empty.
     * @since  2.0.0
     */
    public static function prepareData($paymentData): array {

        if (empty($paymentData)) {
            throw new \Exception(Text::_('COM_EASYSTORE_PAYPAL_ERROR'));          
        }

        $items          = !empty($paymentData->items) ? static::getItems($paymentData) : null;
        $merchantEmail  = (new PaypalConstants())->getMerchantEmail();
        $constant       = new PaypalConstants();

        $returnData = [
            'purchase_units' => [
                [
                    'custom_id' => $paymentData->order_id,
                    'items'     => $items,
                    'amount'    => static::createAmountData($paymentData),
                    'payee'     => ['email_address' => $merchantEmail],
                ],
            ],
            'intent'         => 'CAPTURE',
            'payment_source' => [
                'paypal' => [
                    'experience_context' => [
                        'user_action'               => 'PAY_NOW',
                        'payment_method_preference' => 'UNRESTRICTED',
                        'return_url'                => $constant->getSuccessUrl($paymentData->order_id),
                        'cancel_url'                => $constant->getCancelUrl($paymentData->order_id),
                    ]
                ]
            ]
        ];

        if (isset($paymentData->shipping_address) && !empty($paymentData->shipping_address)) {
            $returnData['purchase_units'][0]['shipping'] = static::getShippingInfo($paymentData->shipping_address, $paymentData->country);
        }

        return $returnData;
    }


    /**
     * Retrieves and formats items from the provided data.
     *
     * @param  object $data The data object containing item details.
     * @return array        An array of formatted items.
     * @since  2.0.0
     */
    private static function getItems(&$data) : array
    {
        $currency       = $data->currency;
        $data->subtotal = 0;

        return array_map(function($item) use ($currency, $data) {

            $data->subtotal += $item->price * (int) $item->quantity;
       
            return [
                'name'          => $item->title,
                'quantity'      => (string) $item->quantity,
                'unit_amount'   => [
                    'currency_code' => $currency,
                    'value'         => number_format($item->price, 2, '.',''),
                ],
            ];
        }, (array) $data->items);
    }


    /**
     * Creates the amount data array for a payment request.
     *
     * This method constructs and returns an array containing the currency code, total price, and item breakdown
     * for a payment request. It also includes optional fields for shipping, tax, and discount if they are present in the input data.
     *
     * @param  object   $data   The input data containing the currency, total price, subtotal, and optional fields.
     * @return array            The formatted amount data including currency code, total price, and breakdown.
     * @since  2.0.0
     */
    private static function createAmountData($data): array
    {
        $returnData = [
            'currency_code' => $data->currency,
            'value'         => number_format($data->total_price, 2,'.',''),
        ];

        $extraCharges = [
            'shipping_charge'           => 'shipping',
            'tax'                       => 'tax_total',
            'coupon_discount_amount'    => 'discount',
            'subtotal'                  => 'item_total',
        ];

        array_walk($extraCharges, function ($value, $key) use ($data, &$returnData) {

            if (isset($data->$key) && !empty($data->$key)) {
                $returnData['breakdown'][$value] = [
                    'currency_code' => $data->currency,
                    'value'         => number_format($data->$key, 2, '.', ''),
                ];
            }
        });

        return $returnData;
    }


    /**
     * Creates the shipping information array for a payment request.
     *
     * This method constructs and returns an array containing the shipping type, receiver's full name,
     * and address for a payment request.
     *
     * @param  object  $data The input data containing the receiver's name and address.
     * @return array         The formatted shipping information including type, name, and address.
     * @since  2.0.0
     */
    private static function getShippingInfo($shipping, $country): array
    {
        [$address1, $address2] = static::splitAddress($shipping, 300);

        return [
            'type'      => 'SHIPPING',
            'name'      => ['full_name' => $shipping->name],
            'address'   => [
                'address_line_1'    => $address1,
                'address_line_2'    => $address2,
                'admin_area_2'      => $shipping->city,
                'admin_area_1'      => $shipping->state,
                'postal_code'       => $shipping->zip_code,
                'country_code'      => $country->iso2,
            ],
        ];
    }

    /**
	 * Splits the address into two parts if it exceeds a certain length.
	 *
	 * @param  string 	$address1 	The primary address.
	 * @param  string 	$address2 	The secondary address (optional).
	 * @param  int 		$maxLength 	The maximum length for the first part of the street address.
	 * @return array 				The formatted part of the street address.
	 * @since  2.0.0
	 */
	private static function splitAddress($data, $maxLength): array
	{
		if (empty($data->address_1)) {
			return [];
		}
		
		$address_1 = mb_strimwidth($data->address_1, 0, $maxLength);
		$address_2 = (strlen($data->address_1) > $maxLength) ? mb_strimwidth($data->address_1, $maxLength, $maxLength) : $data->address_2;

		return [$address_1, $address_2];
	}

    /**
     * Retrieves the URL from an array of links that matches a specified condition.
     *
     * This method filters the provided links array to find the link that matches the given
     * condition. If a matching link is found, it returns the href property of that link.
     *
     * @param  array        $links     The array of links to search through.
     * @param  string       $condition The condition to match against the rel property of the links.
     * @return string|null             The URL if a matching link is found, otherwise null.
     * @since  2.0.0
     */
    public static function getUrl($links, $condition)
    {
        $url = array_filter($links, function ($link) use ($condition) {
            return $link->rel === $condition;
        });

        return $url ? reset($url)->href : null;
    }

    /**
     * Processes and formats PayPal error responses for logging or display.
     *
     * @param  object       $errorResponse  The error response object from PayPal.
     * @return string|null                  A formatted error message, or null if no error details are available.
     * @since  2.0.0
     */
    public static function handleErrorResponse($errorResponse) 
    {
        $message = '';

        if (!empty($errorResponse)) {

            if (!empty($errorResponse->issues)) {
                $message .= static::processIssues($errorResponse->issues);
            }

            if (!empty($errorResponse->details)) {
                $message .= static::processIssues($errorResponse->details);
            }

            if (!empty($errorResponse->error) && !empty($errorResponse->error_description)) {
                $message .= "{$errorResponse->error} : {$errorResponse->error_description}. ";
            }

            if (!empty($errorResponse->message)) {
                $message .= "{$errorResponse->name} : {$errorResponse->message}. ";
            }

            return $message;
        }

        return null;
    }

    /**
     * Processes a list of issues from an error response and formats them into a single message string.
     *
     * This method iterates over the provided issues, extracting relevant information such as
     * the issue type, description, and associated fields. It constructs a detailed message string
     * containing all the extracted information.
     *
     * @param  array  $issues The list of issues from the error response.
     * @return string         The formatted message string containing details of all issues.
     * @since  2.0.0
     */
    private static function processIssues($issues)
    {
        $finalMessage = array_reduce($issues, function ($message, $issue) {

            if (!empty($issue->issue)) {
                $message .= "Issue: {$issue->issue}. ";
            }

            if (!empty($issue->description)) {
                $message .= "Description: {$issue->description}. ";
            }

            if (!empty($issue->fields)) {
                foreach ($issue->fields as $field) {
                    if (!empty($field->field)) {
                        $message .= "Field: {$field->field}. ";
                    }
                }
            }

            return $message;
        }, '');

        return $finalMessage;
    }

    /**
     * Validates the signature of a PayPal webhook notification.
     *
     * @param  object       $payload    The webhook payload containing the necessary headers and raw data.
     * @return bool                     Returns true if the signature validation is successful; otherwise, throws an exception.
     * @throws \Exception               if the signature validation fails.
     * @since  2.0.0
     */
    public static function webhookSignatureValidation($payload)
    {
        $transmissionID        = $payload->server_variables['HTTP_PAYPAL_TRANSMISSION_ID'];
        $transmissionSignature = $payload->server_variables['HTTP_PAYPAL_TRANSMISSION_SIG'];
        $transmissionTime      = $payload->server_variables['HTTP_PAYPAL_TRANSMISSION_TIME'];
        $rawPayload            = $payload->raw_payload;
        $certUrl               = $payload->server_variables['HTTP_PAYPAL_CERT_URL'];
        $webhookID             = (new PaypalConstants())->getWebhookID();

        //<transmissionId>|<timeStamp>|<webhookId>|<crc32>
        $data = implode('|', [$transmissionID, $transmissionTime, $webhookID, crc32($rawPayload)]);

        $signature      = base64_decode($transmissionSignature);
        $certUrlContent = (new Http())->get($certUrl);

        if (is_null($certUrlContent) || $certUrlContent->code !== 200) {
            throw new \Exception(Text::_('COM_EASYSTORE_PAYPAL_ERROR_EMPTY_CERT_CONTENT'));
        }

        $publicKey = openssl_pkey_get_public($certUrlContent->body);

        $checkValidation = openssl_verify($data, $signature, $publicKey, 'sha256WithRSAEncryption');
        if (!$checkValidation) {
            throw new \Exception(Text::_('COM_EASYSTORE_PAYPAL_ERROR_INVALID_SIGNATURE'));
        }
        
        return $checkValidation;
    }

    /**
     * Processes an approved PayPal order by capturing the payment.
     *
     * @param  object       $payloadStream  The decoded payload from PayPal's webhook.
     * @return object                       The response from PayPal's `capturePayment` API.
     * @throws \Exception                   if the order is not approved.
     * @since  2.0.0
     */
    public static function processApprovedOrder($payloadStream)
    {
        if (static::isOrderApproved($payloadStream)) {
            http_response_code(200);
            return (new PaypalApi)->capturePayment($payloadStream);
        }

        exit();
    }

    /**
     * Checks if a PayPal order is approved and ready for capture.
     *
     * @param  object $payloadStream    The payload containing order details.
     * @return bool                     True if the order is approved, otherwise false.
     * @since  2.0.0
     */
    private static function isOrderApproved($payloadStream)
    {        
        return $payloadStream->event_type === 'CHECKOUT.ORDER.APPROVED' && 
                $payloadStream->resource->intent === 'CAPTURE' &&
                $payloadStream->resource->status === 'APPROVED';
    }

    /**
     * Maps PayPal payment statuses to internal status codes.
     *
     * @var     array
     * @since   2.0.0
     */
    public static array $statusMap = [
        'DECLINED'  => 'failed',
        'COMPLETED' => 'paid',
        'PENDING'   => 'pending',
        'APPROVED'  => 'pending'
    ];

    /**
     * Generates headers for PayPal API requests.
     * 
     * @return array  
     * @since  2.0.0                          
     */
    public static function getHeader()
    {
        return [
            'Content-Type'  => 'application/json',
            'Authorization' => (new PaypalApi())->getAccessToken(),
        ];
    }

    /**
     * List of HTTP error response codes.
     *
     * @var  array
     * @since 2.0.0
     */
    public static array $errorResponseCode = [
        400,
        404,
        405,
        406,
        409,
        415,
        422,
        429,
        500,
        503
    ];

    /**
     * Generates and logs an error message from a PayPal error response.
     *
     * @param   object  $data           The error response data from PayPal.
     * @return  string                  The generated error message.
     * @since   2.0.0
     */
    public static function getLogMessage($data) : string
    {
        $errorMessage = static::handleErrorResponse($data);
        Log::add($errorMessage, Log::ERROR, 'paypal.easystore');

        return $errorMessage;
    }

    /**
     * Validates the payee's email against the configured merchant email.
     *
     * @param  object $rawPayLoad The payload containing payment details from PayPal.
     * @return void
     * @since  2.0.1
     */
    public static function validateMerchantEmail($rawPayLoad): void
    {
        $payeeEmail    = $rawPayLoad->resource->purchase_units[0]->payee->email_address ?? null;
        $merchantEmail = (new PaypalConstants())->getMerchantEmail();

        if ($payeeEmail !== $merchantEmail) {
            exit();
        }
    }
}