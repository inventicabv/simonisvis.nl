<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Traits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use stdClass;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Administrator\Checkout\OrderManager;
use JoomShaper\Component\EasyStore\Administrator\Helper\CustomInvoiceHelper;
use JoomShaper\Component\EasyStore\Site\Model\ProductModel;
use JoomShaper\Component\EasyStore\Site\Model\SettingsModel;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Model\OrderModel;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper as HelperEasyStoreHelper;
use JoomShaper\Component\EasyStore\Site\Helper\OrderHelper;

trait Order
{
    use Notifiable;

    /**
     * Function for processing orderTracking api method
     *
     * @return void
     */
    public function orderTracking()
    {
        $requestMethod = $this->getInputMethod();
        $id            = $this->getInput('id', null, 'INT');

        $this->checkNotAllowedMethods(['GET', 'POST', 'PUT', 'DELETE'], $requestMethod);

        $this->addUpdateOrderTracking($id);
    }

    /**
     * Function for processing duplicateOrder api methods
     *
     * @return void
     */
    public function duplicateOrder()
    {
        $requestMethod = $this->getInputMethod();

        $this->checkNotAllowedMethods(['POST', 'PUT', 'PATCH', 'DELETE'], $requestMethod);

        $this->createDuplicateOrder();
    }

    /**
     * Function to get Orders
     *
     * @return void
     */
    public function getOrders()
    {
        $params = (object) [
            'limit'         => $this->getInput('limit', 10),
            'offset'        => $this->getInput('offset', 0),
            'search'        => $this->getInput('search', '', 'STRING'),
            'orderStatus'   => $this->getInput('orderStatus', '', 'STRING'),
            'fulfilment'    => $this->getInput('fulfilment', '', 'STRING'),
            'paymentStatus' => $this->getInput('paymentStatus', '', 'STRING'),
            'sortBy'        => $this->getInput('sortBy', '', 'STRING'),
            'customerId'   => $this->getInput('customer_id', '', 'STRING'),
            'all'           => $this->getInput('all', false),
        ];

        $model = new OrderModel();
        $items = $model->getOrders($params);

        $this->sendResponse($items);
    }

    /**
     * Function to get Order by Id
     *
     * @param int $id
     * @return void
     */
    public function orderById()
    {
        $input = Factory::getApplication()->input;
        $orderId    = $input->get('id', null, 'INT');

        if (is_null($orderId)) {
            throw new \Exception('The ID is missing!');
        }

        $model  = new OrderModel();
        $order = $model->getItem($orderId);

        if (empty($order)) {
            $this->sendResponse(['message' => 'Order not found'], 404);
        }

        $userId = EasyStoreHelper::getCustomerById($order->customer_id)->user_id ?? 0;
        $customer = EasyStoreHelper::getCustomerByUserId($userId);
        $token = $order->order_token ?? null;

        $orderManager = OrderManager::createWith($orderId, $customer, $token);
        $item = $orderManager->getOrderItemWithCalculation();

        if ($order->payment_status === "paid") {
            $item->due_amount =  0.00;
            $item->due_amount_with_currency = HelperEasyStoreHelper::formatCurrency($item->due_amount);
        }
        


        // @TODO: need to move this into the orderManager
        if (!empty($item->activities)) {
            $lastActivity     = reset($item->activities);
            $lastActivityType = $lastActivity->activity_type;

            if ($lastActivityType === 'marked_as_fulfilled' && isset($result->user->user_id)) {
                $model->updateProductsReviews($result->products, $result->user);
            }
        }

        $item->order_number = OrderHelper::formatOrderNumber($item->id);
        $item->invoice_number = CustomInvoiceHelper::getGeneratedCustomInvoiceId($item->custom_invoice_id);

        $country = EasyStoreHelper::getCountryCode($item->shipping_address->country);
        $item->seller_tax_id = OrderHelper::getSellerTaxID($country->code ?? '');

        $this->sendResponse($item);
    }

    /**
     * Update Order by id with PUT request
     *
     * @return void
     */
    public function updateOrders()
    {
        $response = (object) [
            'status'  => false,
            'message' => '',
        ];

        $acl = AccessControl::create();

        if (!$acl->canCreate()) {
            $this->sendResponse(['message' => Text::_("COM_EASYSTORE_PERMISSION_ERROR_MSG")], 403);
        }

        $id          = $this->getInput('id', 0, 'INT');
        $orderStatus = $this->getInput('order_status', 'draft', 'STRING');

        if (empty($id)) {
            $response->status  = false;
            $response->message = Text::_("COM_EASYSTORE_FAILED_TO_UPDATE_ORDER");

            $this->sendResponse($response);
        }

        $model = new OrderModel();

        $response     = new stdClass();
        $response->id = null;

        $orderInformation = new stdClass();

        $orderInformation->id            = $id;
        $orderInformation->order_status  = $orderStatus;
        $orderInformation->creation_date = null;
        $isActivated                     = false;

        if (!$model->checkCreationDateExists($id) && $orderStatus === 'active') {
            $orderInformation->creation_date = Factory::getDate('now');
            $isActivated                     = true;
        }

        $orderInformation->customer_id      = $this->getInput('customer_id', 0, 'INT');
        $orderInformation->customer_note    = $this->getInput('customer_note', '', 'RAW');
        $orderInformation->payment_status   = $this->getInput('payment_status', 'unpaid', 'STRING');
        $orderInformation->fulfilment       = $this->getInput('fulfilment', 'unfulfilled', 'STRING');
        $orderInformation->discount_type    = $this->getInput('discount_type', 'percent', 'STRING');
        $orderInformation->discount_value   = $this->formatToNumeric($this->getInput('discount_value'), 'decimal');
        $orderInformation->discount_reason  = $this->getInput('discount_reason', '', 'STRING');
        $orderInformation->shipping         = $this->getInput('shipping', '', 'STRING');
        $orderInformation->access           = $this->formatToNumeric($this->getInput('access'));
        $orderInformation->ordering         = $this->formatToNumeric($this->getInput('ordering'));
        $orderInformation->shipping_address = null;
        $orderInformation->billing_address  = null;

        if (!empty($orderInformation->customer_id)) {
            $customerInfo                       = EasyStoreHelper::getCustomerById($orderInformation->customer_id);
            $orderInformation->shipping_address = $customerInfo->shipping_address ?? null;
            $orderInformation->billing_address  = $customerInfo->billing_address ?? null;

            $user = HelperEasyStoreHelper::getUserByCustomerId($orderInformation->customer_id);
            $orderInformation->customer_email   = $user->email ?? null;
        }

        $updateStatus = $model->update($orderInformation);

        if ($updateStatus) {
            $orderProducts = $this->getInput('products', [], 'ARRAY');
            $orderProducts = array_map(function ($product) {
                return \json_decode($product, true);
            }, $orderProducts);

            $model->storeMultipleOrderedProducts($orderProducts, $id);

            if (!is_null($orderInformation->shipping_address)) {
                $shippingAddress = json_decode($orderInformation->shipping_address);
                $country         = $shippingAddress->country;
                $state           = $shippingAddress->state;

                $settingsModel = new SettingsModel();
                $tax           = $this->getTaxRate($country, $state);

                $taxRate         = !empty($tax) ? $tax->product_tax_rate : 0;
                $isTaxOnShipping = !empty($tax) ? $tax->applyOnShipping : false;
                $saleTax         = 0.0;
                $subTotal        = 0.0;
                $calculatedPrice = [];
                foreach ($orderProducts as $product) {
                    $calculatedPrice[] = ($product['cart_item']['discounted_price'] > 0 ? $product['cart_item']['discounted_price'] * $product['cart_item']['quantity'] : $product['cart_item']['price'] * $product['cart_item']['quantity']);
                }

                if ($isTaxOnShipping) {
                    $shippingAmount = 0;
                    $shippingInfo   = !empty($orderInformation->shipping) && is_string($orderInformation->shipping) ? json_decode($orderInformation->shipping) : [];

                    if (!empty($shippingInfo) && !empty((float) $shippingInfo->rate)) {
                        $shippingAmount = (float) $shippingInfo->rate;
                    }

                    $subTotal = array_sum($calculatedPrice) + $shippingAmount;
                } else {
                    $subTotal = array_sum($calculatedPrice);
                }

                $saleTax  = ($subTotal * $taxRate) / 100;

                $orderInformation->sale_tax = $saleTax;

                $model->update($orderInformation);
            }

            if ($isActivated) {
                $model->addOrderActivity($id, 'order_created');
            }

            $response->status  = true;
            $response->message = Text::_("COM_EASYSTORE_ORDER_UPDATED");
            $response->id      = $id;
        } else {
            $response->status  = false;
            $response->message = Text::_("COM_EASYSTORE_FAILED_TO_UPDATE_ORDER");
        }

        $this->sendResponse($response);
    }

    /**
     * Update Order fulfilment status by PATCH request
     *
     * @return void
     */
    public function patchOrders()
    {
        $id           = $this->getInput('id', null, 'INT');
        $type         = $this->getInput('type', '', 'STRING');
        $value        = $this->getInput('value', '', 'STRING');
        $refundReason = $this->getInput('refund_reason', '', 'STRING');
        $acl          = AccessControl::create();

        if (empty($id)) {
            $message = Text::_('COM_EASYSTORE_ORDER_EMPTY_VALUE_ORDER_ID');
            $this->sendResponse($message, 422);
        }

        if (empty($type)) {
            $message = Text::_('COM_EASYSTORE_ORDER_EMPTY_TYPE');
            $this->sendResponse($message, 422);
        }

        if (empty($value)) {
            $message = Text::_('COM_EASYSTORE_ORDER_EMPTY_VALUE');
            $this->sendResponse($message, 422);
        }

        if (!$acl->canCreate()) {
            $this->sendResponse(['message' => Text::_("COM_EASYSTORE_PERMISSION_ERROR_MSG")], 403);
        }

        $model    = new OrderModel();
        $response = new stdClass();

        if (in_array($type, ['refunded', 'partially_refunded'], true)) {
            $value        = $this->formatToNumeric($value, 'decimal');
            $updateResult = $model->makeRefund($id, $value, $refundReason);

            if (!$updateResult->status) {
                $response->status  = false;
                $response->message = Text::_($updateResult->message);
                $response->id      = $id;
                $this->sendResponse($response, 500);
            }

            $response->status  = true;
            $response->message = Text::_("COM_EASYSTORE_ORDER_REFUNDED");
            $response->id      = $id;

            $this->sendResponse($response);
        } elseif ($type === 'comment') {
            $updateStatus = $model->addOrderActivity($id, $type, $value);
        } else {
            $updateStatus = $model->patchOrders($id, $type, $value);

            if (!empty($updateStatus) && $type === "payment_status" && $value === "paid") {
                $orm  = new EasyStoreDatabaseOrm();
                $item = $orm->get("#__easystore_order_product_map", 'order_id', $id)->loadObject();
                $data = (object) [
                    'product_id' => $item->product_id,
                    'sku_id'     => $item->variant_id,
                    'quantity'   => $item->quantity,
                ];

                /** @var CMSApplication $app */
                $app = Factory::getApplication();

                /** @var ProductModel $productModel */
                $productModel = $app->bootComponent('com_easystore')->getMVCFactory()->createModel('Product', 'Site');
                $productModel = $app->bootComponent('com_easystore')
                    ->getMVCFactory()->createModel('Product', 'Site');
                $productModel->deductFromProductOrSkuTable($data);

                $order        = $model->getOrderById($id);

                $customer = null;

                if (!empty($order) && isset($order->user)) {
                    $customer = $order->user;
                }
                
                $orderSummery  = OrderManager::createWith($order->id, $customer, $order->order_token)->getOrderItemWithCalculation();

                if (!empty($customer)){
                    $orderSummery->customer_email = $customer->email ?? null;
                }

                if (empty($orderSummery->customer_email)) {
                    return;
                }

                $this->sendPaymentSuccessEmail($orderSummery);
            }
        }

        if (in_array($type, ['payment_status', 'fulfilment', 'order_status', 'refunded', 'partially_refunded'], true)) {
            if (in_array($type, ['refunded', 'partially_refunded'], true)) {
                $value = $type;
            }

            $statusMap = [
                'paid'               => 'marked_as_paid',
                'unpaid'             => 'marked_as_unpaid',
                'refunded'           => 'marked_as_refunded',
                'partially_refunded' => 'marked_as_partially_refunded',
                'unfulfilled'        => 'marked_as_unfulfilled',
                'cancelled'          => 'marked_as_cancelled',
                'fulfilled'          => 'marked_as_fulfilled',
                'active'             => 'marked_as_active',
                'draft'              => 'marked_as_draft',
                'archived'           => 'marked_as_archived',
            ];

            $activityType = $statusMap[$value] ?? null;

            if (!is_null($activityType)) {
                $model->addOrderActivity($id, $activityType);
            }
        }

        if ($updateStatus) {
            $response->status  = true;
            $response->message = Text::_("COM_EASYSTORE_ORDER_UPDATED");
            $response->id      = $id;
        } else {
            $response->status  = false;
            $response->message = Text::_("COM_EASYSTORE_FAILED_TO_UPDATE_ORDER");
        }

        $this->sendResponse($response);
    }

    /**
     * Delete Order by Ids
     *
     * @return void
     */
    public function deleteOrders()
    {
        $ids = $this->getInput('ids', '', 'STRING');
        $ids = !empty($ids) ? explode(',', $ids) : [];

        $response = new stdClass();

        if (empty($ids)) {
            $message = Text::_('COM_EASYSTORE_ORDER_EMPTY_VALUE_ORDER_ID');
            $this->sendResponse($message, 422);
        }

        $acl = AccessControl::create();

        if (!$acl->canDelete()) {
            $this->sendResponse(['message' => Text::_("COM_EASYSTORE_PERMISSION_ERROR_MSG")], 403);
        }

        $model  = new OrderModel();
        $result = $model->deleteOrder($ids);

        if (!$result) {
            $response->status  = false;
            $response->message = Text::_("COM_EASYSTORE_ORDER_FAILED_TO_DELETE_ORDER");
            $this->sendResponse($response);
        }

        $response->status  = true;
        $response->message = Text::_("COM_EASYSTORE_ORDER_DELETE_ORDER_SUCCESS");
        $this->sendResponse($response);
    }

    /**
     * Function for add/update order tracking information
     *
     * @param int $id
     * @return void
     */
    public function addUpdateOrderTracking(int $id)
    {
        if (empty($id)) {
            $message = Text::_('COM_EASYSTORE_ORDER_EMPTY_VALUE_ORDER_ID');
            $this->sendResponse($message, 422);
        }

        $acl           = AccessControl::create();
        $hasPermission = $acl->canCreate() || $acl->canEdit() || $acl->setContext('order')->canEditOwn($id);

        if (!$hasPermission) {
            $this->sendResponse(['message' => Text::_("COM_EASYSTORE_PERMISSION_ERROR_MSG")], 403);
        }

        $data                                      = new stdClass();
        $data->id                                  = $id;
        $data->shipping_carrier                    = $this->getInput('shipping_carrier', '', 'STRING');
        $data->tracking_url                        = $this->getInput('tracking_url', '', 'STRING');
        $data->is_send_shipping_confirmation_email = $this->getInput('send_shipping_email', 1);

        $model        = new OrderModel();
        $updateStatus = $model->addUpdateOrderTracking($data);

        $response = new stdClass();

        if ($updateStatus) {
            $response->status  = true;
            $response->message = Text::_("COM_EASYSTORE_ORDER_TRACKING_UPDATED");
        } else {
            $response->status  = false;
            $response->message = Text::_("COM_EASYSTORE_FAILED_TO_UPDATE_ORDER_TRACKING");
        }

        $this->sendResponse($response);
    }

    /**
     * Function to duplicate a Order by Id
     *
     * @return void
     */
    public function createDuplicateOrder()
    {
        $id = $this->getInput('id', 0, 'INT');

        $response = new stdClass();

        if (empty($id)) {
            $message = Text::_('COM_EASYSTORE_ORDER_EMPTY_VALUE_ORDER_ID');
            $this->sendResponse($message, 422);
        }

        $model  = new OrderModel();
        $result = $model->duplicateOrder($id);

        if (!$result) {
            $response->status  = false;
            $response->message = Text::_("COM_EASYSTORE_ORDER_FAILED_TO_DUPLICATE");
            $this->sendResponse($response);
        }

        $response->status  = true;
        $response->message = Text::_("COM_EASYSTORE_ORDER_DUPLICATE_ORDER_SUCCESS");
        $this->sendResponse($response);
    }
}
