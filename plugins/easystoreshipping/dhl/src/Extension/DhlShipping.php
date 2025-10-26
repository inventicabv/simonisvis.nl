<?php
/**
 * @package     EasyStore.Site
 * @subpackage  EasyStoreShipping.dhl
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Plugin\EasyStoreShipping\Dhl\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Date\Date;
use Joomla\Event\Event;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Plugin\CommonShippingPlugin;
use JoomShaper\Component\EasyStore\Site\Model\CartModel;
use JoomShaper\Plugin\EasyStoreShipping\Dhl\Helper\DhlApiClient;

class DhlShipping extends CommonShippingPlugin
{
    private const SANDBOX_API_URL    = 'https://express.api.dhl.com/mydhlapi/test/rates';
    private const PRODUCTION_API_URL = 'https://express.api.dhl.com/mydhlapi/rates';

    /**
     * Get the shipping methods for DHL
     *
     * @param Event $event The event object
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function onEasyStoreGetShippingMethods(Event $event)
    {
        $arguments       = $event->getArguments();
        $shippingAddress = $arguments['subject']->shipping_address;
        $shippingMethods = [];

        $environment = $this->params->get('environment', 'mock');
        $handlingFee = $this->params->get('handling_fee', 0);

        $isMock    = ($environment === 'mock');
        $isSandbox = ($environment === 'test');

        $apiUrl = $isSandbox ? self::SANDBOX_API_URL : self::PRODUCTION_API_URL;

        if ($isMock) {
            $httpClient = new DhlApiClient();
        } else {
            $httpClient = new DhlApiClient($apiUrl);
        }

        $response   = (object) $httpClient->getShippingRates($this->getRequestData($isMock, $isSandbox, $shippingAddress));
        
        if (!empty($response->products)) {
            foreach ($response->products as $rate) {
                $shippingMethods[] = $this->formatShippingMethod($rate, $rate['productName'], $handlingFee);
            }
        }

        // Set shipping methods in the event
        $event->setArgument('shippingMethods', $shippingMethods);
    }

    /**
     * Get the request data for DHL API call
     *
     * @param bool $isMock      Whether to use mock data
     * @param bool $isSandbox   Whether to use sandbox environment
     * @param object $shippingAddress The shipping address object
     *
     * @return array The request data
     *
     * @since 1.0.0
     */
    public function getRequestData($isMock, $isSandbox, $shippingAddress)
    {
        $accountNumber = $isMock ? '123456789' : ($isSandbox ? $this->params->get('test_account_number') : $this->params->get('account_number'));

        $cart        = new CartModel();
        $totalWeight = $cart->calculateTotalWeight();

        if ($totalWeight > 0) {
            $weight = $totalWeight;
        } else {
            $weight = $this->params->get('default_weight', 5.5);
        }

        $originAddress = $this->getOriginAddress();

        $data = [
            'accountNumber'          => $accountNumber,
            'originCountryCode'      => $originAddress->country,
            'originPostalCode'       => $originAddress->postcode,
            'originCityName'         => $originAddress->city,
            'destinationCountryCode' => $shippingAddress->country_code,
            'destinationPostalCode'  => $shippingAddress->postcode,
            'destinationCityName'    => $shippingAddress->city,
            'weight'                 => $weight,
            'length'                 => $this->params->get('default_length', 10),
            'width'                  => $this->params->get('default_width', 5),
            'height'                 => $this->params->get('default_height', 5),
            'plannedShippingDate'    => (new Date())->format('Y-m-d'),
            'isCustomsDeclarable'    => $this->params->get('is_customs_declarable', false),
            'unitOfMeasurement'      => $this->params->get('unit_of_measurement', 'metric'),
        ];

        return $data;
    }

    /**
     * Method to format the shipping method
     *
     * @param object $rate          The rate object
     * @param string $serviceName   The service name
     * @param float  $handlingFee   The handling fee
     *
     * @return array The formatted shipping method
     *
     * @since 1.0.0
     */
    protected function formatShippingMethod($rate, $serviceName, $handlingFee = 0)
    {
        $settings         = SettingsHelper::getSettings();
        $currencySettings = $settings->get('general.currency', 'USD:$');
        $currencyFormat   = $settings->get('general.currencyFormat', 'short');
        $part             = explode(':', $currencySettings);
        $currency         = '';
        $currencyFull     = $part[0];
        $currencyShort    = $part[1];

        if ($currencyFormat === 'short') {
            $currency = $currencyShort;
        } else {
            $currency = sprintf('%s ', $currencyFull);
        }

        $totalFee = $this->convertCurrency($rate['totalPrice'][0]['price'], $currencyFull);

        if ($handlingFee) {
            $totalFee += $this->convertCurrency($handlingFee, $currencyFull);
        }

        $deliveryDate = new Date($rate['deliveryCapabilities']['estimatedDeliveryDateAndTime'], new \DateTimeZone('UTC'));
        $name         = ucwords(strtoupper($this->_name) . ' ' . strtolower($serviceName));

        return [
            'uuid'               => $this->generate_uuid(),
            'name'               => $this->formatShippingTitle($name),
            'rate'               => $totalFee,
            'rate_with_currency' => $currency . $totalFee,
            'estimate'           => $deliveryDate->format('d M, Y'),
        ];
    }

    // get shop address form general settings
    public function getOriginAddress()
    {
        $settings = SettingsHelper::getSettings();

        $country = EasyStoreHelper::getCountryAlphaCode($settings->get('general.country', ''));

        $originAddress               = new \stdClass();
        $originAddress->address_1    = $settings->get('general.addressLineOne', '');
        $originAddress->address_2    = $settings->get('general.addressLineTwo', '');
        $originAddress->city         = $settings->get('general.city', '');
        $originAddress->state        = $settings->get('general.state', '');
        $originAddress->postcode     = $settings->get('general.postcode', '');
        $originAddress->country      = $country;
        $originAddress->company      = $settings->get('general.company', '');
        $originAddress->phone        = $settings->get('general.phone', '');

        return $originAddress;
    }

    /**
     * Method to get the currency rates
     *
     * @return array The currency rates
     *
     * @since 1.0.0
     */
    protected function getCurrencyRates()
    {
        $currencyRates = $this->params->get('currency_rates', []);
        return $currencyRates;
    }

    /**
     * Method to get the currency rate
     *
     * @param string $currency The currency
     *
     * @return float The currency rate
     *
     * @since 1.0.0
     */
    protected function getCurrencyRate($currency)
    {
        $currencyRates = $this->getCurrencyRates();

        if (isset($currencyRates)) {
            foreach ($currencyRates as $value) {
                if ($value->currency === $currency) {
                    return $value->rate;
                }
            }
        }
        return 1;
    }

    /**
     * Method to convert the currency
     *
     * @param float  $amount   The amount
     * @param string $currency The currency
     *
     * @return float The converted amount
     *
     * @since 1.0.0
     */
    protected function convertCurrency($amount, $currency)
    {
        $currencyRate = $this->getCurrencyRate($currency);

        return round($amount * $currencyRate, 2);
    }
}
