<?php

/**
 * @package     EasyStore.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Email;

use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;

/**
 * Class EmailService
 *
 * Provides functionality for sending emails using specified templates and event handling.
 *
 * @package YourNamespace
 * @implements EmailServiceInterface
 *
 * @since 1.3.0
 */
class EmailService implements EmailServiceInterface
{
    /**
     * Sends an email using the provided variables, template, recipient email, and event.
     *
     * This method checks if the EasystoreMail plugin is enabled and imports it if so.
     * It then creates an event with the provided details and dispatches it.
     *
     * @param array $variables An associative array of variables to be used in the email template.
     * @param string $template The name of the email template to use for sending the email.
     * @param string $to The recipient's email address.
     * @param string $onEvent The name of the event to trigger after sending the email.
     *
     * @return void
     *
     * @throws \InvalidArgumentException If the provided parameters are not valid.
     *
     * @since 1.3.0
     */
    public function send(array $variables, string $template, string $to, string $onEvent): void
    {
        if (empty($variables) || !is_array($variables)) {
            throw new \InvalidArgumentException('The variables must be a non-empty associative array.');
        }

        if (empty($template) || !is_string($template)) {
            throw new \InvalidArgumentException('The template must be a non-empty string.');
        }

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('The recipient email address is not valid.');
        }

        if (empty($onEvent) || !is_string($onEvent)) {
            throw new \InvalidArgumentException('The event name must be a non-empty string.');
        }

        // Plugin and event handling for email sending
        if (PluginHelper::isEnabled('system', 'easystoremail')) {
            PluginHelper::importPlugin('system', 'easystoremail');

            $event = AbstractEvent::create($onEvent, [
                'subject' => (object) [
                    'variables'      => $variables,
                    'type'           => $template,
                    'customer_email' => $to,
                ],
            ]);

            Factory::getApplication()->getDispatcher()->dispatch($event->getName(), $event);
        }
    }
}
