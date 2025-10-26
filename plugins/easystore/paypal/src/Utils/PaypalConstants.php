<?php

/**
 * @package     EasyStore.Site
 * @subpackage  EasyStore.Paypal
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Plugin\EasyStore\Paypal\Utils;

use Joomla\Registry\Registry;
use JoomShaper\Component\EasyStore\Administrator\Plugin\Constants;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


/**
 * Class that contains constants for the Paypal payment gateway.
 * @since 1.0.0
*/
class PaypalConstants extends Constants
{
    /**
     * Plugin parameters
     *
     * @var Registry
     */
    protected $params;

    /**
     * The payment plugin name
     *
     * @var string
     */
    protected $name = 'paypal';

    const API_URL_TEST = 'https://api-m.sandbox.paypal.com';
    const API_URL_LIVE = 'https://api-m.paypal.com';

    public function __construct()
    {
        parent::__construct($this->name);
    }

    /**
     * Get Payment Environment.
     *
     * @return string
     * @since  1.0.5
     */

    public function getPaymentEnvironment()
    {
        return $this->params->get('shop_environment', 'sandbox');
    }

    /**
     * Get the Paypal merchant email.
     *
     * @return string
     * @since  1.0.0
     */
    public function getMerchantEmail()
    {
        return static::getPaymentEnvironment() === 'sandbox' ? $this->params->get('paypal_id','') : $this->params->get('live_paypal_id','');
    }

    /**
     * Returns the appropriate PayPal API URL based on the payment environment.
     *
     * @return string The PayPal API URL for the current environment.
     * @since  2.0.0
     */
    public function getApiURL(): string
    {
        return $this->getPaymentEnvironment() === 'sandbox' ? static::API_URL_TEST: static::API_URL_LIVE;
    }

    /**
     * Retrieves the PayPal client secret key based on the payment environment.
     *
     * @since 2.0.0
     */
    public function getClientSecretKey()
    {
        return $this->getPaymentEnvironment() === 'sandbox' ? $this->params->get('test_client_secret_key', '') : $this->params->get('live_client_secret_key', '');
    }

    /**
     * Retrieves the PayPal client ID based on the payment environment.
     *
     * @since 2.0.0
     */
    public function getClientID()
    {
        return $this->getPaymentEnvironment() === 'sandbox' ? $this->params->get('test_client_id', '') : $this->params->get('live_client_id', '');
    }

    /**
     * Retrieves the PayPal webhook ID for the current environment.
     *
     * @since 2.0.0
     */
    public function getWebhookID()
    {
        return $this->getPaymentEnvironment() === 'sandbox' ? $this->params->get('test_webhook_id', '') : $this->params->get('live_webhook_id', '');
    }
}
