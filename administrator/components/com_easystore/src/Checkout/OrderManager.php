<?php

/**
 * @package     EasyStore.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Checkout;

use Exception;
use Throwable;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\Path;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use QuickPay\API\Exceptions\GenericException;
use JoomShaper\Component\EasyStore\Site\Traits\ProductMedia;
use JoomShaper\Component\EasyStore\Administrator\Supports\Shop;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseMapHelper;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper as EasyStoreHelperSite;

class OrderManager
{
    use ProductMedia;

    /**
     * The order ID by which we will manage the order.
     *
     * @var int
     */
    private $orderId = null;

    /**
     * The customer object.
     *
     * @var object|null
     */
    private $customer = null;

    /**
     * The order token for the guest users.
     *
     * @var string|null
     */
    private $token = null;

    /**
     * The order item or order object.
     *
     * @var object
     */
    private $item = null;

    /**
     * Is legacy order. This is required to check if the order is coming from version < 1.3.0
     *
     * @var boolean
     */
    private $isLegacy = false;

    /**
     * With currency keys. The system will search for these keys and
     * if found then add a suffix `_with_currency` and convert the value with currency.
     *
     * @var array
     */
    private $withCurrencyKeys = [
        'sub_total',
        'discounted_sub_total',
        'taxable_amount',
        'total',
        'shipping_cost',
        'legacy_coupon_discount',
        'total_refunded_amount',
        'coupon_discount',
        'net_payment',
        'refundable_amount',
        'shipping_tax',
        'sales_tax',
    ];

    /**
     * The private constructor. This class could not be instantiated from outside but by using createWith() method.
     *
     * @param int $orderId
     * @param int|null $customer
     * @param string|null $token
     *
     * @since 1.3.0
     */
    private function __construct($orderId, $customer = null, $token = null)
    {
        $this->orderId = $orderId;
        $this->customer = $customer;
        $this->token = $token;

        $this->prepareOrderItem();
        $this->checkForLegacyOrder();
        $this->processLegacyOrder();
    }

    /**
     * Setter method for setting the order products from the outside.
     *
     * @param array $products
     * @return void
     */
    public function setProducts(array $products)
    {
        if (is_null($this->item)) {
            throw new Exception('Order is not initiated yet.');
        }

        $this->item->products = $products;
    }

    /**
     * The instantiation method for creating the order manager instance.
     *
     * @param int $orderId
     * @param int|null $customer
     * @param string|null $token
     *
     * @return self
     */
    public static function createWith($orderId, $customer = null, $token = null)
    {
        return new self($orderId, $customer, $token);
    }

    /**
     * Get the order object item.
     *
     * @return object
     * @since 1.3.0
     */
    public function getOrderItem()
    {
        return $this->processOrderData($this->item);
    }

    /**
     * Get the order item with the calculated values like subtotal, total, tax etc.
     *
     * @return object
     * @since 1.3.0
     */
    public function getOrderItemWithCalculation()
    {
        $item = $this->getOrderItem();
        $item->sub_total = $this->calculateSubtotal();
        $item->discounted_sub_total = $this->calculateDiscountedSubtotal();
        $item->taxable_amount = $this->calculateTax();
        $item->coupon_discount = $this->calculateCouponDiscount();
        $item->total = $this->calculateTotal();
        $item->shipping_cost = $this->calculateShippingCost();
        $item->user = $this->isGuestOrder()
            ? $this->createGuestUser()
            : $this->createRegisteredUser();
        $item->is_legacy_order = $this->isLegacyOrder();
        $item->legacy_coupon_discount = $this->getLegacyCouponDiscount();
        $item->net_payment = $this->calculateNetPayment();
        $item->refundable_amount = $this->calculateRefundableAmount();
        $item->is_shipping_charge_refundable = false;
        $item->total_weight = $this->calculateTotalWeight(); // @todo Need to update this for Unit
        $item->total_weight_with_unit = SettingsHelper::getWeightWithUnit($item->total_weight);
        $item->shipping_tax = $this->calculateShippingTax();
        $item->sales_tax = $item->taxable_amount - $item->shipping_tax;

        if (in_array($item->payment_method, ['banktransfer', 'cheque', 'custom'])) {
            $item->payment_instructions_info = EasyStoreHelper::getManualPaymentInfo($item->payment_method);
        }

        Shop::formatWithCurrency($item, $this->withCurrencyKeys);

        return $this->reformatCartItem($item);
    }

    /**
     * Reformat the order products's cart items after the calculation.
     * This method is for updating any post calculation updates of the order items.
     *
     * @param object $item The order object.
     * @return object
     * @since 1.3.0
     */
    public function reformatCartItem($item)
    {
        if (empty($item->products)) {
            return $item;
        }

        $item->products = array_map(function ($product) {
            $product->cart_item->final_price = Shop::formatWithCurrency(
                $product->cart_item->final_price,
                [
                    'unit_product_price',
                    'total_product_price',
                    'unit_discount_value',
                    'total_discount_value',
                    'unit_discounted_price',
                    'total_discounted_price'
                ]
            );

            if (!isset($product->cart_item->taxable_amount)) {
                $product->cart_item->taxable_amount = 0;
            }

            $product->cart_item->taxable_amount_with_currency = EasyStoreHelper::formatCurrency($product->cart_item->taxable_amount);

            $product->cart_item->tax_rate = (float) $product->cart_item->tax_rate;
            $taxRate = $product->cart_item->tax_rate ?? 0;
            $product->cart_item->final_price = Shop::addTaxablePrices($product->cart_item->final_price, $taxRate);

            return $product;
        }, $item->products);

        return $item;
    }

    /**
     * Check if the order is placed by the guest or logged in user.
     *
     * @return boolean
     * @since 1.3.0
     */
    public function isGuestOrder()
    {
        return (bool) ($this->item->is_guest_order ?? 0);
    }

    /**
     * Check the order status. If the order item object not found or for the registered user's order
     * if the customer_id is not found then, throw an exception.
     *
     * @return void
     *
     * @throws GenericException
     * @since 1.3.0
     */
    public function checkOrderStatus()
    {
        $item = $this->getOrderItem();

        if (empty($item) || (!$this->isGuestOrder() && empty($item->customer_id))) {
            throw new GenericException(Text::_("COM_EASYSTORE_ORDER_NOT_FOUND"), 404);
        }
    }

    /**
     * Create the data for the guest user.
     *
     * @return object
     * @since 1.3.0
     */
    public function createGuestUser()
    {
        $item = $this->getOrderItem();

        return (object) [
            'name' => '',
            'email' => $item->customer_email,
            'is_billing_and_shipping_address_same' => false,
            'shipping_address' => $this->generateAddress($item->shipping_address),
            'billing_address' => $this->generateAddress($item->billing_address)
        ];
    }

    /**
     * Create the user object for the registered user.
     *
     * @return object|null
     * @since 1.3.0
     */
    public function createRegisteredUser()
    {
        $item = $this->getOrderItem();

        if (empty($this->customer)) {
            return null;
        }

        $customer = clone $this->customer;
        $customer->shipping_address = $this->generateAddress($item->shipping_address);
        $customer->billing_address = $this->generateAddress($item->billing_address);

        return $customer;
    }

    /**
     * Check if the order is a legacy order or not.
     * If the cart item not found inside the product item that means that order is created
     * Before version 1.3.0.
     *
     * @return self
     * @since 1.3.0
     */
    public function checkForLegacyOrder()
    {
        $products = $this->getProducts();

        foreach ($products as $product) {
            if (empty($product->cart_item)) {
                $this->isLegacy = true;
                break;
            }
        }

        return $this;
    }

    /**
     * Check if the order is a legacy order.
     *
     * @return boolean
     * @since 1.3.0
     */
    public function isLegacyOrder()
    {
        return $this->isLegacy;
    }

    /**
     * Process the legacy order and convert the old structured order into the new shape.
     *
     * @return void
     * @since 1.3.0
     */
    public function processLegacyOrder()
    {
        if (!$this->isLegacyOrder()) {
            return;
        }

        $order = $this->getOrderItem();
        $products = $this->getProducts();

        $isCouponApplicable = !empty($order->coupon_code);
        $appliedCoupon = null;

        if ($isCouponApplicable) {
            $appliedCoupon = (object) [
                'code' => $order->coupon_code,
                'discount_type' => $order->coupon_type,
                'discount_amount' => $order->coupon_amount,
            ];
        }

        $totalProducts = count((array) $products);

        foreach ($products as $index => &$product) {
            $couponDiscount = $isCouponApplicable
                ? ($appliedCoupon->discount_type === 'amount'
                    ? $appliedCoupon->discount_amount / $totalProducts
                    : $product->price * $appliedCoupon->discount_amount / 100)
                : 0;

                $total_product_price = (float) bcdiv(($product->price * 100) * $product->quantity, '100', 2);
                $total_discount_value = (float) bcdiv(($couponDiscount * 100) * $product->quantity, '100', 2);

            $cartItem = (object)[
                'is_coupon_applicable' => $isCouponApplicable,
                'applied_coupon' => $appliedCoupon,
                'final_price' => (object) [
                    'unit_product_price' => $product->price,
                    'total_product_price' => $total_product_price,
                    'unit_discount_value' => $couponDiscount,
                    'total_discount_value' => $total_discount_value,
                    'unit_discounted_price' => $isCouponApplicable ? $product->price - $couponDiscount : 0,
                    'total_discounted_price' => $isCouponApplicable ? $total_product_price - $total_discount_value : 0,
                ],
                /**
                 * For the legacy order the tax was calculated for overall cart and stored into sale_tax column
                 * But in the new system the tax is calculating for individual products so set the sale_tax to
                 * the first product and set 0 tax for the other products so that we could calculate the taxable_amount
                 * using the same formula.
                 */
                'taxable_amount' => $index === 0 ? $order->sale_tax : 0,
                'is_product_taxable' => 0,
                'tax_rate' => 0,
                'is_taxable' => 0,
            ];

            $cartItem->final_price = Shop::formatWithCurrency($cartItem->final_price);
            $product->cart_item = $cartItem;
        }

        unset($product);

        $this->item->products = $products;
    }

    /**
     * Get the coupon discount for the legacy order.
     *
     * @return float
     * @since 1.3.0
     */
    public function getLegacyCouponDiscount()
    {
        if (!$this->isLegacyOrder()) {
            return 0;
        }

        $order = $this->getOrderItem();

        if (!isset($order->coupon_type)) {
            return 0;
        }

        $subtotal = $this->calculateSubtotal();

        if ($order->coupon_type === 'amount') {
            return $order->coupon_amount;
        } else {
            return ($subtotal * $order->coupon_amount) / 100;
        }
    }

    /**
     * Get the order history or activities.
     *
     * @return array
     * @since 1.3.0
     */
    public function getOrderHistory()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select($db->quoteName(['o.id', 'o.activity_type', 'o.activity_value',  'o.created']))
            ->from($db->quoteName('#__easystore_order_activities', 'o'))
            ->where($db->quoteName('o.order_id') . ' = ' . $this->orderId)
            ->order($db->quoteName('o.created') . 'DESC');

        $db->setQuery($query);

        try {
            $result   = $db->loadObjectList();
        } catch (Throwable $error) {
            return [];
        }

        $newArray = [];

        foreach ($result as $data) {
            $newArray[] = (object) [
                'activity_type' => EasyStoreDatabaseMapHelper::getOrderActivities($data->activity_type),
                'activity_value' => $data->activity_value,
                'type' => $data->activity_type,
                'created' => HTMLHelper::_('date', $data->created, 'DATE_FORMAT_LC2'),
            ];
        }

        return $newArray;
    }

    /**
     * Check if free shipping coupon code is applied or not.
     * In the order record, check if applied coupon category is `free_shipping`
     * And if the shipping rate is zero after applying the coupon code that means
     * The free shipping type coupon is applied and the coupon was valid.
     *
     * @return boolean
     * @since 3.0.1
     */
    public function isFreeShippingCouponApplied()
    {
        $order = $this->getOrder();
        $shipping = !empty($order->shipping) && is_string($order->shipping) ? json_decode($order->shipping) : $order->shipping;

        return $order->coupon_category === 'free_shipping' && (float) $shipping->rate === 0.0;
    }

    /**
     * Calculate the product subtotal excluding the coupon discount.
     *
     * @return float
     * @since 3.0.1
     */
    private function calculateProductSubtotal()
    {
        $products = $this->getProducts();

        if (empty($products)) {
            return 0;
        }

        return array_reduce($products, function ($result, $item) {
            return $result + ($item->cart_item->final_price->total_product_price ?: 0);
        }, 0);
    }

    /**
     * Calculate the subtotal of the order.
     * This is the total price of the products without any coupon calculation
     * and any tax inclusion. But the product sale price is already been deducted.
     *
     * @return float
     * @since 1.3.0
     */
    public function calculateSubtotal()
    {
        if ($this->isFreeShippingCouponApplied()) {
            return $this->calculateProductSubtotal();
        }

        return $this->isCouponApplied()
            ? $this->calculateDiscountedSubtotal()
            : $this->calculateProductSubtotal();
    }

    /**
     * Calculate the discounted subtotal amount.
     * This is the total product price after applying coupon code.
     *
     * @return float
     * @since 1.3.0
     */
    public function calculateDiscountedSubtotal()
    {
        $products = $this->getProducts();

        if (empty($products)) {
            return 0;
        }
        
        return array_reduce($products, function ($result, $item) {
            if ($item->cart_item->is_coupon_applicable) {
                return $result + ($item->cart_item->final_price->total_discounted_price ?: 0);
            }
            return $result + ($item->cart_item->final_price->total_product_price ?: 0);
        }, 0);
    }

    /**
     * Calculate the discount amount by the applied coupon code.
     *
     * @return float
     * @since 1.3.0
     */
    public function calculateCouponDiscount()
    {
        $products = $this->getProducts();

        if (empty($products)) {
            return 0;
        }

        return array_reduce($products, function ($result, $item) {
            return $result + ($item->cart_item->final_price->total_discount_value ?: 0);
        }, 0);
    }

    /**
     * Calculate the overall tax of the product including the product tax and the shipping tax.
     *
     * @return float
     * @since 1.3.0
     */
    public function calculateTax()
    {
        $order = $this->getOrderItem();
        $products = $this->getProducts();

        $shippingTax = floatval($order->shipping_tax ?? 0);

        $productTax = array_reduce($products, function ($result, $item) {
            return $result + ($item->cart_item->taxable_amount ?: 0);
        }, 0);

        // If the price inclusive to the product price and the shipping rate then no need to include the
        // shipping tax to the total tax calculation.
        if ($order->is_tax_included_in_price) {
            $shippingTax = 0;
        }

        return $productTax + $shippingTax;
    }

    /**
     * Calculate the shipping tax
     *
     * @return float
     */
    public function calculateShippingTax()
    {
        $order = $this->getOrderItem();

        return floatval($order->shipping_tax ?? 0);
    }

    /**
     * Calculate the shipping cost amount.
     *
     * @return float
     * @since 1.3.0
     */
    public function calculateShippingCost()
    {
        $item = $this->getOrderItem();

        if (empty($item->shipping)) {
            return 0;
        }

        $shipping = is_string($item->shipping) ? json_decode($item->shipping) : $item->shipping;

        if (empty($shipping)) {
            return 0;
        }

        if (empty($shipping->offerFreeShipping)) {
            return $shipping->rate ?? 0;
        }

        $offerOnAmount = floatval($shipping->offerOnAmount ?? 0);
        $subtotal = $this->calculateSubtotal();

        if ($subtotal > $offerOnAmount) {
            return 0;
        }

        return floatval($shipping->rate ?? 0);
    }

    /**
     * Calculate the order overall total amount.
     * This amount includes the product price (after coupon discount), shipping cost, and tax exemption.
     *
     * @return float
     * @since 1.3.0
     */
    public function calculateTotal()
    {
        $order = $this->getOrder();
        $subtotal = $this->calculateSubtotal();

        $shippingCost = $this->calculateShippingCost();
        $tax = $this->calculateTax();

        // If the price already included into the price then we don't need to include the tax with the total.
        if ($order->is_tax_included_in_price) {
            $tax = 0;
        }

        return $subtotal + $shippingCost + $tax;
    }

    /**
     * Calculate the net payment after applying any refund.
     *
     * @return float
     * @since 1.3.0
     */
    public function calculateNetPayment()
    {
        return $this->calculateTotal() - $this->calculateRefundedAmount();
    }

    /**
     * Calculate the total weight of the order products.
     *
     * @return float
     * @since 1.3.0
     */
    public function calculateTotalWeight()
    {
        $products = $this->getProducts();

        $unit = SettingsHelper::getSettings()->get('products.standardUnits.weight', 'kg');

        if (empty($products)) {
            return 0;
        }

        return array_reduce($products, function ($total, $product) use ($unit) {
            $weight = 0;
            
            // Get product weight and convert if needed
            if (!empty($product->weight)) {
                $weight = (float) $product->weight;
                
                // Convert weight if product uses different unit
                if (!empty($product->unit) && $product->unit !== $unit) {
                    $weight = EasyStoreHelperSite::convertWeight($weight, $product->unit, $unit);
                }
            }

            // Calculate total including quantity and round to 2 decimals
            return bcadd($total, ($weight * (int) $product->quantity), 2); 
        }, 0);
    }

    /**
     * Calculate the total refunded amount.
     *
     * @return float
     * @since 1.3.0
     */
    public function calculateRefundedAmount()
    {
        $orm = new EasyStoreDatabaseOrm();
        return $orm->setColumns([
            $orm->aggregateQuoteName('SUM', 'refund_value'),
        ])->useRawColumns(true)
            ->hasMany($this->orderId, '#__easystore_order_refunds', 'order_id')
            ->loadResult() ?? 0;
    }

    /**
     * Calculate the refundable amount.
     * @ps: The current calculation excludes the shipping charge from refunding.
     *
     * @return float
     * @since 1.3.0
     */
    public function calculateRefundableAmount()
    {
        // @todo: This will come from settings, but for now we are excluding shipping charge from refunding.
        $isShippingChargeRefundable = false;
        $totalPaid = $this->calculateTotal();
        $totalRefunded = $this->calculateRefundedAmount();
        $shippingCost = $this->calculateShippingCost();

        $refundableAmount = $totalPaid - $totalRefunded;

        if (!$isShippingChargeRefundable) {
            $refundableAmount -= $shippingCost;
        }

        return $refundableAmount;
    }

    /**
     * Check if the coupon is applied to the order.
     *
     * @return boolean
     * @since 1.3.0
     */
    public function isCouponApplied()
    {
        $item = $this->getOrderItem();

        return !empty($item->coupon_code);
    }

    /**
     * Get the refunds of the order.
     *
     * @return array
     * @since 1.3.0
     */
    private function getOrderRefunds()
    {
        $orm = new EasyStoreDatabaseOrm();
        $refunds = $orm->setColumns(['id', 'order_id', 'refund_value', 'refund_reason'])
            ->hasMany($this->orderId, '#__easystore_order_refunds', 'order_id')
            ->loadObjectList() ?? [];

        if (empty($refunds)) {
            return [];
        }

        return array_map(function ($refund) {
            Shop::formatWithCurrency($refund, ['refund_value']);
            return $refund;
        }, $refunds);
    }

    /**
     * Process the overall order data, like getting the refunds, formatting the shipping and billing addresses,
     * get the order activities etc.
     *
     * @param object $order
     *
     * @return object
     * @since 1.3.0
     */
    private function processOrderData($order)
    {
        $order->refunds = $this->getOrderRefunds();
        $order->total_refunded_amount = $this->calculateRefundedAmount();

        if (!empty($order->shipping_address) && is_string($order->shipping_address)) {
            $order->shipping_address = json_decode($order->shipping_address);
        }

        if (!empty($order->billing_address) && is_string($order->billing_address)) {
            $order->billing_address = json_decode($order->billing_address);
        }

        if (!empty($order->shipping) && is_string($order->shipping)) {
            $order->shipping = json_decode($order->shipping);
        }

        $order->activities = $this->getOrderHistory();

        return $order;
    }

    /**
     * Get the products of the order.
     *
     * @return array
     * @since 1.3.0
     */
    private function getProducts()
    {
        $products = $this->getOrderItem()->products ?? [];

        if (empty($products)) {
            return $products;
        }

        return array_map(function ($item) {

            $weight = isset($item->weight) && is_numeric($item->weight) ? (string)$item->weight : '0';
            $item->weight = $weight;

            $unit = $item->has_variants 
                ? (isset($item->variant_data->unit) ? $item->variant_data->unit : (isset($item->unit) ? $item->unit : null)) 
                : (isset($item->unit) ? $item->unit : null);
            $item->unit = $unit;

            $quantity = isset($item->cart_item) && isset($item->cart_item->quantity) && is_numeric($item->cart_item->quantity) ? (string) $item->cart_item->quantity : '0';

            $item->weight_total = bcmul($weight, $quantity, 2);
            $item->weight_total_with_unit = SettingsHelper::getWeightWithUnit($item->weight_total, $unit);
            $item->weight_with_unit = SettingsHelper::getWeightWithUnit($item->weight, $unit);
            return $item;
        }, $products);
    }

    /**
     * Prepare the order item for managing the calculations.
     *
     * @return void
     *
     * @throws Exception If the order does not find.
     * @since 1.3.0
     */
    private function prepareOrderItem()
    {
        $order = $this->getOrder();

        if (empty($order)) {
            throw new Exception(sprintf("Order with ID %s not found.", $this->orderId), 404);
        }

        if (!empty($order->shipping) && is_string($order->shipping)) {
            $order->shipping = json_decode($order->shipping);
        }

        $order->products = $this->getOrderProducts();

        $this->item = $order;
    }

    /**
     * Get the order from the database by the order ID.
     *
     * @return object
     *
     * @throws Throwable
     * @since 1.3.0
     */
    private function getOrder()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select(['o.*'])
            ->from($db->quoteName('#__easystore_orders', 'o'))
            ->where($db->quoteName('o.id') . ' = ' . $this->orderId);

            if ($this->token) {
                $query->where($db->quoteName('o.order_token') . ' = ' . $db->quote($this->token));
            }

        $db->setQuery($query);


        try {
            return $db->loadObject();
        } catch (Throwable $error) {
            throw $error;
        }
    }

    /**
     * Get the order products from the database using the order id
     * The products should contains the variant information and other related data.
     * This is required for admin order edit.
     *
     * @return array
     *
     * @throws Throwable
     * @since 1.3.0
     */
    private function getOrderProducts()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select($db->quoteName(['p.id', 'p.title', 'p.has_variants']))
            ->from($db->quoteName('#__easystore_products', 'p'));

        $query->select($db->quoteName(['ord_pro_map.quantity', 'ord_pro_map.discount_type', 'ord_pro_map.discount_value', 'ord_pro_map.discount_reason', 'ord_pro_map.price', 'ord_pro_map.variant_id', 'ord_pro_map.cart_item']))
            ->join('LEFT', $db->quoteName('#__easystore_order_product_map', 'ord_pro_map'), $db->quoteName('ord_pro_map.product_id') . ' = ' . $db->quoteName('p.id'))
            ->where($db->quoteName('ord_pro_map.order_id') . ' = ' . $this->orderId);

        $db->setQuery($query);

        $orm = new EasyStoreDatabaseOrm();

        try {
            $products = $db->loadObjectList();

            foreach ($products as &$product) {
                $product->media = $this->getMedia($product->id);

                if (!empty($product->cart_item) && is_string($product->cart_item)) {
                    $product->cart_item = json_decode($product->cart_item);
                }

                if (!empty($product->variant_id)) {
                    $query->clear();

                    $query->select($db->quoteName(['prod_sku.combination_name', 'prod_sku.combination_value', 'prod_sku.price', 'prod_sku.inventory_status', 'prod_sku.inventory_amount', 'prod_sku.sku', 'prod_sku.weight', 'prod_sku.unit']))
                        ->from($db->quoteName('#__easystore_product_skus', 'prod_sku'))
                        ->where($db->quoteName('prod_sku.id') . ' = ' . $product->variant_id)
                        ->where($db->quoteName('prod_sku.product_id') . ' = ' . $product->id);

                    $db->setQuery($query);
                    $variantData = $db->loadObject();

                    if (!empty($variantData)) {
                        $product->variant_data                      = $variantData;
                        $product->variant_data->price_with_currency = EasyStoreHelper::formatCurrency($variantData->price);
                        $product->variant_data->weight_with_unit    = !empty($variantData->weight) ? SettingsHelper::getWeightWithUnit($variantData->weight, $variantData->unit) : '';

                        $product->variant_data->options = EasyStoreHelperSite::detectProductOptionFromCombination(
                            EasyStoreHelperSite::getProductOptionsById($product->id),
                            $variantData->combination_value
                        );
                    }
                }

                $combinationName            = $orm->hasOne($product->variant_id, '#__easystore_product_skus', 'id')->loadObject();
                $product->combination_name = !empty($combinationName) ? $combinationName->combination_name : '';
                $product->image = $orm->setColumns(['src'])
                    ->hasOne($product->id, '#__easystore_media', 'product_id')
                    ->whereInReference($orm->quoteName('is_featured') . ' = 1')
                    ->loadResult();

                $mainProduct = $orm->setColumns(['title', 'catid', 'weight', 'sku'])
                    ->hasOne($product->id, '#__easystore_products', 'id')
                    ->loadObject();

                $product->title        = $mainProduct->title;
                $product->catid        = $mainProduct->catid;
                $product->weight       = $mainProduct->weight;
                $product->sku          = $mainProduct->sku;
                $product->options      = [];

                if (!empty($product->variant_id)) {
                    $variant = $orm->setColumns(['combination_name', 'combination_value', 'image_id', 'sku', 'weight'])
                        ->hasOne($product->variant_id, '#__easystore_product_skus', 'id')
                        ->loadObject();

                    if (!empty($variant)) {
                        if (!empty($variant->image_id)) {
                            $product->image = $orm->setColumns(['src'])
                            ->hasOne($variant->image_id, '#__easystore_media', 'id')
                            ->loadResult();
                        }

                        $product->weight       = $variant->weight ?? '';
                        $product->sku          = $variant->sku ?? '';
                        $product->variant_name = $variant->combination_name ?? '';
                        $product->combination_name = $variant->combination_name ?? '';

                        $product->options = EasyStoreHelperSite::detectProductOptionFromCombination(
                            EasyStoreHelperSite::getProductOptionsById($product->id),
                            $variant->combination_value
                        );
                    }
                }

                if (!empty($product->image)) {
                    $product->image = Uri::root(true) . '/' . Path::clean($product->image);
                }
            }

            unset($product);

            return $products;
        } catch (Throwable $error) {
            throw $error;
        }
    }

    /**
     * Structure the address object.
     *
     * @param mixed $address
     *
     * @return object
     * @since 1.3.0
     */
    private function generateAddress($address)
    {
        if (empty($address)) {
            return [];
        }

        $address          = is_string($address) ? json_decode($address) : $address;
        $countryState     = EasyStoreHelperSite::getCountryStateFromJson($address->country, $address->state);
        $address->country_code = $address->country;
        $address->country = $countryState->country;
        $address->state   = $countryState->state;

        return $address;
    }
}
