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
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Administrator\Concerns\HasRelationship;
use JoomShaper\Component\EasyStore\Administrator\Concerns\Taxable;
use JoomShaper\Component\EasyStore\Administrator\Helper\CartHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Supports\Shop;
use JoomShaper\Component\EasyStore\Site\Helper\ArrayHelper;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper as HelperEasyStoreHelper;
use JoomShaper\Component\EasyStore\Site\Model\CartModel;
use JoomShaper\Component\EasyStore\Site\Model\CouponModel;
use JoomShaper\Component\EasyStore\Site\Model\ProductsModel;
use JoomShaper\Component\EasyStore\Site\Model\SettingsModel;

class CartManager
{
    use Taxable;
    use HasRelationship;

    /**
     * The product items added to the cart.
     *
     * @var array
     * @since 1.3.0
     */
    private $items = [];

    /**
     * The country numeric code
     *
     * @var string|null
     * @since 1.3.0
     */
    private $country = null;

    /**
     * The country state ID
     *
     * @var string|null
     * @since 1.3.0
     */
    private $state = null;

    /**
     * The zip code of the shipping address.
     *
     * @var string|null
     * @since 1.6.0
     */
    private $zipCode = null;

    /**
     * The city of the shipping address.
     *
     * @var string|null
     * @since 1.6.0
     */
    private $city = null;

    /**
     * The coupon code applied to the cart/checkout
     *
     * @var string|null
     * @since 1.3.0
     */
    private $couponCode = null;

    /**
     * The primary key of the #__easystore__users table.
     * Note that: this is not the Joomla user ID.
     *
     * @var int|null
     * @since 1.3.0
     */
    private $customerId = null;

    /**
     * The shipping UUID.
     *
     * @var string|null
     * @since 1.3.0
     */
    private $shippingId = null;

    /**
     * Check if the cart initiated successfully and ready for calculation.
     *
     * @var boolean
     * @since 1.3.0
     */
    private $isReady = false;


    /**
     * The Cart manager constructor method.
     * This is a local constructor and could only be instantiated via createWith() method
     *
     * @param array         $items      The product items.
     * @param string|null   $couponCode The coupon code if applied.
     * @param int|null      $customerId The customer ID.
     * @param string|null   $country    The shipping country numeric code
     * @param string|null   $state      The shipping state id.
     * @param string|null   $shippingId The shipping method's UUID.
     *
     * @since 1.3.0
     */
    private function __construct(array $items, $couponCode, $customerId, $country, $state, $city, $zipCode, $shippingId)
    {
        $this->items = $items;
        $this->couponCode = $couponCode;
        $this->country = $country;
        $this->state = $state;
        $this->city = $city;
        $this->zipCode = $zipCode;
        $this->customerId = $customerId;
        $this->shippingId = $shippingId;

        $this->prepareCartItems();
    }

    /**
     * Create the instance of the cart manager with the required data.
     *
     * @param array         $items      The product items.
     * @param string|null   $couponCode The coupon code if applied.
     * @param int|null      $customerId The customer ID.
     * @param string|null   $country    The shipping country numeric code
     * @param string|null   $state      The shipping state id.
     * @param string|null   $shippingId The shipping method's UUID.
     *
     * @return self
     * @since 1.3.0
     */
    public static function createWith(array $items, $couponCode, $customerId, $country, $state, $city, $zipCode, $shippingId)
    {
        return new self($items, $couponCode, $customerId, $country, $state, $city, $zipCode, $shippingId);
    }

    /**
     * Prepare the cart items and make ready for calculation.
     *
     * @return self
     * @since 1.3.0
     */
    public function prepareCartItems()
    {
        $this->items = $this->makeReadyCartItems($this->items);

        if (Shop::isCouponEnabled()) {
            $model = new CouponModel();

            // Don't change the sequence of calling these two methods. The order matters.
            $this->items =  $model->checkIfCouponIsApplicable(
                $this->items,
                $this->couponCode,
                $this->customerId
            );

            $this->items =  $model->updateCartItemsPriceAfterApplyingCoupon(
                $this->items,
                $this->couponCode,
                $this->country
            );
        }

        $this->items = $this->formatFinalPricesWithCurrency($this->items);
        $this->applyTax();
        $this->items = $this->postProcessingItems($this->items);

        // Set the isReady flag true, that is the cart manager is ready for calculations.
        $this->isReady = true;

        return $this;
    }

    /**
     * Get the cart product items.
     *
     * @return array
     * @since 1.3.0
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Get the shipping method from the shipping country and state.
     *
     * @return object|null
     * @since 1.3.0
     */
    public function getShippingMethod()
    {
        $model = new SettingsModel();
        $couponModel = new CouponModel();
        // $methods = $model->getShipping($this->country, $this->state);
        $methods = [];
        /** @var CartModel $cartModel */
        $cartModel = new CartModel();
        $shipping_methods = $cartModel->getShippingMethods();

        if (!empty($shipping_methods) && is_string($shipping_methods)) {
            $methods = json_decode($shipping_methods);
        }

        $shipping = null;

        if (!empty($this->shippingId) && !empty($methods)) {
            $methods = is_array($methods) ? $methods : [$methods];
            $method = ArrayHelper::find(function ($item) {
                return $item->uuid === $this->shippingId;
            }, $methods);

            $shipping = $method;
        } else {
            $result = is_array($methods) ? reset($methods) : $methods;
            $shipping = !empty($result) ? $result : null;
        }

        if (empty($shipping)) {
            return null;
        }

        $isFreeShippingApplied = $couponModel->isFreeShippingCouponApplied(
            $this->getItems(),
            $this->couponCode,
            $this->country
        );

        if ($isFreeShippingApplied) {
            $shipping->rate = 0;
            $shipping->name = Text::_('COM_EASYSTORE_APP_COUPON_TYPE_FREE_SHIPPING');
        }

        if (!empty($shipping->offerFreeShipping)) {
            $offerAmount = floatval($shipping->offerOnAmount ?? 0);
            $subtotal = $this->calculateSubtotal();

            if ($subtotal > $offerAmount) {
                $shipping->rate = 0;
                $shipping->name = Text::_('COM_EASYSTORE_APP_COUPON_TYPE_FREE_SHIPPING');
            }
        }

        $shipping->rate_with_currency = EasyStoreHelper::formatCurrency($shipping->rate);

        return $shipping;
    }

    public function getShippingCarriers($country, $state, $city, $zipCode)
    {
        $address = new \stdClass();
        $address->state = $state;
        $address->city = $city;
        $address->postcode = $zipCode;
        $address->country_code = HelperEasyStoreHelper::getCountryIsoNames($country)->iso2;

        $event = AbstractEvent::create('onEasyStoreGetShippingMethods', ['subject' => (object) ['shipping_address' => $address]]);
        $dispatcher = Factory::getApplication()->getDispatcher();
        $dispatcher->dispatch('onEasyStoreGetShippingMethods', $event);

        $shippingMethods = $event->getArgument('shippingMethods');

        return $shippingMethods;
    }

    /**
     * Calculate the product subtotal from the product unit price times quantity.
     *
     * @return float
     *
     * @throws Exception
     * @since 3.0.1
     */
    private function calculateProductSubtotal()
    {
        $this->checkReadyStatus();

        return array_reduce($this->items, function ($result, $item) {
            return $result + $item->final_price->total_product_price;
        }, 0);
    }

    /**
     * Calculate the subtotal of the product items without applying any coupon discount.
     *
     * @return float
     *
     * @throws Exception If the system is not ready yet.
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
     * Calculate the discounted subtotal after applying any coupon.
     *
     * @return float
     *
     * @throws Exception If the system is not ready yet.
     * @since 1.3.0
     */
    public function calculateDiscountedSubtotal()
    {
        $this->checkReadyStatus();

        return array_reduce($this->items, function ($result, $item) {
            if ($item->is_coupon_applicable) {
                return $result + $item->final_price->total_discounted_price;
            }
            return $result + $item->final_price->total_product_price;
        }, 0);
    }

    /**
     * Calculate the shipping cost.
     *
     * @return float
     * @since 1.3.0
     */
    public function calculateShippingCost()
    {
        $method = $this->getShippingMethod();

        if (empty($method) || empty($method->rate)) {
            return 0;
        }

        return floatval($method->rate ?? 0);
    }

    /**
     * Calculate the total coupon discount amount.
     * This is the total discount value.
     *
     * @return float
     * @since 1.3.0
     */
    public function calculateCouponDiscount()
    {
        if (empty($this->items)) {
            return 0;
        }

        return array_reduce($this->items, function ($result, $item) {
            return $result + ($item->final_price->total_discount_value ?: 0);
        }, 0);
    }

    /**
     * Calculate the shipping tax if it is enabled.
     *
     * @return float
     * @since 1.3.0
     */
    public function calculateShippingTax()
    {
        if (!Shop::isShippingTaxEnabled()) {
            return 0;
        }

        $taxRate = $this->getShippingTaxRate($this->country, $this->state);
        $shippingCost = $this->calculateShippingCost();

        return Shop::isTaxEnabled()
            ? Shop::calculateTaxableAmount($shippingCost, $taxRate)
            : Shop::calculateTaxableAmountForProductsExcludingTax($shippingCost, $taxRate);
    }

    /**
     * Calculate the total tax applied to the cart.
     * The total tax is including the product tax and the shipping tax.
     *
     * @return float
     *
     * @throws Exception If it is not ready for calculation.
     * @since 1.3.0
     */
    public function calculateTax()
    {
        $this->checkReadyStatus();

        $productTax = $this->calculateTotalProductTax();
        $shippingTax = $this->calculateShippingTax();

        if (!Shop::isTaxEnabled()) {
            $shippingTax = 0;
        }

        return $productTax + $shippingTax;
    }

    /**
     * Calculate the shipping tax rate by the country code and the state id.
     *
     * @return float
     * @since 1.3.0
     */
    public function calculateShippingTaxRate()
    {
        return $this->getShippingTaxRate($this->country, $this->state);
    }

    /**
     * Calculate the total product weight
     *
     * @return float
     *
     * @throws Exception
     * @since 1.3.0
     */
    public function calculateTotalWeight()
    {
        $this->checkReadyStatus();
        $unit = SettingsHelper::getSettings()->get('products.standardUnits.weight', 'kg');

        return array_reduce($this->items, function ($result, $item) use ($unit) {

            if ($item->unit !== $unit && !empty($item->unit)) {
                $item->weight = HelperEasyStoreHelper::convertWeight((float) $item->weight, $item->unit, $unit);
            }

            $weight = $item->weight ?: 0;
            
            return bcadd($result, ($weight * $item->quantity), 2);
        }, 0);
    }

    /**
     * Calculate the payable total amount including the shipping cost, tax and deducting the coupon discount.
     *
     * @return float
     * @since 1.3.0
     */
    public function calculateTotal()
    {
        $subtotal = $this->calculateSubtotal();
        $shippingCost = $this->calculateShippingCost();
        $tax = $this->calculateTax();

        if (!Shop::isTaxEnabled()) {
            $tax = 0;
        }

        return $subtotal + $shippingCost + $tax;
    }

    /**
     * Check if the free shipping coupon code is applied or not.
     *
     * @return boolean
     * @since 3.0.1
     */
    public function isFreeShippingCouponApplied()
    {
        $couponModel  = new CouponModel();
        return $couponModel->isFreeShippingCouponApplied(
            $this->items,
            $this->couponCode,
            $this->country
        );
    }

    /**
     * Check if the coupon discount is applied or not.
     *
     * @return boolean
     * @since 1.3.0
     */
    public function isCouponApplied()
    {
        // If free shipping coupon applied then marked the coupon application false as free shipping coupon does not applied on the product discount
        if ($this->isFreeShippingCouponApplied()) {
            return false;
        }

        return !empty($this->couponCode);
    }

    /**
     * Calculate the total product taxable amount.
     *
     * @return float
     */
    private function calculateTotalProductTax()
    {
        return array_reduce($this->items, function ($result, $item) {
            return $result + $item->taxable_amount;
        }, 0);
    }

    /**
     * Check for the ready status.
     *
     * @return void
     *
     * @throws Exception
     * @since 1.3.0
     */
    private function checkReadyStatus()
    {
        if (!$this->isReady) {
            throw new Exception(sprintf('The cart is not yet ready for calculation. Please run prepareCartItems function first.'));
        }
    }

    /**
     * Make the cart items ready for calculations.
     *
     * @param array $items
     *
     * @return array
     * @since 1.3.0
     */
    private function makeReadyCartItems($items)
    {
        if (empty($items)) {
            return [];
        }

        return array_map(function ($item) {
            $item->price            = (float) bcdiv($this->getItemPrice($item) * 100 , '100', 2);
            $item->discount         = $this->getDiscountObject($item);
            $item->item_price       = $item->price;
            $item->inventory_amount = $this->getInventoryAmount($item);
            $item->inventory_status = $this->getInventoryStatus($item);
            $item->weight           = $this->getItemWeight($item);
            $item->unit             = $this->getItemUnit($item);
            $item->weight_with_unit = SettingsHelper::getWeightWithUnit($item->weight, $item->unit);
            $item = $this->applyDiscountForProductSale($item);
            $item = $this->initiateProductItem($item);
            $item->is_coupon_applicable = false;
            $item->is_product_taxable = $this->getProductTaxStatus($item);

            return $item;
        }, $items);
    }

    /**
     * Traverse all the cart item's final price object and add a suffix `_with_currency` with all the price property.
     *
     * @param array $items
     *
     * @return array
     * @since 1.3.0
     */
    private function formatFinalPricesWithCurrency($items)
    {
        if (empty($items)) {
            return [];
        }

        return array_map(function ($item) {
            $item->final_price = CartHelper::withCurrency($item->final_price);
            return $item;
        }, $items);
    }

    /**
     * Apply tax to the cart items.
     *
     * @return self
     * @since 1.3.0
     */
    private function applyTax()
    {
        $this->items = array_map(function ($item) {
            $taxRate = $this->getTaxRate($this->country, $this->state, $item);
            $item->tax_rate = $taxRate->product_tax_rate;

            $itemPrice = ($this->isCouponApplied() && $item->is_coupon_applicable)
                ? $item->final_price->total_discounted_price
                : $item->final_price->total_product_price;

            $item->taxable_amount = Shop::isTaxEnabled()
                ? Shop::calculateTaxableAmount($itemPrice, $item->tax_rate)
                : Shop::calculateTaxableAmountForProductsExcludingTax($itemPrice, $item->tax_rate);
            $item->taxable_amount = round((float) $item->taxable_amount, 2);
            $item->taxable_amount_with_currency = EasyStoreHelper::formatCurrency($item->taxable_amount);
            return $item;
        }, $this->items);

        return $this;
    }

    /**
     * Get the tax status for a product.
     * This will check if there is a variant of the product.
     * If variant exists and the variant is taxable then the product is taxable.
     * If variant is not exists and the product is taxable then it is taxable.
     *
     * @param object $item
     *
     * @return bool
     * @since 1.3.0
     */
    private function getProductTaxStatus($item)
    {
        $productIsTaxable = $item->is_taxable ?? 1;
        $variantIsTaxable = $item->is_taxable_variant ?? 1;

        return $item->has_variants ? $variantIsTaxable : $productIsTaxable;
    }

    /**
     * Get the cart item price.
     * If the cart item has variant then the item price would be the variant's price
     * Otherwise the regular price.
     *
     * @param object $item
     *
     * @return float
     * @since 1.3.0
     */
    private function getItemPrice($item)
    {
        $price = $item->regular_price;

        if ($item->has_variants) {
            $price = $item->price;
        }

        return $price ?? 0;
    }

    /**
     * Get the inventory amount of the given cart item.
     *
     * @param   object $item
     *
     * @return  float
     * @since   1.3.0
     */
    private function getInventoryAmount($item)
    {
        $amount = $item->available_quantity ?? 0;

        if ($item->has_variants) {
            $amount = $item->sku_inventory_amount ?? 0;
        }

        return $amount;
    }

    /**
     * Get inventory status for the cart item.
     *
     * @param [type] $item
     * @return void
     */
    private function getInventoryStatus($item)
    {
        $inventoryStatus = $item->product_inventory_status ?? 0;

        if ($item->has_variants) {
            $inventoryStatus = $item->sku_inventory_status ?? 0;
        }

        return $inventoryStatus;
    }

    /**
     * Get the cart item weight from the product or variant conditionally.
     *
     * @param object $item The cart item.
     *
     * @return float
     * @since 1.3.0
     */
    private function getItemWeight($item)
    {
        $weight = $item->weight ?? 0;

        if ($item->has_variants) {
            $weight = $item->sku_weight ?? 0;
        }

        return $weight;
    }

    /**
     * Get the cart item weight unit from the product or variant conditionally.
     *
     * @param object $item The cart item.
     *
     * @return float
     * @since 1.3.0
     */
    private function getItemUnit($item)
    {
        $unit = $item->unit ?? 0;

        if ($item->has_variants) {
            $unit = $item->sku_unit ?? 0;
        }

        return $unit;
    }

    /**
     * Apply the product sale discount at the beginning that is applied while creating a product.
     * Other discount like, coupon discount, admin discount will be applied later upon this discounted price.
     *
     * @param object $item The cart product item.
     *
     * @return object
     * @since 1.3.0
     */
    private function applyDiscountForProductSale($item)
    {
        $item->discounted_price = 0;
        // Check if the item has a sale and a valid discount amount
        if ($item->has_sale && isset($item->discount->amount) && $item->discount->amount > 0) {
            // Calculate the discounted price using a helper function
            $item->discounted_price = EasyStoreHelper::calculateDiscountedPrice(
                $item->discount->type,
                $item->discount->amount,
                $item->price
            );

            // Update the item price to the discounted price
            $item->item_price = $item->discounted_price;
        }

        return $item;
    }

    /**
     * Initiate the product item for beginning the calculation.
     *
     * @param object $item The product item object.
     * @return object
     */
    private function initiateProductItem($item)
    {
        $item->final_price = CartHelper::defaultPrice($item);
        $item->applied_coupon = (object) [
            'code' => null,
            'discount_type' => null,
            'discount_amount' => null,
        ];

        return $item;
    }

    /**
     * Get the discount object.
     *
     * @param object $item The cart product item.
     *
     * @return object
     * @since 1.3.0
     */
    private function getDiscountObject($item)
    {
        return  (object) [
            'type'   => $item->discount_type ?? 'percent',
            'amount' => $item->discount_value ?? 0,
        ];
    }

    /**
     * Post processing the cart items after all the calculation done.
     *
     * @param array $items
     *
     * @return array
     * @since 1.3.0
     */
    private function postProcessingItems($items)
    {
        if (empty($items)) {
            return [];
        }

        return array_map(function ($item) {
            $taxRate = $item->tax_rate ?? 0;
            $item->final_price = Shop::addTaxablePrices($item->final_price, $taxRate);
            return $item;
        }, $items);
    }

    /**
     * Get the product details from the product ID.
     *
     * @return mixed
     * @since 1.5.0
     */
    public function getCrossellProducts()
    {
        // Get unique product IDs from cart items
        $productIds = $this->getUniqueProductIds();

        // Get all related product IDs
        $relatedIds = $this->getRelatedProductIds($productIds);

        if (empty($relatedIds)) {
            return null;
        }

        // Fetch and return related products
        return $this->fetchRelatedProducts($relatedIds);
    }

    /**
     * Extract unique product IDs from cart items
     * 
     * @return array
     * 
     * @since 1.5.0
     */
    private function getUniqueProductIds(): array
    {
        return array_unique(array_map(function ($item) {
            return $item->product_id;
        }, $this->items));
    }

    /**
     * Get related product IDs for given product IDs
     * 
     * @param array $productIds
     * @return array
     * 
     * @since 1.5.0
     */
    private function getRelatedProductIds(array $productIds): array
    {
        $relatedIds = [];
        
        foreach ($productIds as $productId) {
            $relatedIds = array_merge(
                $relatedIds,
                $this->getRelatedRecords(
                    $productId,
                    'product',
                    'crossell',
                    '#__easystore_product_crossells'
                )
            );
        }

        return array_unique($relatedIds);
    }

    /**
     * Fetch related products using product IDs
     * 
     * @param array $relatedIds
     * @return array
     * 
     * @since 1.5.0
     */
    private function fetchRelatedProducts(array $relatedIds): array
    {
        $model = new ProductsModel();
        $model->setState('easystore.pks', $relatedIds);
        $result = $model->getItems();

        return $result->products ?? [];
    }

    /**
     * Get the product cross-sells from the product ID.
     *
     * @param int $productId The product ID.
     *
     * @return array
     * @since 1.5.0
     */
    private function getProducts(int $productId)
    {
        $products = $this->getRelatedRecords($productId, 'product', 'crossell', '#__easystore_product_crossells');

        return $this->getProductDetails($products);
    }
}
