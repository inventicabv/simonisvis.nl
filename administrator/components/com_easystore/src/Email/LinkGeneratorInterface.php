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
 * Interface LinkGeneratorInterface
 *
 * Defines a contract for link generation implementations based on order details.
 *
 * @package YourNamespace
 *
 * @since 1.3.0
 */
interface LinkGeneratorInterface
{
    /**
     * Generates a link based on the provided order object.
     *
     * @param object $order The order object containing necessary information for link generation.
     *
     * @return string The generated link as a string.
     *
     * @throws InvalidArgumentException If the provided order object is not valid.
     *
     * @since 1.3.0
     */
    public function generateLink(object $order): string;
}
