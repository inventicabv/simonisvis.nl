<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\Database\DatabaseInterface;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper as EasyStoreAdminHelper;
use JoomShaper\Component\EasyStore\Administrator\Supports\Shop;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class CouponModel extends ItemModel
{
    /**
     * Model context string.
     *
     * @var        string
     */
    protected $_context = 'com_easystore.coupon';

    /**
     * Returns a message for display
     * @param int $pk Primary key of the "message item", currently unused
     * @return mixed Message object
     */
    public function getItem($pk = null)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('*')
            ->from($db->quoteName('#__easystore_coupons'))
            ->where($db->quoteName('id') . ' = ' . $pk);

        $db->setQuery($query);

        try {
            return $db->loadObject() ?? null;
        } catch (\Throwable $error) {
            throw $error;
        }
    }

    /**
     * Function to get coupon by code
     *
     * @param string $code
     * @return object|null
     */
    public function getCouponByCode(string $code)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('*')
            ->from($db->quoteName('#__easystore_coupons'))
            ->where('LOWER(' . $db->quoteName('code') . ') = ' . $db->quote(strtolower($code)))
            ->where($db->quoteName('published') . ' = 1');

        $db->setQuery($query);

        try {
            return $db->loadObject() ?? null;
        } catch (\Throwable $error) {
            throw $error;
        }
    }

    /**
     * Function to get the Product data related to coupon
     *
     * @param int $couponId
     * @return object
     */
    public function getProductCouponData($couponId)
    {
        $orm = new EasyStoreDatabaseOrm();

        $productCouponData = $orm->hasMany($couponId, '#__easystore_coupon_product_map', 'coupon_id')
            ->updateQuery(function ($query) use ($orm) {
                $query->where($orm->quoteName('buy_get_offer') . ' IS NULL');
            })->loadObjectList() ?? [];

        return $productCouponData;
    }

    /**
     * Function to get the Category data related to coupon
     *
     * @param int $couponId
     * @return object
     */
    public function getCategoryCouponData($couponId)
    {
        $orm = new EasyStoreDatabaseOrm();

        $categoryCouponData = $orm->hasMany($couponId, '#__easystore_coupon_category_map', 'coupon_id')
            ->updateQuery(function ($query) use ($orm) {
                $query->where($orm->quoteName('buy_get_offer') . ' IS NULL');
            })->loadObjectList() ?? [];

        return $categoryCouponData;
    }

    /**
     * Function to get the Product data related to coupon
     *
     * @param int $couponId
     * @return array
     */
    public function getProductCouponList($couponId)
    {
        $orm = new EasyStoreDatabaseOrm();

        $productCouponData = $orm->hasMany($couponId, '#__easystore_coupon_product_map', 'coupon_id')
            ->updateQuery(function ($query) use ($orm) {
                $query->where($orm->quoteName('buy_get_offer') . ' IS NULL');
            })->loadAssoc() ?? [];

        return $productCouponData;
    }

    /**
     * Function to get the Category data related to coupon
     *
     * @param int $couponId
     * @return array
     */
    public function getCategoryCouponList($couponId)
    {
        $orm = new EasyStoreDatabaseOrm();

        $categoryCouponData = $orm->hasMany($couponId, '#__easystore_coupon_category_map', 'coupon_id')
            ->updateQuery(function ($query) use ($orm) {
                $query->where($orm->quoteName('buy_get_offer') . ' IS NULL');
            })->loadAssoc() ?? [];

        return $categoryCouponData;
    }

    public function getCouponProducts($couponId)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select($db->quoteName('product_id'))
            ->from($db->quoteName('#__easystore_coupon_product_map'))
            ->where($db->quoteName('coupon_id') . ' = :couponId')
            ->where($db->quoteName('buy_get_offer') . ' IS NULL')
            ->bind(':couponId', $couponId);
        $db->setQuery($query);

        return $db->loadColumn() ?? [];
    }

    public function getCouponCategories($couponId)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select($db->quoteName('category_id'))
            ->from($db->quoteName('#__easystore_coupon_category_map'))
            ->where($db->quoteName('coupon_id') . ' = :couponId')
            ->where($db->quoteName('buy_get_offer') . ' IS NULL')
            ->bind(':couponId', $couponId);
        $db->setQuery($query);

        return $db->loadColumn() ?? [];
    }

    /**
     * Function to apply coupon code
     *
     * @param int $cartId
     * @param object $couponData
     * @param string $countryId
     * @param string $shippingAmount
     * @return void
     */
    public function applyCouponCode($cartId, $couponData, $countryId = null, $shippingAmount = null)
    {
        $orm       = new EasyStoreDatabaseOrm();
        $cartModel = new CartModel();
        $cartData  = $cartModel->getItem();

        // Coupon expiry check
        if (!EasyStoreHelper::isCouponCodeValid($couponData->start_date, $couponData->end_date)) {
            throw new \Exception(Text::_('COM_EASYSTORE_CART_COUPON_CODE_EXPIRED'));
        }
        // Coupon usage limit check
        $couponUsageLimit = $this->isCouponUsageWithinLimit($couponData);

        if (!$couponUsageLimit) {
            throw new \Exception(Text::_('COM_EASYSTORE_CART_COUPON_LIMIT_EXCEEDED'));
        }
        // User coupon usage limit check
        if (!empty($cartData->customer_id)) {
            $userData             = EasyStoreAdminHelper::getUserByCustomerId($cartData->customer_id);
            $userCouponUsageLimit = $this->isUserCouponUsageWithinLimit($userData->email, $couponData);

            if (!$userCouponUsageLimit) {
                throw new \Exception(Text::_('COM_EASYSTORE_CART_USER_COUPON_LIMIT_EXCEEDED'));
            }
        }

        $couponData->product_coupon_map  = $this->getProductCouponData($couponData->id);
        $couponData->category_coupon_map = $this->getCategoryCouponData($couponData->id);

        // switch ($couponData->coupon_category) {
        //     case 'discount':
        //         $processCoupon = $this->processDiscountCoupon($cartData, $couponData);
        //         break;
        //     case 'free_shipping':
        //         $processCoupon = $this->processFreeShippingCoupon($cartData, $couponData, $countryId, $shippingAmount);
        //         break;
        //     case 'sale_price':
        //         $processCoupon = $this->processSalePriceCoupon($cartData, $couponData);
        //         break;
        //     default:
        //         $processCoupon = $this->processDiscountCoupon($cartData, $couponData);
        //         break;
        // }

        // if (!$processCoupon->status) {
        //     throw new \Exception($processCoupon->message);
        // }

        $data = (object) [
            'id'              => $cartId,
            'coupon_category' => $couponData->coupon_category,
            'coupon_code'     => $couponData->code,
            'coupon_type'     => $couponData->discount_type,
            'coupon_amount'   => $couponData->discount_value,
        ];

        try {
            $orm->update('#__easystore_cart', $data, 'id');
        } catch (\Throwable $error) {
            throw $error;
        }
    }

    public function removeCouponCode($cartId)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->update($db->quoteName('#__easystore_cart'))
            ->set([
                $db->quoteName('coupon_code') . ' = NULL',
                $db->quoteName('coupon_type') . ' = NULL',
                $db->quoteName('coupon_amount') . ' = 0',
            ])
            ->where($db->quoteName('id') . ' = ' . (int) $cartId);

        $db->setQuery($query);

        try {
            $db->execute();

            return true;
        } catch (\Throwable $error) {
            throw $error;
        }
    }

    /**
     * Function to calculate the Coupon discount amount based on cart data
     *
     * @param object $cartData
     * @return object
     */
    public function calculateCouponDiscount(object $cartData, string $countryId = null)
    {
        $couponData = $this->getCouponByCode($cartData->coupon_code);
        $orm        = new EasyStoreDatabaseOrm();
        $response   = new \stdClass();

        $couponData->user_coupon_map = $orm->hasMany($couponData->id, '#__easystore_user_coupon_usage', 'coupon_id')
            ->loadObjectList() ?? [];

        $couponUsageLimit = $this->isCouponUsageWithinLimit($couponData);

        if (!$couponUsageLimit) {
            $response->status  = false;
            $response->message = 'coupon_limit_exceeded';

            return $response;
        }

        // User coupon usage limit check
        if (!empty($cartData->customer_id)) {
            $userData             = EasyStoreAdminHelper::getUserByCustomerId($cartData->customer_id);
            $userCouponUsageLimit = $this->isUserCouponUsageWithinLimit($userData->email, $couponData);

            if (!$userCouponUsageLimit) {
                $response->status  = false;
                $response->message = 'user_coupon_limit_exceeded';

                return $response;
            }
        }

        $couponData->product_coupon_map  = $this->getProductCouponData($couponData->id);
        $couponData->category_coupon_map = $this->getCategoryCouponData($couponData->id);

        $discountAmount = 0;

        switch ($couponData->coupon_category) {
            case 'discount':
                $couponDiscount = $this->processDiscountCoupon($cartData, $couponData);
                break;
            case 'free_shipping':
                $couponDiscount = $this->processFreeShippingCoupon($cartData, $couponData, $countryId);
                break;
            case 'sale_price':
                $couponDiscount = $this->processSalePriceCoupon($cartData, $couponData);
                break;
        }

        if (!$couponDiscount->status) {
            $response->status  = false;
            $response->message = $couponDiscount->message;

            return $response;
        }

        if (!empty($couponDiscount->amount)) {
            $discountAmount = $couponDiscount->amount;
        }

        $response->status  = true;
        $response->message = 'coupon_success';
        $response->value   = $discountAmount;

        return $response;
    }

    /**
     * Function to calculate the Coupon discount amount based on cart data
     *
     * @param array $items
     * @param string $couponCode
     * @return object
     */
    public function updateCartItemsPriceAfterApplyingCoupon($items, $couponCode, string $countryId = null)
    {
        if (empty($couponCode)) {
            return $items;
        }

        $couponData = $this->getCouponByCode($couponCode);

        if (empty($couponData)) {
            return $items;
        }

        switch ($couponData->coupon_category) {
            case 'discount':
                return $this->applyCouponDiscount($items, $couponData);
            case 'free_shipping':
                return $this->rollbackIsCouponApplicableStatus($items);
            case 'sale_price':
                return $this->applySalePriceCouponDiscount($items, $couponData);
        }

        return $items;
    }

    public function checkIfCouponIsApplicable($items, $couponCode, $customerId)
    {
        if (empty($couponCode)) {
            return $items;
        }

        $couponData = $this->getCouponByCode($couponCode);

        if (empty($couponData)) {
            return $items; // Return early if no coupon data
        }

        $this->setUserCouponMap($couponData);

        $appliedCoupon = (object) [
            'code' => $couponData->code,
            'discount_type' => $couponData->discount_type,
            'discount_amount' => $couponData->discount_value,
        ];

        $couponUsageLimit     = $this->checkCouponUsageLimit($couponData);
        $userCouponUsageLimit = $this->checkUserCouponUsageLimit($customerId, $couponData);

        // Apply coupon rules to cart items only if any limits are valid
        if ($couponUsageLimit || $userCouponUsageLimit) {
            return $this->applyCouponToCartItems($items, $couponData, $appliedCoupon);
        }

        return $items;
    }



    public function setUserCouponMap(&$couponData)
    {
        if (empty($couponData) || empty($couponData->id)) {
            return; // Exit early if no coupon data is provided
        }

        $orm = new EasyStoreDatabaseOrm();

        // Load user coupon usage data related to this coupon
        $userCouponMap = $orm->hasMany($couponData->id, '#__easystore_user_coupon_usage', 'coupon_id')->loadObjectList();

        // Assign coupon usage data, ensuring it defaults to an empty array if none found
        $couponData->user_coupon_map = !empty($userCouponMap) ? $userCouponMap : [];
    }

    public function checkCouponUsageLimit($couponData)
    {
        return $this->isCouponUsageWithinLimit($couponData);
    }

    public function checkUserCouponUsageLimit($customerId, $couponData)
    {
        // Early exit if customer ID or coupon data is missing
        if (empty($customerId) || empty($couponData)) {
            return false;
        }

        // Get user data by customer ID
        $userData = EasyStoreAdminHelper::getUserByCustomerId($customerId);

        // Check if user data and email exist before proceeding
        if (empty($userData) || empty($userData->email)) {
            return false;
        }

        // Check if the user's coupon usage is within the allowed limit
        return $this->isUserCouponUsageWithinLimit($userData->email, $couponData);
    }

    public function applyCouponToCartItems($items, $couponData, $appliedCoupon)
    {
        switch ($couponData->applies_to) {
            case 'all_products':
                return $this->applyCouponToAllProducts($items, $appliedCoupon);
            case 'specific_products':
                $applicableProductList = $this->getCouponProducts($couponData->id);
                return $this->applyCouponToSpecificProducts($items, $applicableProductList, $appliedCoupon);
            case 'specific_categories':
                $applicableCategoryList = $this->getCouponCategories($couponData->id);
                return $this->applyCouponToSpecificCategories($items, $applicableCategoryList, $appliedCoupon);
        }
    }

    private function applyCouponToAllProducts($items, $appliedCoupon)
    {
        return array_map(function ($item) use ($appliedCoupon) {
            $item->is_coupon_applicable = true;
            $item->applied_coupon = $appliedCoupon;
            return $item;
        }, $items);
    }

    private function applyCouponToSpecificProducts($items, $applicableProductList, $appliedCoupon)
    {
        return array_map(function ($item) use ($applicableProductList, $appliedCoupon) {
            $item->is_coupon_applicable = in_array($item->product_id, $applicableProductList, true);
            $item->applied_coupon = $appliedCoupon;
            return $item;
        }, $items);
    }

    private function applyCouponToSpecificCategories($items, $applicableCategoryList, $appliedCoupon)
    {
        return array_map(function ($item) use ($applicableCategoryList, $appliedCoupon) {
            $item->is_coupon_applicable = in_array($item->catid, $applicableCategoryList, true);
            $item->applied_coupon = $appliedCoupon;
            return $item;
        }, $items);
    }
    /**
     * Checks if the coupon usage is within the limit
     *
     * @param object $couponData
     * @return bool
     */
    public function isCouponUsageWithinLimit(object $couponData)
    {
        if ($couponData->coupon_limit_status) {
            $orm = new EasyStoreDatabaseOrm();

            $userCouponUsage = $orm->hasMany($couponData->id, '#__easystore_user_coupon_usage', 'coupon_id')
                ->loadObjectList() ?? [];

            $couponUsage = 0;

            foreach ($userCouponUsage as $value) {
                $couponUsage += (float) $value->coupon_count;
            }

            if ($couponUsage >= $couponData->coupon_limit_value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if the User's coupon usage is within the limit
     *
     * @param string $email
     * @param object $couponData
     * @return bool
     */
    public function isUserCouponUsageWithinLimit(string $email, object $couponData)
    {
        if (empty($email)) {
            return false;
        }

        if ($couponData->usage_limit_status) {
            $orm = new EasyStoreDatabaseOrm();

            $userCouponUsage = $orm->hasMany($couponData->id, '#__easystore_user_coupon_usage', 'coupon_id')
                ->updateQuery(function ($query) use ($orm, $email) {
                    $query->where($orm->quoteName('email') . ' = ' . $orm->quote($email));
                })
                ->loadObjectList() ?? [];

            $couponUsage = 0;

            foreach ($userCouponUsage as $value) {
                $couponUsage += (float) $value->coupon_count;
            }

            if ($couponUsage >= $couponData->usage_limit_value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Function to calculate coupon amount for 'discount' coupon category
     *
     * @param object $orderData
     * @param object $couponData
     * @return object
     */
    public function processDiscountCoupon(object $orderData, object $couponData)
    {
        $discountAmount   = 0;
        $couponProducts   = [];
        $couponCategories = [];
        $cartQuantityMap  = [];
        $response         = new \stdClass();
        $orm              = new EasyStoreDatabaseOrm();

        foreach ($couponData->product_coupon_map as &$couponProduct) {
            $couponProducts[] = $couponProduct->product_id;

            // Get Coupon Product Sku data
            $skuIds = $orm->setColumns(['sku_id'])
                ->hasMany($couponProduct->product_id, '#__easystore_coupon_product_sku_map', 'product_id')
                ->updateQuery(function ($query) use ($orm, $couponData) {
                    $query->where($orm->quoteName('coupon_id') . ' = ' . $couponData->id);
                })
                ->loadColumn();

            $couponProduct->variant_ids = !empty($skuIds) ? $skuIds : [];
        }

        unset($couponProduct);

        foreach ($couponData->category_coupon_map as $couponCategory) {
            $couponCategories[] = $couponCategory->category_id;
        }

        foreach ($orderData->items as $product) {
            if ($couponData->applies_to === 'specific_products') {
                if (in_array($product->product_id, $couponProducts)) {
                    if ($product->has_variants) {
                        foreach ($couponData->product_coupon_map as $couponProduct) {
                            if ($couponProduct->product_id == $product->product_id) {
                                if (in_array($product->sku_id, $couponProduct->variant_ids)) {
                                    if (isset($cartQuantityMap[$product->product_id])) {
                                        $cartQuantityMap[$product->product_id] += $product->quantity;
                                    } else {
                                        $cartQuantityMap[$product->product_id] = $product->quantity;
                                    }
                                }
                                break;
                            }
                        }
                    } else {
                        if (isset($cartQuantityMap[$product->product_id])) {
                            $cartQuantityMap[$product->product_id] += $product->quantity;
                        } else {
                            $cartQuantityMap[$product->product_id] = $product->quantity;
                        }
                    }
                }
            } elseif ($couponData->applies_to === 'specific_categories') {
                if (in_array($product->catid, $couponCategories)) {
                    if (isset($cartQuantityMap[$product->product_id])) {
                        $cartQuantityMap[$product->product_id] += $product->quantity;
                    } else {
                        $cartQuantityMap[$product->product_id] = $product->quantity;
                    }
                }
            }
        }

        $calculableSubTotal = 0;
        $calculableQuantity = 0;
        $isCouponApplicable = false;

        if ($couponData->applies_to !== 'all_products') {
            foreach ($orderData->items as $product) {
                $totalPrice = $product->final_price->total_product_price ?? 0;
                $isProductValidForCoupon = false;
                if ($couponData->applies_to === 'specific_products') {
                    if (in_array($product->product_id, $couponProducts)) {
                        if ($product->has_variants) {
                            foreach ($couponData->product_coupon_map as $couponProduct) {
                                if ($couponProduct->product_id == $product->product_id) {
                                    if (in_array($product->sku_id, $couponProduct->variant_ids)) {
                                        $calculableSubTotal += $totalPrice;
                                        $isProductValidForCoupon = true;
                                        $isCouponApplicable      = true;
                                    }
                                    break;
                                }
                            }
                        } else {
                            $calculableSubTotal += $totalPrice;
                            $isProductValidForCoupon = true;
                            $isCouponApplicable      = true;
                        }
                    }
                } elseif ($couponData->applies_to === 'specific_categories') {
                    $categoryId = EasyStoreDatabaseOrm::get('#__easystore_products', 'id', $product->product_id, 'catid')->loadObject()->catid;
                    if (in_array($categoryId, $couponCategories)) {
                        $calculableSubTotal += $totalPrice;
                        $isProductValidForCoupon = true;
                        $isCouponApplicable      = true;
                    }
                }

                if ($isProductValidForCoupon) {
                    if (!empty($cartQuantityMap[$product->product_id])) {
                        $calculableQuantity += $product->quantity;
                    }

                    $productDiscountAmount = EasyStoreAdminHelper::calculateDiscountValue($couponData->discount_type, $couponData->discount_value, $totalPrice, $product->quantity);

                    $discountAmount = !empty($productDiscountAmount) ? $discountAmount + $productDiscountAmount : $discountAmount;
                }
            }
        } else {
            foreach ($orderData->items as $product) {
                $calculableQuantity += $product->quantity;
            }

            $discountAmount     = EasyStoreAdminHelper::calculateDiscountValue($couponData->discount_type, $couponData->discount_value, $orderData->sub_total, $calculableQuantity);
            $calculableSubTotal = (float) $orderData->sub_total;

            foreach ($orderData->items as $product) {
                $calculableQuantity += $product->quantity;
            }

            $isCouponApplicable = true;
        }

        if ($isCouponApplicable && $couponData->purchase_requirements === 'minimum_purchase') {
            if ((float) $couponData->purchase_requirements_value > $calculableSubTotal) {
                $response->status  = false;
                $response->message = Text::_('COM_EASYSTORE_CART_COUPON_FAILED_MINIMUM_PURCHASE_REQUIREMENT');

                return $response;
            }
        } elseif ($isCouponApplicable && $couponData->purchase_requirements === 'minimum_quantity') {
            if ((float) $couponData->purchase_requirements_value > $calculableQuantity) {
                $response->status  = false;
                $response->message = Text::_('COM_EASYSTORE_CART_COUPON_FAILED_MINIMUM_QUANTITY_REQUIREMENT');

                return $response;
            }
        }

        $response->status  = true;
        $response->message = 'success';
        $response->amount  = $discountAmount;

        return $response;
    }

    private function calculateFixedAmountDiscount($items)
    {
        $sum = array_reduce($items, function ($result, $current) {
            return $current->is_coupon_applicable ? $result + ($current->item_price * $current->quantity) : $result;
        }, 0);

        return array_map(function ($item) use ($sum) {
            if (!$item->is_coupon_applicable) {
                return $item;
            }

            $discountValue = ($item->item_price / $sum) * $item->applied_coupon->discount_amount;
            $item->final_price = (object) [
                'unit_product_price' => $item->item_price,
                'total_product_price' => $item->item_price * $item->quantity,
                'unit_discount_value' => $discountValue,
                'total_discount_value' => $discountValue * $item->quantity,
                'unit_discounted_price' => $item->item_price - $discountValue,
                'total_discounted_price' => ($item->item_price * $item->quantity) - ($discountValue * $item->quantity)
            ];

            return $item;
        }, $items);
    }

    private function calculatePercentageDiscount($items)
    {
        return array_map(function ($item) {
            if (!$item->is_coupon_applicable) {
                return $item;
            }

            $discountRate = floatval($item->applied_coupon->discount_amount / 100);
            $discountValue = Shop::isTaxEnabled()
                ? ($item->item_price * $item->applied_coupon->discount_amount) / 100
                : $item->item_price - ($item->item_price * (1 - $discountRate));

            $item->final_price = (object) [
                'unit_product_price' => $item->item_price,
                'total_product_price' => $item->item_price * $item->quantity,
                'unit_discount_value' => $discountValue,
                'total_discount_value' => $discountValue * $item->quantity,
                'unit_discounted_price' => $item->item_price - $discountValue,
                'total_discounted_price' => ($item->item_price * $item->quantity) - ($discountValue * $item->quantity)
            ];

            return $item;
        }, $items);
    }

    private function rollbackIsCouponApplicableStatus($items)
    {
        return array_map(function ($item) {
            $item->is_coupon_applicable = false;
            return $item;
        }, $items);
    }

    public function applySalePriceCouponDiscount($items, $couponData)
    {
        $totalQuantity  = array_sum(array_column($items, 'quantity'));
        $totalAmount = array_reduce($items, function ($result, $item) {
            return $result + ($item->item_price * $item->quantity);
        }, 0);

        if ($couponData->purchase_requirements === 'minimum_purchase') {
            if ((float) $couponData->purchase_requirements_value > $totalAmount) {
                return $this->rollbackIsCouponApplicableStatus($items);
            }
        }

        if ($couponData->purchase_requirements === 'minimum_quantity') {
            if ((int) $couponData->purchase_requirements_value > $totalQuantity) {
                return $this->rollbackIsCouponApplicableStatus($items);
            }
        }

        $discountValue = $couponData->sale_value ?? 0;

        return array_map(function ($item) use ($discountValue) {
            if (!$item->is_coupon_applicable) {
                return $item;
            }

            $discountedAmount = $item->item_price - $discountValue;

            // If the discounted amount is less than 0 then don't apply coupon to the product.
            if ($discountedAmount < 0) {
                $item->is_coupon_applicable = false;
            }

            // If the discounted value is more than the item price then skip including the discount
            $unitDiscountedPrice = $discountedAmount >= 0 ? $discountValue : $item->item_price;

            $item->final_price = (object) [
                'unit_product_price' => $item->item_price,
                'total_product_price' => $discountValue * $item->quantity,
                'unit_discount_value' => $discountedAmount,
                'total_discount_value' => $discountedAmount * $item->quantity,
                'unit_discounted_price' => $unitDiscountedPrice,
                'total_discounted_price' => ($unitDiscountedPrice * $item->quantity)
            ];

            return $item;
        }, $items);
    }

    public function applyCouponDiscount($items, $couponData)
    {
        $totalQuantity  = array_sum(array_column($items, 'quantity'));
        $totalAmount = array_reduce($items, function ($result, $item) {
            return $result + ($item->item_price * $item->quantity);
        }, 0);

        if ($couponData->purchase_requirements === 'minimum_purchase') {
            if ((float) $couponData->purchase_requirements_value > $totalAmount) {
                return $this->rollbackIsCouponApplicableStatus($items);
            }
        }

        if ($couponData->purchase_requirements === 'minimum_quantity') {
            if ((int) $couponData->purchase_requirements_value > $totalQuantity) {
                return $this->rollbackIsCouponApplicableStatus($items);
            }
        }

        return $couponData->discount_type === 'percent'
            ? $this->calculatePercentageDiscount($items)
            : $this->calculateFixedAmountDiscount($items);
    }

    public function isFreeShippingCouponApplied($items, $couponCode, $countryId)
    {
        if (empty($couponCode)) {
            return false;
        }

        $couponData = $this->getCouponByCode($couponCode);

        if (empty($couponData)) {
            return false;
        }

        if ($couponData->coupon_category !== 'free_shipping') {
            return false;
        }

        $totalQuantity  = array_sum(array_column($items, 'quantity'));
        $totalAmount = array_reduce($items, function ($result, $item) {
            return $result + ($item->item_price * $item->quantity);
        }, 0);

        if ($couponData->country_type !== 'all') {
            $countries = !empty($couponData->selected_countries) ? explode(',', $couponData->selected_countries) : [];

            if (!in_array($countryId, $countries, true)) {
                return false;
            }
        }

        if ($couponData->purchase_requirements === 'minimum_purchase') {
            if ((float) $couponData->purchase_requirements_value > $totalAmount) {
                return false;
            }
        }

        if ($couponData->purchase_requirements === 'minimum_quantity') {
            if ((int) $couponData->purchase_requirements_value > $totalQuantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * Function to calculate coupon amount for 'free_shipping' coupon category
     *
     * @param object $orderData
     * @param object $couponData
     * @param string $countryId
     * @param mixed $shippingAmount
     * @return object
     */
    public function processFreeShippingCoupon(object $orderData, object $couponData, string $countryId = null, $shippingAmount = null)
    {
        $shippingAmount     = isset($orderData->shipping_method->rate) ? (float) $orderData->shipping_method->rate : (float) $shippingAmount;
        $calculableSubTotal = (float) $orderData->sub_total;
        $calculableQuantity = 0;
        $discountAmount     = $shippingAmount;
        $applyDiscount      = false;
        $response           = new \stdClass();

        if ($couponData->country_type === 'all') {
            $applyDiscount = true;
        } else {
            $countries = explode(',', $couponData->selected_countries);

            if (in_array($countryId, $countries)) {
                $applyDiscount = true;
            }
        }

        foreach ($orderData->items as $product) {
            $calculableQuantity += $product->quantity;
        }

        if ($couponData->purchase_requirements === 'minimum_purchase') {
            if ((float) $couponData->purchase_requirements_value > $calculableSubTotal) {
                $response->status  = false;
                $response->message = Text::_('COM_EASYSTORE_CART_COUPON_FAILED_MINIMUM_PURCHASE_REQUIREMENT');

                return $response;
            }
        } elseif ($couponData->purchase_requirements === 'minimum_quantity') {
            if ((float) $couponData->purchase_requirements_value > $calculableQuantity) {
                $response->status  = false;
                $response->message = Text::_('COM_EASYSTORE_CART_COUPON_FAILED_MINIMUM_QUANTITY_REQUIREMENT');

                return $response;
            }
        }

        if (!$applyDiscount) {
            $response->status  = true;
            $response->message = 'success';
            $response->amount  = 0;

            return $response;
        }

        $response->status  = true;
        $response->message = 'success';
        $response->amount  = $discountAmount;

        return $response;
    }

    /**
     * Function to calculate coupon amount for 'sale_price' coupon category
     *
     * @param object $orderData
     * @param object $couponData
     * @return object
     */
    public function processSalePriceCoupon(object $orderData, object $couponData)
    {
        $discountAmount   = 0;
        $couponProducts   = [];
        $couponCategories = [];
        $cartQuantityMap  = [];
        $response         = new \stdClass();
        $orm              = new EasyStoreDatabaseOrm();

        foreach ($couponData->product_coupon_map as $couponProduct) {
            $couponProducts[] = $couponProduct->product_id;

            // Get Coupon Product Sku data
            $skuIds = $orm->setColumns(['sku_id'])
                ->hasMany($couponProduct->product_id, '#__easystore_coupon_product_sku_map', 'product_id')
                ->updateQuery(function ($query) use ($orm, $couponData) {
                    $query->where($orm->quoteName('coupon_id') . ' = ' . $couponData->id);
                })
                ->loadColumn();

            $couponProduct->variant_ids = !empty($skuIds) ? $skuIds : [];
        }

        unset($couponProduct);

        foreach ($couponData->category_coupon_map as $couponCategory) {
            $couponCategories[] = $couponCategory->category_id;
        }

        foreach ($orderData->items as $product) {
            if ($couponData->applies_to === 'specific_products') {
                if (in_array($product->product_id, $couponProducts)) {
                    if ($product->has_variants) {
                        foreach ($couponData->product_coupon_map as $couponProduct) {
                            if ($couponProduct->product_id == $product->product_id) {
                                if (in_array($product->sku_id, $couponProduct->variant_ids)) {
                                    if (isset($cartQuantityMap[$product->product_id])) {
                                        $cartQuantityMap[$product->product_id] += $product->quantity;
                                    } else {
                                        $cartQuantityMap[$product->product_id] = $product->quantity;
                                    }
                                }
                                break;
                            }
                        }
                    } else {
                        if (isset($cartQuantityMap[$product->product_id])) {
                            $cartQuantityMap[$product->product_id] += $product->quantity;
                        } else {
                            $cartQuantityMap[$product->product_id] = $product->quantity;
                        }
                    }
                }
            } elseif ($couponData->applies_to === 'specific_categories') {
                if (in_array($product->catid, $couponCategories)) {
                    if (isset($cartQuantityMap[$product->product_id])) {
                        $cartQuantityMap[$product->product_id] += $product->quantity;
                    } else {
                        $cartQuantityMap[$product->product_id] = $product->quantity;
                    }
                }
            }
        }

        $calculableSubTotal = 0;
        $calculableQuantity = 0;
        $isCouponApplicable = false;

        if ($couponData->applies_to !== 'all_products') {
            foreach ($orderData->items as $product) {
                if ($couponData->applies_to === 'specific_products') {
                    if (in_array($product->product_id, $couponProducts)) {
                        if ($product->has_variants) {
                            foreach ($couponData->product_coupon_map as $couponProduct) {
                                if ($couponProduct->product_id == $product->product_id) {
                                    if (in_array($product->sku_id, $couponProduct->variant_ids)) {
                                        $calculablePrice = empty($product->discounted_price) ? (float) $product->item_price : (float) $product->discounted_price;

                                        $calculableSubTotal += $calculablePrice;

                                        if ($calculablePrice > (float) $couponData->sale_value) {
                                            $discountedPrice = (float) $couponData->sale_value * $product->quantity;
                                            $discountAmount += $product->total - $discountedPrice;
                                            $isCouponApplicable = true;
                                        }
                                    }
                                    break;
                                }
                            }
                        } else {
                            $calculablePrice = empty($product->discounted_price) ? (float) $product->item_price : (float) $product->discounted_price;

                            $calculableSubTotal += $calculablePrice;

                            if ($calculablePrice > (float) $couponData->sale_value) {
                                $discountedPrice = (float) $couponData->sale_value * $product->quantity;
                                $discountAmount += $product->total - $discountedPrice;
                                $isCouponApplicable = true;
                            }
                        }
                    }
                } elseif ($couponData->applies_to === 'specific_categories') {
                    $categoryId = EasyStoreDatabaseOrm::get('#__easystore_products', 'id', $product->product_id, 'catid')->loadObject()->catid;

                    if (in_array($categoryId, $couponCategories)) {
                        $calculablePrice = empty($product->discounted_price) ? (float) $product->item_price : (float) $product->discounted_price;

                        $calculableSubTotal += $calculablePrice;

                        if ($calculablePrice > (float) $couponData->sale_value) {
                            $discountedPrice = (float) $couponData->sale_value * $product->quantity;
                            $discountAmount += $product->total - $discountedPrice;
                            $isCouponApplicable = true;
                        }
                    }
                }

                if (!empty($cartQuantityMap[$product->product_id])) {
                    $calculableQuantity += $product->quantity;
                }
            }
        } else {
            $calculableSubTotal = (float) $orderData->sub_total;

            foreach ($orderData->items as $product) {
                $calculablePrice = empty($product->discounted_price) ? (float) $product->item_price : (float) $product->discounted_price;

                if ($calculablePrice > (float) $couponData->sale_value) {
                    $discountedPrice = (float) $couponData->sale_value * $product->quantity;
                    $discountAmount += $product->total_price - $discountedPrice;
                }

                $calculableQuantity += $product->quantity;
            }
        }

        if ($isCouponApplicable && $couponData->purchase_requirements === 'minimum_purchase') {
            if ((float) $couponData->purchase_requirements_value > $calculableSubTotal) {
                $response->status  = false;
                $response->message = Text::_('COM_EASYSTORE_CART_COUPON_FAILED_MINIMUM_PURCHASE_REQUIREMENT');

                return $response;
            }
        } elseif ($isCouponApplicable && $couponData->purchase_requirements === 'minimum_quantity') {
            if ((float) $couponData->purchase_requirements_value > $calculableQuantity) {
                $response->status  = false;
                $response->message = Text::_('COM_EASYSTORE_CART_COUPON_FAILED_MINIMUM_QUANTITY_REQUIREMENT');

                return $response;
            }
        }

        $response->status  = true;
        $response->message = 'success';
        $response->amount  = $discountAmount;

        return $response;
    }

    /**
     * Calculate the amount a coupon will discount based on the provided order data and order summary.
     *
     * This function takes the order details and coupon information, performs various checks
     * (such as coupon validity, usage limits, and user-specific limits), and calculates the
     * discount amount based on the type of coupon (e.g., discount, free shipping, sale price, buy one get one free).
     *
     * @param  object $orderData    An object containing order information including coupon code and shipping address.
     * @param  object $orderSummary An object containing summary information about the order, such as shipping cost.
     * @param bool $fromCheckout   Set value to true if it is called from checkout page
     *
     * @return float|object         The amount of discount provided by the coupon. Returns 0.00 if the coupon is invalid
     * or an object containing coupon discount amount
     * @since  1.2.0
     */
    public function getCouponAmount($orderData, $orderSummary, bool $fromCheckout = false)
    {
        $couponData = $this->getCouponByCode($orderData->coupon_code);

        if (!is_null($couponData)) {
            if ($fromCheckout) {
                // Coupon expiry check
                if (!EasyStoreHelper::isCouponCodeValid($couponData->start_date, $couponData->end_date)) {
                    return 0.00;
                }

                // Coupon usage limit check
                $couponUsageLimit = $this->isCouponUsageWithinLimit($couponData);

                if (!$couponUsageLimit) {
                    return 0.00;
                }

                // User coupon usage limit check
                if (!$orderData->is_guest_order) {
                    $userCouponUsageLimit = $this->isUserCouponUsageWithinLimit($orderData->customer_email, $couponData);

                    if (!$userCouponUsageLimit) {
                        return 0.00;
                    }
                }
            }

            $countryId = !empty($orderData->shipping_address) && is_string($orderData->shipping_address) ? json_decode($orderData->shipping_address)->country : null;

            $couponData->product_coupon_map  = $this->getProductCouponData($couponData->id);
            $couponData->category_coupon_map = $this->getCategoryCouponData($couponData->id);

            switch ($couponData->coupon_category) {
                case 'discount':
                    $processCoupon = $this->processDiscountCoupon($orderSummary, $couponData);
                    break;
                case 'free_shipping':
                    $processCoupon = $this->processFreeShippingCoupon($orderSummary, $couponData, $countryId, $orderSummary->shipping_cost);
                    break;
                case 'sale_price':
                    $processCoupon = $this->processSalePriceCoupon($orderSummary, $couponData);
                    break;
                default:
                    $processCoupon = $this->processDiscountCoupon($orderSummary, $couponData);
                    break;
            }

            if (!empty($processCoupon->amount)) {
                $processCoupon->amount = (float) number_format($processCoupon->amount, 2, '.', '');
            }

            return $processCoupon;
        }

        return 0.00;
    }

    /**
     * Updates the usage count of a coupon for a given user.
     *
     * This function retrieves the coupon by its code and increments the usage count
     * for the specified user email. If the user has used the coupon before, it updates
     * the existing record; otherwise, it creates a new record with the updated usage count.
     *
     * @param stdClass $usageData An object containing the coupon usage data.
     *                            Expected properties:
     *                            - user_id: the user id if available.
     *                            - coupon_code: The code of the coupon being used.
     *                            - email: The email of the user using the coupon.
     *
     * @since  1.2.0
     * @return void
     */
    public function updateCouponUsageCount($usageData)
    {
        $coupon = $this->getCouponByCode($usageData->coupon_code);

        $email    = $usageData->email;
        $couponId = $coupon->id;

        $orm             = new EasyStoreDatabaseOrm();
        $userCouponUsage = $orm->hasOne($couponId, '#__easystore_user_coupon_usage', 'coupon_id')
            ->updateQuery(function ($query) use ($orm, $email) {
                $query->where($orm->quoteName('email') . ' = ' . $orm->quote($email));
            })->loadObject();

        $previousCount = 0;

        if (!empty($userCouponUsage)) {
            $previousCount = (int) $userCouponUsage->coupon_count;
        }

        $newCount = $previousCount + 1;

        $usageData->coupon_id    = $couponId;
        $usageData->coupon_count = $newCount;
        $action                  = 'create';

        if (!empty($userCouponUsage)) {
            $action = 'update';
        }

        $this->createUpdateCouponUsage($usageData, $action);
    }

    /**
     * Creates or updates the coupon usage record in the database.
     *
     * This function handles both creating a new coupon usage record and updating an existing one.
     * Depending on the action specified ('create' or 'update'), it either inserts a new record
     * or updates the existing record with the new usage count.
     *
     * @param object $data   An object containing the coupon usage data.
     *                       Expected properties:
     *                       - user_id: The ID of the user using the coupon.
     *                       - email: The email of the user using the coupon.
     *                       - coupon_id: The ID of the coupon being used.
     *                       - coupon_count: The updated usage count of the coupon.
     * @param string $action The action to perform. Can be 'create' to insert a new record or 'update' to update an existing record.
     *
     * @since  1.2.0
     * @return void
     * @throws \Throwable If the database query fails, an exception is thrown.
     */
    public function createUpdateCouponUsage(object $data, string $action)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        if ($action === 'create') {
            $columns = [
                'email',
                'coupon_id',
                'coupon_count',
            ];

            $values = [
                $db->quote($data->email),
                $db->quote($data->coupon_id),
                $db->quote($data->coupon_count),
            ];

            if (!empty($user_id)) {
                $columns[] = 'user_id';
                $values[]  = $db->quote($user_id);
            }

            // Prepare the insert query.
            $query->insert($db->quoteName('#__easystore_user_coupon_usage'))
                ->columns($db->quoteName($columns))
                ->values(implode(',', $values));

            $db->setQuery($query);

            try {
                $db->execute();
            } catch (\Throwable $error) {
                throw $error;
            }
        } elseif ($action === 'update') {
            $fields = [
                $db->quoteName('coupon_count') . ' = ' . $data->coupon_count,
            ];

            $conditions = [
                $db->quoteName('email') . ' = ' . $db->quote($data->email),
                $db->quoteName('coupon_id') . ' = ' . $db->quote($data->coupon_id),
            ];

            $query->update($db->quoteName('#__easystore_user_coupon_usage'))->set($fields)->where($conditions);
            $db->setQuery($query);

            try {
                $db->execute();
            } catch (\Throwable $error) {
                throw $error;
            }
        }
    }
}
