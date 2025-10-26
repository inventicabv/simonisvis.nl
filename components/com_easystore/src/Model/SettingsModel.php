<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Site\Model;

use Throwable;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseInterface;
use JoomShaper\Component\EasyStore\Site\Helper\ArrayHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper as AdministratorEasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


class SettingsModel extends ListModel
{
    /**
     * Model context string.
     *
     * @var        string
     */
    protected $_context = 'com_easystore.settings';

    public function getSettingByKey(string $key)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('value')
            ->from($db->quoteName('#__easystore_settings'))
            ->where($db->quoteName('key') . ' = ' . $db->quote($key));

        $db->setQuery($query);

        try {
            $setting = $db->loadResult() ?? null;

            if (!is_null($setting)) {
                return json_decode($setting);
            }

            return null;
        } catch (Throwable $error) {
            throw $error;
        }
    }

    public function getCountriesWithStates()
    {
        $settings = SettingsHelper::getSettings();
        $shipping = $settings->get('shipping', []);

        $countries = [];

        foreach ($shipping as $shippingItem) {
            if (!empty($shippingItem->regions)) {
                foreach ($shippingItem->regions as $region) {
                    $states                      = $region->states ?? [];
                    $countries[$region->country] = $countries[$region->country] ?? [];
                    $countries[$region->country] = array_unique(array_merge($countries[$region->country], $states));
                }
            }
        }

        return $countries;
    }

    protected function getRegionByCountry($shipping, $country)
    {
        $region = null;

        foreach ($shipping as $item) {
            if (!empty($item->regions)) {
                foreach ($item->regions as $value) {
                    if ($value->country == $country && empty($value->states)) {
                        $region = $item;
                        break;
                    }
                }
            }

            if ($region) {
                break;
            }
        }

        return $region;
    }

    private function isRegionMatched($region, $country, $state)
    {
        $country = (string) $country;
        $state = (string) $state;

        if ((string) $region->country !== $country) {
            return false;
        }

        if (empty($region->states)) {
            return true;
        }

        $states = array_map(function ($item) {
            return (string) $item;
        }, $region->states);

        if (in_array($state, $states, true)) {
            return true;
        }

        return false;
    }

    protected function getRegionByCountryAndState($shipping, $country, $state)
    {
        return ArrayHelper::find(function ($item) use ($country, $state) {
            return ArrayHelper::find(function ($region) use ($country, $state) {
                return $this->isRegionMatched($region, $country, $state);
            }, $item->regions);
        }, $shipping);
    }

    protected function getRegion($shipping, $country, $state = null)
    {
        if (empty($country) || empty($shipping)) {
            return null;
        }

        if (!$state) {
            return $this->getRegionByCountry($shipping, $country);
        }

        return $this->getRegionByCountryAndState($shipping, $country, $state);
    }

    public function getShipping($country = null, $state = null, $subtotal = null)
    {
        $settings = SettingsHelper::getSettings();
        $shipping = $settings->get('shipping', []);

        if (empty($country)) {
            return null;
        }

        $shipping = ArrayHelper::filter(function ($item) {
            return $item->enabled;
        }, $shipping);

        $region = $this->getRegion($shipping, $country, $state);

        if (empty($region)) {
            return null;
        }

        if ($region->methodType === 'none') {
            return null;
        }

        $method    = $region->methodType;
        $methodMap = [
            'flat'   => 'flatRate',
            'free'   => 'freeShipping',
            'weight' => 'rateByWeight',
        ];
        $methodKey  = $methodMap[$method];
        $methodData = $region->$methodKey ?? [];

        if ($method === 'free') {
            $methodData->rate = 0;
            $methodData       = [$methodData];
        }

         $shippingMethods = array_map(function ($item) use ($method, $subtotal) {
            if ($method === 'weight') {
                $cartModel        = new CartModel();
                $cumulativeWeight = $cartModel->calculateTotalWeight();
                $item->rate       = $this->calculateTheRateByWeight($item->weights, $cumulativeWeight);
            }

            if (!empty($item->offerFreeShipping)) {
                $offerAmount = $item->offerOnAmount ?? null;

                if (!is_null($offerAmount)) {
                    $offerAmount = (float) $offerAmount;

                    if ($subtotal > $offerAmount) {
                        $item->rate = 0;
                    }
                }
            }

            $rate                     = $item->rate ?? 0;
            $item->rate_with_currency = AdministratorEasyStoreHelper::formatCurrency($rate);

            return $item;
        }, $methodData);

        return $shippingMethods;
    }

    protected function calculateTheRateByWeight($data, $weight)
    {
        if (empty($data) || !is_array($data)) {
            return 0;
        }

        foreach ($data as $item) {
            $from = $item->from ?? null;
            $to   = $item->to ?? null;
            $rate = $item->rate ?? 0;

            if (is_null($from)) {
                return 0.00;
            }

            $from = (float) $from;
            $to   = empty($to) ? INF : (float) $to;

            if ($weight >= $from && $weight <= $to) {
                return (float) $rate;
            }
        }

        return 0.00;
    }

    public function getShippingCarriers($country)
    {
        $settings = SettingsHelper::getSettings();
        $shipping = $settings->get('shipping', []);

        if (empty($shipping)) {
            return [];
        }

        $carriers = [];

        foreach ($shipping as $item) {
            if ($item->regions) {
                $region = ArrayHelper::find(function ($region) use ($country) {
                    return ($region->country == $country) ;
                }, $item->regions);
                
                if (empty($region)) {
                    continue;
                }
            }
            if (!empty($item->carriers)) {
                foreach ($item->carriers as $carrier) {
                    if (!empty($carrier->name) && $carrier->enabled) {
                        $carriers[] = $carrier->name;
                    }
                }
            }
        }

        return array_unique($carriers);
    }
}
