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

        // Generate a valid minimal PDF for testing
        $mockPdfContent = $this->generateMockPdf($testBarcode, $payload);

        return [
            'ResponseShipments' => [
                [
                    'Barcode' => $testBarcode,
                    'ProductCodeDelivery' => $payload['Shipments'][0]['ProductCodeDelivery'] ?? '3085',
                    'Labels' => [
                        [
                            'Content' => base64_encode($mockPdfContent),
                            'Labeltype' => $this->defaults['label_format'] ?? 'PDF'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Generate a valid minimal PDF for mock labels
     *
     * @param string $barcode Test barcode
     * @param array  $payload Original payload
     *
     * @return string PDF content
     *
     * @since 1.0.0
     */
    private function generateMockPdf(string $barcode, array $payload): string
    {
        $receiverName = $payload['Shipments'][0]['Addresses'][0]['FirstName'] ?? '';
        $receiverName .= ' ' . ($payload['Shipments'][0]['Addresses'][0]['Name'] ?? '');
        $address = $payload['Shipments'][0]['Addresses'][0]['Street'] ?? '';
        $address .= ' ' . ($payload['Shipments'][0]['Addresses'][0]['HouseNr'] ?? '');
        $zipCity = ($payload['Shipments'][0]['Addresses'][0]['Zipcode'] ?? '') . ' ' . ($payload['Shipments'][0]['Addresses'][0]['City'] ?? '');

        // PostNL labels are A6 format: 100x150mm = 283x425 points (1 point = 0.352778mm)
        $width = 283;
        $height = 425;

        // Generate barcode pattern (simple Code128-like pattern)
        $barcodePattern = $this->generateBarcodePattern($barcode);

        // Minimal valid PDF structure
        $pdf = "%PDF-1.4\n";
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources 4 0 R /MediaBox [0 0 {$width} {$height}] /Contents 5 0 R >>\nendobj\n";
        $pdf .= "4 0 obj\n<< /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >>\nendobj\n";

        // Content stream with mock label
        // Background border
        $content = "q\n";
        $content .= "0.8 0.8 0.8 RG\n"; // Gray border
        $content .= "2 w\n"; // Line width
        $content .= "10 10 " . ($width - 20) . " " . ($height - 20) . " re\n"; // Rectangle
        $content .= "S\n"; // Stroke
        
        // PostNL logo area (simplified)
        $content .= "q\n";
        $content .= "0 0.5 0.8 rg\n"; // PostNL blue
        $content .= "15 350 " . ($width - 30) . " 50 re\n"; // Header rectangle
        $content .= "f\n"; // Fill
        $content .= "Q\n";
        
        // Text content
        $content .= "BT\n";
        $content .= "1 1 1 rg\n"; // White text
        $content .= "/F1 16 Tf\n";
        $content .= "20 370 Td\n";
        $content .= "(PostNL TEST LABEL) Tj\n";
        $content .= "ET\n";
        
        // Barcode area
        $content .= "q\n";
        $content .= "0 0 0 rg\n"; // Black
        $content .= $barcodePattern; // Draw barcode pattern
        $content .= "Q\n";
        
        // Barcode text below barcode
        $content .= "BT\n";
        $content .= "0 0 0 rg\n"; // Black text
        $content .= "/F1 10 Tf\n";
        $content .= "20 280 Td\n";
        $content .= "(" . $this->escapePdfString($barcode) . ") Tj\n";
        $content .= "ET\n";
        
        // Address information
        $content .= "BT\n";
        $content .= "0 0 0 rg\n";
        $content .= "/F1 11 Tf\n";
        $content .= "20 250 Td\n";
        $content .= "(Naar:) Tj\n";
        $content .= "0 -18 Td\n";
        $content .= "(" . $this->escapePdfString(trim($receiverName)) . ") Tj\n";
        $content .= "0 -16 Td\n";
        $content .= "(" . $this->escapePdfString(trim($address)) . ") Tj\n";
        $content .= "0 -16 Td\n";
        $content .= "(" . $this->escapePdfString(trim($zipCity)) . ") Tj\n";
        $content .= "ET\n";
        
        // Test label notice
        $content .= "BT\n";
        $content .= "0.8 0 0 rg\n"; // Red text
        $content .= "/F1 8 Tf\n";
        $content .= "20 50 Td\n";
        $content .= "(TEST LABEL - Niet geldig voor verzending) Tj\n";
        $content .= "ET\n";
        
        $content .= "Q\n";

        $contentLength = strlen($content);
        $pdf .= "5 0 obj\n<< /Length {$contentLength} >>\nstream\n{$content}\nendstream\nendobj\n";

        // Cross-reference table
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n";
        $pdf .= "0000000000 65535 f \n";
        $pdf .= "0000000009 00000 n \n";
        $pdf .= "0000000058 00000 n \n";
        $pdf .= "0000000115 00000 n \n";
        $pdf .= "0000000214 00000 n \n";
        $pdf .= sprintf("%010d 00000 n \n", $xrefOffset - 93);

        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . strlen($pdf) . "\n%%EOF";

        return $pdf;
    }

    /**
     * Generate a simple barcode pattern for mock labels
     *
     * @param string $barcode Barcode string
     *
     * @return string PDF drawing commands
     *
     * @since 1.0.0
     */
    private function generateBarcodePattern(string $barcode): string
    {
        $x = 20;
        $y = 300;
        $barHeight = 40;
        $barWidth = 1.5;
        $pattern = '';
        
        // Generate bars based on barcode characters
        // Simple pattern: each digit creates alternating bars
        $chars = str_split($barcode);
        foreach ($chars as $char) {
            $code = ord($char);
            // Create pattern based on character code
            for ($i = 0; $i < 3; $i++) {
                $bar = ($code >> $i) & 1;
                if ($bar) {
                    $pattern .= sprintf("%.2f %.2f %.2f %.2f re f\n", $x, $y, $barWidth, $barHeight);
                }
                $x += $barWidth * 2;
            }
            $x += $barWidth; // Gap between characters
        }
        
        return $pattern;
    }

    /**
     * Escape special characters in PDF strings
     *
     * @param string $text Text to escape
     *
     * @return string Escaped text
     *
     * @since 1.0.0
     */
    private function escapePdfString(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
