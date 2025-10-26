<?php
/**
 * @package     EasyStore.Site
 * @subpackage  EasyStoreShipping.dhl
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Plugin\EasyStoreShipping\Dhl\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class DhlApiClient
{
    private $baseUrl;
    private $authCredentials;
    private $curl;

    public function __construct($apiKey = 'demo-key', $apiSecret = 'demo-secret', $baseUrl = 'https://api-mock.dhl.com/mydhlapi/rates')
    {
        $this->baseUrl         = $baseUrl;
        $this->authCredentials = base64_encode($apiKey . ':' . $apiSecret);
        $this->curl            = curl_init();
        $this->setDefaultCurlOptions();
    }

    public function __destruct()
    {
        if ($this->curl) {
            curl_close($this->curl);
        }
    }

    private function setDefaultCurlOptions(): void
    {
        curl_setopt_array($this->curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => $this->getDefaultHeaders(),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
    }

    private function getDefaultHeaders(): array
    {
        return [
            'Authorization: Basic ' . $this->authCredentials,
            'x-version: 3.0.0',
            'Accept: application/json',
            'Content-Type: application/json',
        ];
    }

    /**
     * Update authentication credentials
     *
     * @param string $apiKey
     * @param string $apiSecret
     * @return void
     */
    public function updateCredentials(string $apiKey, string $apiSecret): void
    {
        $this->authCredentials = base64_encode($apiKey . ':' . $apiSecret);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->getDefaultHeaders());
    }

    /**
     * Get shipping rates from DHL API
     *
     * @param array $params Query parameters for the API call
     * @return array Decoded JSON response
     * @throws Exception If API call fails
     */
    public function getShippingRates(array $params): array
    {
        try {
            $this->validateShippingParams($params);

            $queryString = http_build_query($params);
            curl_setopt($this->curl, CURLOPT_URL, $this->baseUrl . '?' . $queryString);

            $response = curl_exec($this->curl);

            if ($response === false) {
                throw new \Exception('cURL error: ' . curl_error($this->curl));
            }

            $httpCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
            if ($httpCode >= 400) {
                $errorMessage = $this->parseErrorResponse($response, $httpCode);
                throw new \Exception('HTTP error: ' . $httpCode . ' - ' . $errorMessage);
            }

            $decodedResponse = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON decode error: ' . json_last_error_msg());
            }

            return $decodedResponse;

        } catch (\Exception $e) {
            throw new \Exception('Failed to get shipping rates: ' . $e->getMessage());
        }
    }

    /**
     * Validate required parameters for shipping rates
     *
     * @param array $params
     * @throws Exception If required parameters are missing
     */
    private function validateShippingParams(array $params): void
    {
        $requiredParams = ['accountNumber', 'originCountryCode', 'destinationCountryCode'];

        foreach ($requiredParams as $param) {
            if (!isset($params[$param]) || empty($params[$param])) {
                throw new \Exception("Required parameter '{$param}' is missing or empty");
            }
        }
    }

    /**
     * Parse error response to get meaningful error message
     *
     * @param string $response
     * @param int $httpCode
     * @return string
     */
    private function parseErrorResponse(string $response, int $httpCode): string
    {
        $decodedError = json_decode($response, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($decodedError['message'])) {
            return $decodedError['message'];
        }

        return $response ?: 'Unknown error occurred';
    }
}
