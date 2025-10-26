<?php

/**
 * @package     EasyStore.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Email;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Class OrderLinkGenerator
 *
 * Generates links for orders based on order details and guest tokens.
 *
 * @package YourNamespace
 * @implements LinkGeneratorInterface
 *
 * @since 1.3.0
 */
class OrderLinkGenerator implements LinkGeneratorInterface
{
    /**
     * Generates a link based on the provided order object.
     *
     * If the order is a guest order, it appends the guest token to the generated link.
     *
     * @param object $order The order object containing necessary information for link generation.
     *
     * @return string The generated link as a string.
     *
     * @throws \InvalidArgumentException If the provided order object is not valid.
     *
     * @since 1.3.0
     */
    public function generateLink(object $order): string
    {
        // Validate the order object
        if (!isset($order->id) || !is_numeric($order->id)) {
            throw new \InvalidArgumentException('The provided order object must have a valid numeric ID.');
        }

        if (!property_exists($order, 'is_guest_order')) {
            throw new \InvalidArgumentException('The provided order object must have an is_guest_order property.');
        }

        if ($order->is_guest_order && !property_exists($order, 'order_token')) {
            throw new \InvalidArgumentException('The provided order object must have an order_token property for guest orders.');
        }

        $token = '';

        if ($order->is_guest_order && !empty($order->order_token)) {
            $token = '&guest_token=' . $order->order_token;
        }

        return Route::_(Uri::root() . 'index.php?option=com_easystore&view=order&id=' . $order->id . $token);
    }
}
