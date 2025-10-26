<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Site\Model;

use Exception;
use Throwable;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseInterface;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper as AdministratorEasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class CheckoutModel extends ItemModel
{
    /**
     * Model context string.
     *
     * @var        string
     */
    protected $_context = 'com_easystore.checkout';

    /**
     * Returns a message for display
     * @param int $pk Primary key of the "message item", currently unused
     * @return mixed Message object
     */
    public function getItem($pk = null)
    {
        $user      = Factory::getApplication()->getIdentity();
        $cartModel = new CartModel();
        $cart      = $cartModel->getItem();

        $settings           = SettingsHelper::getSettings();
        $allowGuestCheckout = $settings->get('checkout.allow_guest_checkout', false);

        if (!$allowGuestCheckout && $user->guest) {
            throw new \Exception('Login before checking out.');
        }

        $customer = EasyStoreHelper::getCustomerByUserId($user->id);

        return (object) [
            'customer' => $customer,
            'cart'     => $cart,
        ];
    }

    public function getInformation()
    {
        $user     = Factory::getApplication()->getIdentity();
        $customer = EasyStoreHelper::getCustomerByUserId($user->id);

        if (!empty($customer->shipping_address) && is_string($customer->shipping_address)) {
            $customer->shipping_address = json_decode($customer->shipping_address);
        }

        if (!empty($customer->billing_address) && is_string($customer->billing_address)) {
            $customer->billing_address = json_decode($customer->billing_address);
        }

        return $customer;
    }

    public function getGuestShippingAddress($email)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('shipping_address')
            ->from($db->quoteName('#__easystore_guests'))
            ->where($db->quoteName('email') . ' = ' . $db->quote($email));

        $db->setQuery($query);

        try {
            $shipping = $db->loadResult() ?? null;

            if (!empty($shipping) && is_string($shipping)) {
                return json_decode($shipping);
            }
        } catch (Throwable $error) {
            throw $error;
        }

        return null;
    }

    public function getShipping()
    {
        $params = ComponentHelper::getParams('com_easystore');

        $shipping = $params->get('shipping', '');
        $shipping = is_string($shipping) ? json_decode($shipping) : $shipping;

        if (is_object($shipping)) {
            $shipping = get_object_vars($shipping);
        }

        if (empty($shipping)) {
            return [];
        }

        foreach ($shipping as &$method) {
            $method->price_with_currency = AdministratorEasyStoreHelper::formatCurrency($method->price);
        }

        unset($method);

        return array_values((array) $shipping);
    }

    public function saveCustomerInformation($data)
    {
        $orm       = new EasyStoreDatabaseOrm();
        $cartModel = new CartModel();

        try {
            $data = $orm->updateOrCreate('#__easystore_users', $data, 'user_id');

            if ($data->id) {
                $cartModel->updateStatus('information');
            }

            return $data->id;
        } catch (Throwable $error) {
            throw $error;
        }
    }

    public function saveGuestCustomerInformation($data)
    {
        if (empty($data->email) || empty($data->shipping_address)) {
            throw new Exception('Missing required fields.');
        }

        $orm = new EasyStoreDatabaseOrm();

        try {
            $orm->updateOrCreate('#__easystore_guests', $data, 'email');
        } catch (Throwable $error) {
            throw $error;
        }
    }

    public function removeCouponCode($cartId)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->update($db->quoteName('#__easystore_cart'))
            ->set([
                $db->quoteName('coupon_code') . ' = NULL',
                $db->quoteName('coupon_type') . ' = NULL',
                $db->quoteName('coupon_amount') . ' = 0',
            ])
            ->where($db->quoteName('id') . ' = ' . (int) $cartId);

        $db->setQuery($query);

        try {
            $db->execute();

            return true;
        } catch (Throwable $error) {
            throw $error;
        }
    }
}
