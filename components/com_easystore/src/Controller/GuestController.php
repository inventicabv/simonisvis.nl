<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\MVC\Controller\BaseController;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Guest Controller of EasyStore component
 *
 * @since  1.2.2
 */
class GuestController extends BaseController
{
    /**
     * Handles the request to generate a new token for guest orders.
     *
     * @return void
     * @throws \Exception If no order is found or email configuration is invalid.
     *
     * @since 1.2.2
     */
    public function requestToken()
    {
        $orm           = new EasyStoreDatabaseOrm();
        $input         = $this->app->getInput();
        $customerEmail = $input->get('customer_email', '', 'email');
        $orders        = [];
        $db            = Factory::getContainer()->get(DatabaseInterface::class);
        $query         = $db->getQuery(true);

        $query->select('*');
        $query->from($db->quoteName('#__easystore_orders'));
        $query->where($db->quoteName('customer_email') . ' = ' . $db->quote($customerEmail));
        $query->where($db->quoteName('published') . ' = 1');

        $db->setQuery($query);

        try {
            $orders = $db->loadObjectList();
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }

        if (empty($orders)) {
            $this->setRedirect(Uri::current(), Text::_("COM_EASYSTORE_ORDER_NOT_FOUND"), 'error');
            return;
        }

        $replacements = [];
        foreach ($orders as $order) {
            if (empty($order->order_token)) {
                /**
                 * @var OrderController $orderController Instance of the orderController used to manage order operations.
                 */
                $orderController = $this->factory->createController('Order', 'Site', [], $this->app, $this->input);

                $newToken = $orderController->getUniqueGuestOrderToken();

                $values = (object) [
                    'id'          => $order->id,
                    'order_token' => $newToken,
                ];

                $replacements[] = [
                    'order_link' => '<li><a href="' . Route::_(Uri::root() . 'index.php?option=com_easystore&view=order&id=' . $order->id . '&guest_token=' . $newToken) . '"target="__blank"> ' . Text::sprintf('COM_EASYSTORE_GUEST_ORDER_TOKEN_EMAIL_ORDER_ID', $order->id) . '</a></li>',
                ];

                $orm->update('#__easystore_orders', $values);
            }
        }

        if (empty($replacements)) {
            $this->setRedirect(Uri::current(), Text::_('COM_EASYSTORE_GUEST_ORDER_TOKEN_EMAIL_SUCCESS_MESG'));
            return;
        }

        $this->sendNewTokenLink($customerEmail, $replacements);
    }

    /**
     * Sends the new token link to the customer's email.
     *
     * @param string $customerEmail The customer's email address.
     * @param array $replacements An array of replacement data for the email body.
     * @return bool True on success, false on failure.
     * @throws \Exception If email configuration is invalid.
     *
     * @since 1.2.2
     */
    private function sendNewTokenLink($customerEmail, $replacements)
    {
        /** @var CMSApplication */
        $app    = Factory::getApplication();
        $config = $app->getConfig();

        $senderEmail = $config->get('mailfrom');
        $senderName  = $config->get('fromname');

        if (empty($senderEmail) || empty($senderName)) {
            throw new \Exception(Text::_('COM_EASYSTORE_GUEST_ORDER_TOKEN_EMAIL_ERROR_MESG'), 400);
        }

        $subject            = Text::_('COM_EASYSTORE_GUEST_ORDER_TOKEN_EMAIL_EMAIL_SUBJECT');
        $into               = Text::_('COM_EASYSTORE_GUEST_ORDER_TOKEN_EMAIL_EMAIL_BODY_INTRO');
        $body               = Text::_('COM_EASYSTORE_GUEST_ORDER_TOKEN_EMAIL_EMAIL_BODY');
        $orderLinkWithToken = '';

        foreach ($replacements as $replacement) {
            foreach ($replacement as $key => $value) {
                $orderLinkWithToken .= str_replace('{' . strtoupper($key) . '}', $value, $body);
            }
        }

        $body    = $orderLinkWithToken;

        $mailer = Factory::getMailer();
        $mailer->isHTML(true);
        $mailer->setSender([$senderEmail, $senderName]);
        $mailer->addRecipient($customerEmail);

        $mailer->setSubject($subject);
        $mailer->setBody($into . '<ul>' . $body . '</ul>');

        try {
            $mailer->Send();
            $this->setRedirect(Uri::current(), Text::_('COM_EASYSTORE_GUEST_ORDER_TOKEN_EMAIL_MESG'));
            return true;
        } catch (\Throwable $error) {
            throw $error;
            return false;
        }
    }
}
