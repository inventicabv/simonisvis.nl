<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Controller;

use Joomla\Input\Json;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Controller\BaseController;
use JoomShaper\Component\EasyStore\Site\Traits\Api;
use JoomShaper\Component\EasyStore\Site\Traits\Token;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Default Controller of EasyStore component
 *
 * @since  1.0.0
 */
class PaymentController extends BaseController
{
    use Api;
    use Token;

    /**
     * After a successful payment, the following functionalities will be triggered.
     *
     * @since 1.0.0
     */
    public function onPaymentSuccess()
    {
        $input       = $this->app->getInput();
        $paymentType = $input->getString('type');
        $orderID     = $input->getString('order_id');
        $uri         = Uri::getInstance(Route::_('index.php?option=com_easystore&view=payment&layout=success', false));

        if (!empty($paymentType)) {
            $uri->setVar('type', $paymentType);
        }

        if (!empty($orderID)) {
            $uri->setVar('order_id', $orderID);
        }

        $this->app->redirect($uri->toString());
    }

    /**
     * If the payment is canceled, the following functionalities will be triggered.
     * @since 1.0.0
     * @return void
     */
    public function onPaymentCancel()
    {
        $input   = $this->app->getInput();
        $orderID = $input->get('order_id', '', 'STRING');

        $this->app->redirect(Route::_('index.php?option=com_easystore&view=payment&layout=cancel&order_id=' . $orderID, false));
    }

    /**
     * This function manages payment notifications. It identifies the payment type, imports the relevant
     * plugin, and triggers the 'onPaymentNotify' event. If any errors occur, a user-friendly message is
     * displayed.
     *
     * @since 1.0.0
     */
    public function onPaymentNotify()
    {
        $paymentType = $this->input->getString('type');

        $plugin = Factory::getApplication()->bootPlugin($paymentType, 'easystore');
        $plugin->registerListeners();
        
        PluginHelper::importPlugin('easystore', $paymentType);

        /**
         * @var OrderController $orderController Instance of the OrderController used to manage order operations.
         */
        $orderController = $this->factory->createController('Order', 'Site', [], $this->app, $this->input);

        $rawPayLoad      = (new Json())->getRaw();
        $postData        = $this->app->input->post->getArray();
        $serverVariables = $this->app->input->server->getArray();
        $getData         = $this->app->input->get->getArray();
        $order           = $orderController;

        $event = AbstractEvent::create(
            'onPaymentNotify',
            [
                'subject' => (object) [
                    'raw_payload'      => $rawPayLoad,
                    'get_data'         => $getData,
                    'post_data'        => $postData,
                    'server_variables' => $serverVariables,
                    'order'            => $order,
                ],
            ]
        );

        try {
            $this->app->getDispatcher()->dispatch($event->getName(), $event);
        } catch (\Throwable $th) {
            $this->app->enqueueMessage($th->getMessage());
        }
    }

    /**
     * Redirects to the payment gateway based on the provided payment data.
     * Decodes the 'data' parameter, imports the necessary easystore plugin,
     * retrieves cart data for payment, and triggers the 'onPayment' event.
     * Handles errors during event dispatching by displaying an error message
     * and redirecting back to the checkout page.
     *
     * @since 1.0.3
     */

    public function navigateToPaymentGateway()
    {
        $data        = $this->app->input->get('data', '', 'STRING');
        $data        = json_decode(base64_decode($data));

        PluginHelper::importPlugin('easystore', $data->payment_method);

        $dataForPayment = EasyStoreHelper::getOrderDataForPayment($data);

        $event = AbstractEvent::create(
            'onPayment',
            [
                'subject' => (object) $dataForPayment,
            ]
        );

        try {
            $this->app->getDispatcher()->dispatch($event->getName(), $event);
        } catch (\Throwable $error) {
            $this->app->enqueueMessage($error->getMessage(), 'error');
            $this->app->redirect($dataForPayment->back_to_checkout_page);
        }
    }
}
