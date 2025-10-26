<?php

/**
 * @package     EasyStore.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Email;

use Joomla\CMS\Factory;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;

/**
 * Class CustomerNameProvider
 *
 * Provides customer names based on order details and checkout settings.
 *
 * @package YourNamespace
 * @implements CustomerNameProviderInterface
 *
 * @since 1.3.0
 */
class CustomerNameProvider implements CustomerNameProviderInterface
{
    /**
     * Retrieves the customer name based on the order and checkout settings.
     *
     * If guest checkout is enabled, it retrieves the name from the shipping address
     * of the order. Otherwise, it returns the name of the currently authenticated user.
     *
     * @param Order $order The order object containing customer and shipping information.
     *
     * @return string The name of the customer.
     *
     * @throws \InvalidArgumentException If the provided order is not valid.
     *
     * @since 1.3.0
     */
    public function getCustomerName($order): string
    {
        if (!empty($order->shipping_address) && is_string($order->shipping_address)) {
            $order->shipping_address = json_decode($order->shipping_address);
        }

        if (!isset($order->shipping_address) || !property_exists($order->shipping_address, 'name')) {
            throw new \InvalidArgumentException('The provided order object must have a valid shipping_address with a name.');
        }

        $settings           = SettingsHelper::getSettings();
        $allowGuestCheckout = $settings->get('checkout.allow_guest_checkout', false);

        if ($allowGuestCheckout && $order->customer_id == null) {
            return $order->shipping_address->name;
        }

        if ($order->customer_id) {
            return $order->company_name ?: $order->shipping_address->name;
        }


        return Factory::getApplication()->getIdentity()->name ?: $order->shipping_address->name;
    }
}
