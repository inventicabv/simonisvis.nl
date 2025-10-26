<?php

/**
 * @package     EasyStore.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Email;

/**
 * Interface CustomerNameProviderInterface
 *
 * Defines a contract for classes that provide customer names based on order details.
 *
 * @package YourNamespace
 *
 * @since 1.3.0
 */
interface CustomerNameProviderInterface
{
    /**
     * Retrieves the customer name based on the provided order.
     *
     * @param Order $order The order object containing customer information.
     *
     * @return string The name of the customer.
     *
     * @throws InvalidArgumentException If the provided order is not valid.
     *
     * @since 1.3.0
     */
    public function getCustomerName($order): string;
}
