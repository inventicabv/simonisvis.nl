<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Site\View\Payment;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Site\Traits\Token;
use JoomShaper\Component\EasyStore\Site\Traits\DispatchEventTrait;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View for the payment form of the EasyStore component
 */
class HtmlView extends BaseHtmlView
{
    use Token, DispatchEventTrait;

    /**
     * Display the view
     *
     * @param   string  $template  The name of the layout file to parse.
     * @return  void
     */
    public function display($template = null)
    {
        /** @var CMSApplication */
        $app     = Factory::getApplication();
        $input   = $app->getInput();
        $orderID = $input->getString('order_id', '');

        $view     = $input->get('layout', '', 'STRING');
        $settings = SettingsHelper::getSettings();

        // get token from order using order id

        $result = (new EasyStoreDatabaseOrm())->get('#__easystore_orders', 'id', $orderID, 'order_token')->loadObject();
        $token  = $result->order_token;

        $this->continueShoppingUrl = Route::_($settings->get('products.shopPage', 'index.php'), false);
        $this->orderHistoryUrl     = Route::_('index.php?option=com_easystore&view=orders', false);
        $this->isGuestUser         = $app->getIdentity()->guest;
        $gustQuery                 = $this->isGuestUser ? '&guest_token=' . $token : '';
        $this->orderDetails        = Route::_('index.php?option=com_easystore&view=order&id=' . $orderID . $gustQuery, false);

        // Trigger before display event
        $this->onEasystorePaymentBeforeDisplay = $this->dispatchEasyStoreEvent('onEasystorePaymentBeforeDisplay', ['view' => $view])->getArgument('subject');

        if ($view === 'success') {
            $this->_layout = 'success';
            $paymentType   = $input->getString('type');

            // Trigger payment success event
            $this->onEasystorePaymentSuccessRender = $this->dispatchEasyStoreEvent('onEasystorePaymentSuccessRender', [
                'paymentType' => $paymentType,
                'orderId' => $orderID
            ])->getArgument('subject');

            // Trigger payment complete event
            $this->onEasystorePaymentComplete = $this->dispatchEasyStoreEvent('onEasystorePaymentComplete', [
                'paymentType' => $paymentType,
                'orderId' => $orderID
            ])->getArgument('subject');

            if (in_array($paymentType, EasyStoreHelper::getManualPaymentLists())) {
                $this->manualPaymentData = EasyStoreHelper::getManualPaymentInfo($paymentType);
            }
        }

        if ($view === 'cancel') {
            $this->_layout = 'cancel';
            
            // Trigger payment cancel event
            $this->onEasystorePaymentCancel = $this->dispatchEasyStoreEvent('onEasystorePaymentCancel', ['orderId' => $orderID])->getArgument('subject');
        }

        if ($view === 'error') {
            $this->_layout = 'error';
            
            // Trigger payment error event
            $this->onEasystorePaymentError = $this->dispatchEasyStoreEvent('onEasystorePaymentError', ['orderId' => $orderID])->getArgument('subject');
        }

        // Trigger after display event
        $this->onEasystorePaymentAfterDisplay = $this->dispatchEasyStoreEvent('onEasystorePaymentAfterDisplay', ['view' => $view])->getArgument('subject');

        parent::display($template);
    }
}
