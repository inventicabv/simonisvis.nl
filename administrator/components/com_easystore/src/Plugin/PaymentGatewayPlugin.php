<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Plugin;

use Joomla\Event\Event;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\DispatcherInterface;
use Joomla\CMS\Application\CMSApplication;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


/**
 * PaymentGatewayPlugin is an abstract class that extends CMSPlugin for implementing different types of
 * payment gateway. It has three abstract method that will be implemented by the subclasses.
 *
 * @since 1.0.0
 */

abstract class PaymentGatewayPlugin extends CMSPlugin
{
    /**
     * The application object
     *
     * @var CMSApplication
     *
     * @since 1.0.0
     */
    protected $app;

    /**
     * Constructor
     *
     * @param   DispatcherInterface  $dispatcher  The event dispatcher
     * @param   array                $config      An optional associative array of configuration settings.
     *                                            Recognized key values include 'name', 'group', 'params', 'language'
     *                                            (this list is not meant to be comprehensive).
     *
     * @since   1.0.0
     */
    public function __construct(DispatcherInterface $dispatcher, array $config = [])
    {
        parent::__construct($dispatcher, $config);
    }

    /**
     * On Before Payment Event. This event is triggered before the payment process to ensure all required fields are set before processing a payment.
     *
     * @param Event $event The application event we are handling.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    abstract public function onBeforePayment(Event $event);

    /**
     * On Payment Event. This event is triggered when a payment event occurs, accepting an Event object as a parameter. The Event object contains cart data required for payment processing.
     *
     * @param Event $event The application event we are handling.
     *
     * @return void
     *
     * @since 1.0.0
     */
    abstract public function onPayment(Event $event);

    /**
     * On Payment Notify Event. This event is triggered when it receives a notification from payment portal. It takes an Event object as a parameter. The Event object contains relevant data for payment notification, including raw payload, GET data, POST data, server variables, and an instance of Order Class.
     *
     * @param Event $event The application event we are handling.
     *
     * @return void
     *
     * @since 1.0.0
     */
    abstract public function onPaymentNotify(Event $event);
}
