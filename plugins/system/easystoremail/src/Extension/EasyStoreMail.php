<?php

/**
 * @package     EasyStore.Plugin
 * @subpackage  System.easystoremail
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Plugin\System\EasyStoreMail\Extension;

use Joomla\CMS\Log\Log;
use Joomla\Event\Event;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use JoomShaper\Component\EasyStore\Site\Lib\Email;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


/**
 * Easy Store Mail plugin for mail sending service.
 *
 * @since  1.0.0
 */
final class EasyStoreMail extends CMSPlugin implements SubscriberInterface
{
    /**
     * function for getSubscribedEvents : new Joomla 4 feature
     *
     * @return array
     *
     * @since   1.0.0
     * @since   1.4.4 - Added onOrderTracking and onOrderRefund events
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onSuccessfulPayment'   => 'sendMail',
            'onFailedPayment'   => 'sendMail',
            'onOrderPlaced'   => 'sendMail',
            'onOrderRefund'   => 'sendMail',
            'onOrderTracking'   => 'sendMail',
        ];
    }

    public function sendMail(Event $event)
    {
        $arguments   = $event->getArguments();
        $data        = $arguments['subject'];

        if ($data->type == 'order_confirmation_admin') {
            $settings   = SettingsHelper::getSettings();
            $storeEmail = $settings->get('general.storeEmail', '');

            self::deliverEmail($storeEmail, 'order_confirmation_admin', $data->variables);
        } else {
            self::deliverEmail($data->customer_email, $data->type, $data->variables);
        }
    }

    /**
     * Sends an email with the provided details.
     *
     * @param  string $email   The email address to send the email to.
     * @param  string $type    The type of email to send.
     * @param  array $contents The contents to bind to the email.
     * @return void
     * @since  1.0.9
     */
    private function deliverEmail($email, $type, $contents)
    {
        try {
            $email = new Email($email, $type);
            $email->bind($contents)->send();
            Log::add('EasyStoreMail: Email Send success - ' . $type, Log::ALL, 'email.easystore');
        } catch (\Exception $e) {
            Log::add('EasyStoreMail: Email Send failed - ' . $type . $e->getMessage(), Log::ALL, 'email.easystore');
        }
    }
}
