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
 * Interface EmailServiceInterface
 *
 * Defines a contract for email service implementations.
 *
 * @package     EasyStore.Administrator
 * @subpackage  com_easystore
 *
 * @since 1.3.0
 */
interface EmailServiceInterface
{
    /**
     * Sends an email using the provided variables, template, recipient email, and event.
     *
     * @param array $variables An associative array of variables to be used in the email template.
     * @param string $template The name of the email template to use for sending the email.
     * @param string $to The recipient's email address.
     * @param string $onEvent The name of the event to trigger after sending the email.
     *
     * @return void
     *
     * @throws InvalidArgumentException If the provided parameters are not valid.
     *
     * @since 1.3.0
     */
    public function send(array $variables, string $template, string $to, string $onEvent): void;
}
