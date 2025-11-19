<?php

/**
 * @package     EasyStore.Site
 * @subpackage  EasyStore.Mollie
 *
 * @copyright   Copyright (C) 2023 - 2024 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Plugin\EasyStore\Mollie\Utils;

use Joomla\Registry\Registry;
use JoomShaper\Component\EasyStore\Administrator\Plugin\Constants;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Class that contains constants for the Paddle payment gateway.
 * @since 1.0.0
 */
class MollieConstants extends Constants
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
    protected $name = 'mollie';

    /**
     * The constructor method
     */
    public function __construct()
    {
        parent::__construct($this->name);
    }

    /**
     * Get Payment Environment
     *
     * @return string
     */
    public function getPaymentEnvironment()
    {
        return $this->params->get('shop_environment');
    }

    /**
     * Get Test Api Key
     *
     * @return string
     */

    public function getTestApiKey()
    {
        return $this->params->get('test_api_key', '');
    }

    /**
     * Get Live Api Key
     *
     * @return string
     */

    public function getLiveApiKey()
    {
        return $this->params->get('live_api_key', '');
    }

    /**
     * Get Secret Key based on payment environment
     *
     * @return string
     */

    public function getSecretKey()
    {
        return $this->getPaymentEnvironment() === 'test' ? self::getTestApiKey() : self::getLiveApiKey();
    }

    /**
     * Get Webhook URL (alias for getWebHookUrl for consistency)
     *
     * @return string
     */
    public function getWebhookUrl()
    {
        return $this->getWebHookUrl();
    }
}
