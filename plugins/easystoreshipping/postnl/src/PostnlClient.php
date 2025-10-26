<?php

/**
 * @package     PlgEasystoreshippingPostnl
 * @subpackage  PostNL Client
 *
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace PlgEasystoreshippingPostnl;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Http\Http;
use Joomla\CMS\Log\Log;

/**
 * PostNL API Client
 *
 * Handles all communication with the PostNL Shipping API
 *
 * @since 1.0.0
 */
class PostnlClient
{
    /**
     * HTTP client
     *
     * @var Http
     */
    private Http $http;

    /**
     * Base URL for PostNL API
     *
     * @var string
     */
    private string $baseUrl;

    /**
     * API Key or Token
     *
     * @var string
     */
    private string $apiKey;

    /**
     * Default configuration
     *
     * @var array
     */
    private array $defaults;

    /**
     * Authentication type (apikey or bearer)
     *
     * @var string
     */
    private string $authType;

    /**
     * Constructor
     *
     * @param Http   $http      HTTP client instance
     * @param string $baseUrl   Base URL for PostNL API
     * @param string $apiKey    API key or bearer token
     * @param array  $defaults  Default configuration options
     *
     * @since 1.0.0
     */
    public function __construct(Http $http, string $baseUrl, string $apiKey, array $defaults = [])
    {
        $this->http      = $http;
        $this->baseUrl   = rtrim($baseUrl, '/');
        $this->apiKey    = $apiKey;
        $this->defaults  = $defaults;
        $this->authType  = $defaults['auth_type'] ?? 'apikey';

        // Initialize logging
        Log::addLogger(
            ['text_file' => 'plg_easystoreshipping_postnl.php'],
            Log::ALL,
            ['plg_easystoreshipping_postnl']
        );
    }

    /**
     * Create a shipment
     *
     * @param array $payload Shipment payload data
     *
     * @return array Response from API
     *
     * @throws \RuntimeException On API errors
     *
     * @since 1.0.0
     */
    public function createShipment(array $payload): array
    {
        $endpoint = '/v2/shipment';

        Log::add(
            'Creating PostNL shipment: ' . json_encode($payload),
            Log::INFO,
            'plg_easystoreshipping_postnl'
        );

        try {
            $response = $this->request('POST', $endpoint, $payload);

            Log::add(
                'PostNL shipment created successfully',
                Log::INFO,
                'plg_easystoreshipping_postnl'
            );

            return $response;
        } catch (\Exception $e) {
            Log::add(
                'Failed to create PostNL shipment: ' . $e->getMessage(),
                Log::ERROR,
                'plg_easystoreshipping_postnl'
            );
            throw $e;
        }
    }

    /**
     * Confirm a shipment
     *
     * @param string $shipmentIdentifier Shipment identifier or barcode
     *
     * @return array Response from API
     *
     * @throws \RuntimeException On API errors
     *
     * @since 1.0.0
     */
    public function confirmShipment(string $shipmentIdentifier): array
    {
        $endpoint = '/v2/shipment/confirm';

        $payload = [
            'Shipments' => [
                [
                    'Barcode' => $shipmentIdentifier
                ]
            ]
        ];

        Log::add(
            'Confirming PostNL shipment: ' . $shipmentIdentifier,
            Log::INFO,
            'plg_easystoreshipping_postnl'
        );

        try {
            $response = $this->request('POST', $endpoint, $payload);

            Log::add(
                'PostNL shipment confirmed successfully',
                Log::INFO,
                'plg_easystoreshipping_postnl'
            );

            return $response;
        } catch (\Exception $e) {
            Log::add(
                'Failed to confirm PostNL shipment: ' . $e->getMessage(),
                Log::ERROR,
                'plg_easystoreshipping_postnl'
            );
            throw $e;
        }
    }

    /**
     * Extract label from API response
     *
     * @param array $response API response containing label data
     *
     * @return array Array with keys: 'content' (binary/base64), 'mime', 'filename'
     *
     * @since 1.0.0
     */
    public function getLabelFromResponse(array $response): array
    {
        $labelFormat = $this->defaults['label_format'] ?? 'PDF';

        // PostNL returns labels in ResponseShipments[0].Labels
        if (isset($response['ResponseShipments'][0]['Labels'][0])) {
            $label = $response['ResponseShipments'][0]['Labels'][0];

            $content  = $label['Content'] ?? '';
            $barcode  = $response['ResponseShipments'][0]['Barcode'] ?? 'label';
            $filename = $barcode . '.' . strtolower($labelFormat);

            return [
                'content'  => base64_decode($content),
                'mime'     => $labelFormat === 'PDF' ? 'application/pdf' : 'application/zpl',
                'filename' => $filename,
                'barcode'  => $barcode
            ];
        }

        throw new \RuntimeException('No label found in API response');
    }

    /**
     * Make an HTTP request to PostNL API
     *
     * @param string $method   HTTP method (GET, POST, etc.)
     * @param string $endpoint API endpoint
     * @param array  $data     Request payload
     *
     * @return array Decoded JSON response
     *
     * @throws \RuntimeException On request failure
     *
     * @since 1.0.0
     */
    private function request(string $method, string $endpoint, array $data = []): array
    {
        $url     = $this->baseUrl . $endpoint;
        $headers = $this->buildHeaders();

        // Log request details (without sensitive headers)
        Log::add(
            sprintf('PostNL API Request: %s %s', $method, $url),
            Log::DEBUG,
            'plg_easystoreshipping_postnl'
        );

        try {
            if ($method === 'POST' || $method === 'PUT') {
                $response = $this->http->$method(
                    $url,
                    json_encode($data),
                    $headers
                );
            } else {
                $response = $this->http->$method($url, $headers);
            }

            $statusCode = $response->code;
            $body       = $response->body;

            Log::add(
                sprintf('PostNL API Response: HTTP %d', $statusCode),
                Log::DEBUG,
                'plg_easystoreshipping_postnl'
            );

            // Check for errors
            if ($statusCode >= 400) {
                $error = $this->parseErrorResponse($body, $statusCode);
                throw new \RuntimeException($error);
            }

            $result = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON response from PostNL API');
            }

            return $result;

        } catch (\Exception $e) {
            Log::add(
                'PostNL API request failed: ' . $e->getMessage(),
                Log::ERROR,
                'plg_easystoreshipping_postnl'
            );
            throw $e;
        }
    }

    /**
     * Build HTTP headers for API requests
     *
     * @return array Headers array
     *
     * @since 1.0.0
     */
    private function buildHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];

        // Add authentication header based on type
        if ($this->authType === 'bearer') {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        } else {
            $headers['apikey'] = $this->apiKey;
        }

        return $headers;
    }

    /**
     * Parse error response from API
     *
     * @param string $body       Response body
     * @param int    $statusCode HTTP status code
     *
     * @return string Error message
     *
     * @since 1.0.0
     */
    private function parseErrorResponse(string $body, int $statusCode): string
    {
        $decoded = json_decode($body, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            // PostNL specific error format
            if (isset($decoded['Errors'])) {
                $errors = [];
                foreach ($decoded['Errors'] as $error) {
                    $errors[] = sprintf(
                        '%s: %s',
                        $error['ErrorCode'] ?? 'Unknown',
                        $error['ErrorMsg'] ?? 'Unknown error'
                    );
                }
                return implode('; ', $errors);
            }

            if (isset($decoded['message'])) {
                return $decoded['message'];
            }

            if (isset($decoded['error_description'])) {
                return $decoded['error_description'];
            }
        }

        return sprintf('PostNL API error: HTTP %d - %s', $statusCode, $body);
    }

    /**
     * Generate mock response for test mode
     *
     * @param array $payload Original request payload
     *
     * @return array Mock response
     *
     * @since 1.0.0
     */
    public function generateMockResponse(array $payload): array
    {
        $testBarcode = '3STEST' . str_pad((string) rand(1, 999999999), 9, '0', STR_PAD_LEFT);

        Log::add(
            'Test mode: Generating mock shipment with barcode ' . $testBarcode,
            Log::INFO,
            'plg_easystoreshipping_postnl'
        );

        return [
            'ResponseShipments' => [
                [
                    'Barcode' => $testBarcode,
                    'ProductCodeDelivery' => $payload['Shipments'][0]['ProductCodeDelivery'] ?? '3085',
                    'Labels' => [
                        [
                            'Content' => base64_encode('MOCK LABEL DATA - TEST MODE'),
                            'Labeltype' => $this->defaults['label_format'] ?? 'PDF'
                        ]
                    ]
                ]
            ]
        ];
    }
}
