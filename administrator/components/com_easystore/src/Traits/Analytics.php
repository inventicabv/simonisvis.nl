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

use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Administrator\Model\OrderModel;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;

trait Analytics
{
    protected $hasDuplicateShipping = false;

    public function analytics()
    {
        $requestMethod = $this->getInputMethod();

        $this->checkNotAllowedMethods(['POST', 'PUT', 'DELETE', 'PATCH'], $requestMethod);

        if ($requestMethod === 'GET') {
            $this->getAnalytics();
        }
    }

    /**
     * API for getting the app-config data.
     *
     * @return void
     */
    protected function getAnalytics()
    {
        $dateFrom = $this->getInput('from', '', 'STRING');
        $dateTo   = $this->getInput('to', '', 'STRING');

        if (empty($dateFrom) || empty($dateTo)) {
            $this->sendResponse(Text::_('COM_EASYSTORE_BAD_REQUEST'), 400);
        }

        $from = date("Y-m-d 00:00:00", strtotime($dateFrom));
        $to   = date("Y-m-d 23:59:59", strtotime($dateTo));

        $orderModel = new OrderModel();
        $orders     = $orderModel->getOrdersByDate($from, $to);

        $result                 = new \stdClass();
        $result->grossSale      = 0;
        $result->netSale        = 0;
        $result->totalOrders    = 0;
        $result->productSold    = 0;
        $result->avgOrderValue  = 0;
        $result->cancelledOrder = 0;
        $result->returnAmount   = 0;
        $result->totalTax       = 0;
        $topSellingItems        = [];
        $topSellingCategories   = [];
        $chartDataByDate        = [];

        foreach ($orders as $order) {
            if ($order->fulfilment == 'cancelled') {
                $result->cancelledOrder += 1;
                continue;
            } else {
                $result->totalOrders += 1;
            }

            $refund          = $this->checkIfOrderIsReturned($order->id);
            $creationDate    = date('Y-m-d', strtotime($order->creation_date));
            $subTotal        = (float) $order->sub_total;
            $netSale         = EasyStoreHelper::calculateDiscountedPrice($order->discount->type, $order->discount->amount, $subTotal);
            $taxes           = $order->sale_tax;
            $productQuantity = 0;

            if (!$refund) {
                foreach ($order->products as $product) {
                    $result->productSold += (int) $product->quantity;
                    $productQuantity += $product->quantity;

                    // For Top Selling Items
                    if (array_key_exists($product->product_id, $topSellingItems)) {
                        $topSellingItems[$product->product_id]['quantity']  = $topSellingItems[$product->product_id]['quantity'] + (int) $product->quantity;
                        $topSellingItems[$product->product_id]['grossSale'] = $topSellingItems[$product->product_id]['grossSale'] + (float) $product->total;
                    } else {
                        $productName                           = EasyStoreDatabaseOrm::get('#__easystore_products', 'id', $product->product_id, 'title')->loadObject()->title;
                        $topSellingItems[$product->product_id] = [
                            'id'            => $product->product_id,
                            'name'          => $productName,
                            'quantity'      => (int) $product->quantity,
                            'grossSale'     => (float) $product->total,
                            'featuredImage' => $product->image ?? '',
                        ];
                    }

                    // For Top Selling Categories
                    $categoryId = $product->catid;

                    if (array_key_exists($categoryId, $topSellingCategories)) {
                        $topSellingCategories[$categoryId]['quantity']  = $topSellingCategories[$categoryId]['quantity'] + (int) $product->quantity;
                        $topSellingCategories[$categoryId]['grossSale'] = $topSellingCategories[$categoryId]['grossSale'] + (float) $product->total;
                    } else {
                        $categoryData                      = EasyStoreDatabaseOrm::get('#__easystore_categories', 'id', $categoryId, '*')->loadObject();
                        $categoryName                      = $categoryData->title;
                        $categoryImage                     = $categoryData->image;
                        $topSellingCategories[$categoryId] = [
                            'id'            => $categoryId,
                            'name'          => $categoryName,
                            'quantity'      => (int) $product->quantity,
                            'grossSale'     => (float) $product->total,
                            'featuredImage' => $categoryImage ?? '',
                        ];
                    }
                }
            } else {
                $result->returnAmount += $refund;

                // For Chart Data by Day
                if (array_key_exists($creationDate, $chartDataByDate)) {
                    $chartDataByDate[$creationDate]['refunds'] = $chartDataByDate[$creationDate]['refunds'] + (float) $refund;
                    $chartDataByDate[$creationDate]['taxes']   = $chartDataByDate[$creationDate]['taxes'] + $taxes;
                } else {
                    $chartDataByDate[$creationDate] = [
                        'grossSale' => 0.00,
                        'netSale'   => 0.00,
                        'taxes'     => $taxes,
                        'refunds'   => (float) $refund,
                        'quantity'  => 0,
                    ];
                }
            }

            $result->grossSale += $subTotal;
            $result->totalTax += $taxes;
            $result->netSale += $netSale;

            // For Chart Data by Day
            if (!$refund) {
                if (array_key_exists($creationDate, $chartDataByDate)) {
                    $chartDataByDate[$creationDate]['grossSale'] = $chartDataByDate[$creationDate]['grossSale'] + (float) $subTotal;
                    $chartDataByDate[$creationDate]['netSale']   = $chartDataByDate[$creationDate]['netSale'] + (float) $netSale;
                    $chartDataByDate[$creationDate]['taxes']     = $chartDataByDate[$creationDate]['taxes'] + (float) $taxes;
                    $chartDataByDate[$creationDate]['quantity']  = $chartDataByDate[$creationDate]['quantity'] + $productQuantity;
                } else {
                    $chartDataByDate[$creationDate] = [
                        'grossSale' => (float) $subTotal,
                        'netSale'   => (float) $netSale,
                        'taxes'     => (float) $taxes,
                        'refunds'   => 0.00,
                        'quantity'  => $productQuantity,
                    ];
                }
            }
        }

        ksort($chartDataByDate);
        $result->grossSale            = $result->grossSale - $result->returnAmount;
        $result->netSale              = $result->netSale - $result->returnAmount;
        $result->avgOrderValue        = $result->netSale / ($result->totalOrders == 0 ? 1 : $result->totalOrders);
        $topSellingItems              = array_values($topSellingItems);
        $result->topSellingItems      = $this->sortContent($topSellingItems);
        $topSellingCategories         = array_values($topSellingCategories);
        $result->topSellingCategories = $this->sortContent($topSellingCategories);
        $result->chartDataByDate      = $chartDataByDate;

        $this->sendResponse($result);
    }

    /**
     * Function to Sort Top Selling Contents and return limited data
     *
     * @param array $items
     * @param int $limit
     * @return array
     */
    private function sortContent($items, $limit = 5)
    {
        usort($items, function ($a, $b) {
            return $b['grossSale'] - $a['grossSale'];
        });

        return array_slice($items, 0, $limit); // To get top 5 products
    }

    /**
     * Function to ceheck if Order is refunded
     *
     * @param int $orderId
     * @return bool|float
     */
    private function checkIfOrderIsReturned($orderId)
    {
        $orm = new EasyStoreDatabaseOrm();

        $refundData = $orm->hasOne($orderId, '#__easystore_order_refunds', 'order_id')->loadObject();

        if (!empty($refundData)) {
            return (float) $refundData->refund_value;
        }

        return false;
    }
}
