<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Plugin;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Extension\ExtensionHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


/**
 * Constants Class
 *
 * This class encapsulates constants and configuration settings related to the EasyStore payment plugin.
 * @since 1.0.5
 */
class Constants
{
    /**
     * The payment plugin name
     *
     * @var string
     */
    protected $name;

    /**
     * Plugin params
     *
     * @var Registry
     */
    protected $params;

    /** @var CMSApplication */
    protected $app;

    /**
     * Constructor method for the payment plugin constants
     *
     * @param string $name the plugin name
     * @since 1.0.5
     */
    public function __construct(string $name)
    {
        $this->name   = $name;
        $this->params = new Registry($this->getPluginParams($this->name));
        $this->app    = Factory::getApplication();
    }

    public function getPluginParams($element)
    {
       $plugin = ExtensionHelper::getExtensionRecord($element, 'plugin', 0, 'easystore');

       return $plugin->params;
    }

    /**
     *  Returns a URL to which the user will be redirected upon completing the payment procedure on the payment portal.
     *
     * @param  int|null $orderID  -- Order ID(optional).
     * @return string
     * @since  1.0.5
     */
    public function getSuccessUrl($orderID = null)
    {
        $uri = new Uri(Route::_(Uri::root() . 'index.php?option=com_easystore&task=payment.onPaymentSuccess'));
        $uri->setVar('type', $this->name);

        if (!is_null($orderID)) {
            $uri->setVar('order_id', $orderID);
        }

        return $uri->toString();
    }

    /**
     * Returns the cancellation url if the user cancel the payment on the payment portal.
     *
     * @return string
     * @since  1.0.5
     */
    public function getCancelUrl($orderID)
    {
        return Route::_(Uri::root() . 'index.php?option=com_easystore&task=payment.onPaymentCancel&order_id=' . $orderID, false);
    }

    /**
     * Returns the webhook URL which will be used to receive notifications from payment portal.
     *
     * @return string
     * @since  1.0.5
     */
    public function getWebHookUrl()
    {
        return Route::_(Uri::root() . 'index.php?option=com_easystore&task=payment.onPaymentNotify&type=' . $this->name, false);
    }

    /**
     * Retrieve the payment environment if a `payment environment` field is configured in the settings.
     *
     * @return string
     * @since  1.0.6
     */

    public function getPaymentEnvironment()
    {
        return $this->params->get('payment_environment', 'test');
    }

    /**
     * Returns a test Key if a `test key` field is configured in the settings.
     *
     * @return string
     * @since  1.0.6
     */

    public function getTestKey()
    {
        return $this->params->get('test_key', '');
    }

    /**
     * Returns a Live Key if a `live key` field is configured in the settings.
     *
     * @return string
     * @since  1.0.6
     */

    public function getLiveKey()
    {
        return $this->params->get('live_key', '');
    }

    /**
     * Returns the secret key or an alternative based on the payment environment and the values set in the `test_key` and `live_key` fields.
     *
     * @return string
     * @since  1.0.6
     */
    public function getSecretKey()
    {
        return $this->getPaymentEnvironment() === 'test' ? self::getTestKey() : self::getLiveKey();
    }

    /**
     * Retrieve additional information from the plugin parameters.
     *
     * @return string The additional information retrieved from the plugin parameters, or an empty string if not set.
     * @since  1.0.10
     */
    public function getAdditionalInformation()
    {
        return $this->params->get('additional_information', '');
    }

    /**
     * Retrieve title from the plugin parameters.
     *
     * @return string The title retrieved from the plugin parameters, or an empty string.
     * @since  1.1.0
     */
    public function getTitle()
    {
        return $this->params->get('title', '');
    }

    /**
     * Get the test private key.
     *
     * @return string The test private key.
     * @since  1.1.2
     */
    public function getTestPrivateKey()
    {
        return $this->params->get('test_private_key', '');
    }

    /**
     * Get the live private key.
     *
     * @return string The live private key.
     * @since  1.1.2
     */
    public function getLivePrivateKey()
    {
        return $this->params->get('live_private_key', '');
    }

    /**
     * Get the appropriate private key based on the payment environment.
     *
     * @return string The private key.
     * @since  1.1.2
     */
    public function getPrivateKey()
    {
        return $this->getPaymentEnvironment() === 'test' ? self::getTestPrivateKey() : self::getLivePrivateKey();
    }

    /**
     * Get the test public key.
     *
     * @return string The test public key.
     * @since  1.1.2
     */
    public function getTestPublicKey()
    {
        return $this->params->get('test_public_key', '');
    }

    /**
     * Get the live public key.
     *
     * @return string The live public key.
     * @since  1.1.2
     */
    public function getLivePublicKey()
    {
        return $this->params->get('live_public_key', '');
    }

    /**
     * Get the appropriate public key based on the payment environment.
     *
     * @return string The public key.
     * @since  1.1.2
     */
    public function getPublicKey()
    {
        return $this->getPaymentEnvironment() === 'test' ? self::getTestPublicKey() : self::getLivePublicKey();
    }

    /**
     * Get the name of the plugin.
     *
     * @return string The name of the plugin.
     * @since  1.4.2
     */
    public function getName()
    {
        return $this->name;
    }
}