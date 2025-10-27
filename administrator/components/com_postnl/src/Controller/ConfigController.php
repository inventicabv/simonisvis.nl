<?php

/**
 * @package     COM_POSTNL
 * @subpackage  Controller
 *
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace Simonisvis\Component\PostNL\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Http\HttpFactory;
use Simonisvis\Component\PostNL\Administrator\Helper\PostnlClient;

/**
 * Configuration controller class for PostNL component.
 *
 * @since  1.0.0
 */
class ConfigController extends BaseController
{
    /**
     * Test PostNL API connection
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function testapi(): void
    {
        // Check token
        $this->checkToken();

        try {
            $app = Factory::getApplication();

            // Get plugin parameters for API configuration
            $plugin = \Joomla\CMS\Plugin\PluginHelper::getPlugin('easystoreshipping', 'postnl');

            if (!$plugin) {
                throw new \RuntimeException(Text::_('COM_POSTNL_ERROR_PLUGIN_NOT_FOUND'));
            }

            $params = new \Joomla\Registry\Registry($plugin->params);

            // Get API configuration
            $testMode = (bool) $params->get('test_mode', 1);
            $baseUrl = $params->get('api_base_url', 'https://api.postnl.nl');
            $apiKey = $params->get('api_key', '');
            $authType = $params->get('auth_type', 'apikey');

            // Build API client
            $http = HttpFactory::getHttp();
            $defaults = [
                'auth_type' => $authType,
                'label_format' => $params->get('label_format', 'PDF'),
            ];

            $client = new PostnlClient($http, $baseUrl, $apiKey, $defaults);

            // Test connection
            $result = $client->testConnection();

            // Add mode info to message
            $modeInfo = $testMode ? 'TEST MODE' : 'PRODUCTION MODE';
            $result['message'] = sprintf('[%s] %s', $modeInfo, $result['message']);

            // Display result
            if ($result['success']) {
                $app->enqueueMessage($result['message'], 'success');

                // Also show configuration details
                $app->enqueueMessage(
                    sprintf(
                        'Configuration: Base URL: %s | Auth Type: %s | API Key: %s',
                        $baseUrl,
                        $authType,
                        empty($apiKey) ? 'Not configured' : 'Configured (***)'
                    ),
                    'info'
                );
            } else {
                $app->enqueueMessage($result['message'], 'error');
            }

        } catch (\Exception $e) {
            Log::add(
                'PostNL API test failed: ' . $e->getMessage(),
                Log::ERROR,
                'com_postnl'
            );

            $app = Factory::getApplication();
            $app->enqueueMessage(
                Text::sprintf('COM_POSTNL_ERROR_GENERAL', $e->getMessage()),
                'error'
            );
        }

        // Redirect back to orders view
        $this->setRedirect(
            Route::_('index.php?option=com_postnl&view=orders', false)
        );
    }

    /**
     * Test PostNL API with a real shipment request (sandbox)
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function testshipment(): void
    {
        // Check token
        $this->checkToken();

        try {
            $app = Factory::getApplication();

            // Get plugin parameters
            $plugin = \Joomla\CMS\Plugin\PluginHelper::getPlugin('easystoreshipping', 'postnl');

            if (!$plugin) {
                throw new \RuntimeException(Text::_('COM_POSTNL_ERROR_PLUGIN_NOT_FOUND'));
            }

            $params = new \Joomla\Registry\Registry($plugin->params);

            // Verify we're in test mode
            $testMode = (bool) $params->get('test_mode', 1);

            if (!$testMode) {
                throw new \RuntimeException(Text::_('COM_POSTNL_ERROR_TEST_ONLY_IN_TEST_MODE'));
            }

            // Build API client
            $http = HttpFactory::getHttp();
            $baseUrl = $params->get('api_base_url', 'https://api.postnl.nl');
            $apiKey = $params->get('api_key', '');
            $authType = $params->get('auth_type', 'apikey');
            $labelFormat = $params->get('label_format', 'PDF');

            $defaults = [
                'auth_type' => $authType,
                'label_format' => $labelFormat,
            ];

            $client = new PostnlClient($http, $baseUrl, $apiKey, $defaults);

            // Create test payload
            $testPayload = $this->createTestPayload($params);

            // Create test shipment
            $response = $client->generateMockResponse($testPayload);

            // Get label info
            $labelInfo = $client->getLabelFromResponse($response);

            $app->enqueueMessage(
                sprintf(
                    'Test shipment created successfully! Barcode: %s',
                    $labelInfo['barcode']
                ),
                'success'
            );

            $app->enqueueMessage(
                'This is a TEST shipment with mock data. The barcode cannot be tracked on PostNL website.',
                'info'
            );

        } catch (\Exception $e) {
            Log::add(
                'PostNL test shipment failed: ' . $e->getMessage(),
                Log::ERROR,
                'com_postnl'
            );

            $app = Factory::getApplication();
            $app->enqueueMessage(
                Text::sprintf('COM_POSTNL_ERROR_GENERAL', $e->getMessage()),
                'error'
            );
        }

        // Redirect back
        $this->setRedirect(
            Route::_('index.php?option=com_postnl&view=orders', false)
        );
    }

    /**
     * Create test payload for shipment testing
     *
     * @param   \Joomla\Registry\Registry  $params  Plugin parameters
     *
     * @return  array  Test payload
     *
     * @since   1.0.0
     */
    protected function createTestPayload(\Joomla\Registry\Registry $params): array
    {
        return [
            'Customer' => [
                'CustomerCode' => $params->get('customer_code', ''),
                'CustomerNumber' => $params->get('customer_number', ''),
            ],
            'Shipments' => [
                [
                    'Addresses' => [
                        [
                            'AddressType' => '01',
                            'FirstName' => 'Test',
                            'Name' => 'Customer',
                            'Street' => 'Teststraat',
                            'HouseNr' => '1',
                            'Zipcode' => '1234AB',
                            'City' => 'Amsterdam',
                            'Countrycode' => 'NL',
                        ],
                        [
                            'AddressType' => '02',
                            'CompanyName' => $params->get('shipper_company_name', ''),
                            'Street' => $params->get('shipper_street', ''),
                            'HouseNr' => $params->get('shipper_house_number', ''),
                            'Zipcode' => $params->get('shipper_zipcode', ''),
                            'City' => $params->get('shipper_city', ''),
                            'Countrycode' => $params->get('shipper_country_code', 'NL'),
                        ],
                    ],
                    'ProductCodeDelivery' => $params->get('product_code_delivery', '3085'),
                ],
            ],
        ];
    }
}
