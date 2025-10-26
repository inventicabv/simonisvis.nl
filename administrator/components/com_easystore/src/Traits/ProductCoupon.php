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
use JoomShaper\Component\EasyStore\Administrator\Model\CouponsModel;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;

trait ProductCoupon
{
    /**
     * Function for processing coupons api methods
     *
     * @return void
     */
    public function coupons()
    {
        $requestMethod = $this->getInputMethod();

        switch ($requestMethod) {
            case 'GET':
                $this->getCoupons();
                break;
            case 'POST':
                $this->postCoupons();
                break;
            case 'PUT':
                $this->updateCoupons();
                break;
            case 'PATCH':
                $this->patchCoupons();
                break;
            case 'DELETE':
                $this->deleteCoupons();
                break;
        }
    }

    /**
     * Function for processing couponById api methods
     *
     * @return void
     */
    public function couponById()
    {
        $requestMethod = $this->getInputMethod();
        $id            = $this->getInput('id', null, 'INT');

        $this->checkNotAllowedMethods(['POST', 'PUT', 'PATCH', 'DELETE'], $requestMethod);

        $this->getCouponById($id);
    }

    /**
     * Function for processing couponBulkEdit api methods
     *
     * @return void
     */
    public function couponBulkEdit()
    {
        $requestMethod = $this->getInputMethod();

        $this->checkNotAllowedMethods(['GET', 'POST', 'PUT', 'DELETE'], $requestMethod);

        $this->couponBulkUpdate();
    }

    /**
     * Coupon Bulk update main function
     *
     * @return void
     */
    private function couponBulkUpdate()
    {
        $ids = $this->getInput('ids', '', 'STRING');
        $ids = !empty($ids) ? explode(',', $ids) : [];

        $data = $this->getInput('data', '', 'STRING');

        $data = json_decode($data, true);

        $response = new \stdClass();

        if (empty($ids)) {
            $response->status  = false;
            $response->message = Text::_("COM_EASYSTORE_APP_BULK_EDIT_FAILED");

            $this->sendResponse($response);
        }

        if (is_array($data)) {
            $type  = $data['type'];
            $value = !empty($data['value']) ? $data['value'] : null;

            $model = $this->getModel('Coupons');

            if (empty($value)) {
                $response->status  = false;
                $response->message = Text::_("COM_EASYSTORE_APP_BULK_EDIT_FAILED");

                $this->sendResponse($response);
            }

            if ($type === 'status') {
                $coupon            = new \stdClass();
                $coupon->published = $value === 'active' ? 1 : 0;

                foreach ($ids as $id) {
                    $coupon->id = $id;
                    $model->editByObject($coupon);
                }
            } elseif ($type === 'delete') {
                $model->delete($ids);
            } else {
                $response->status  = false;
                $response->message = Text::_("COM_EASYSTORE_APP_BULK_EDIT_FAILED");

                $this->sendResponse($response);
            }

            $response->status  = true;
            $response->message = Text::_("COM_EASYSTORE_APP_BULK_EDIT_SUCCESS");

            $this->sendResponse($response);
        } else {
            $response->status  = false;
            $response->message = Text::_("COM_EASYSTORE_APP_BULK_EDIT_FAILED");

            $this->sendResponse($response);
        }
    }

    /**
     * Function for processing validateCoupon api methods
     *
     * @return void
     */
    public function validateCoupon()
    {
        $requestMethod = $this->getInputMethod();

        $this->checkNotAllowedMethods(['POST', 'PUT', 'PATCH', 'DELETE'], $requestMethod);

        $this->couponValidation();
    }

    /**
     * Function for processing duplicateCoupon api methods
     *
     * @return void
     */
    public function duplicateCoupon()
    {
        $requestMethod = $this->getInputMethod();

        $this->checkNotAllowedMethods(['POST', 'PUT', 'PATCH', 'DELETE'], $requestMethod);

        $this->createDuplicateCoupon();
    }

    /**
     * Function to get Coupons
     *
     * @return void
     */
    private function getCoupons()
    {
        $params         = new \stdClass();
        $params->limit  = $this->getInput('limit', 10);
        $params->offset = $this->getInput('offset', 0);
        $params->search = $this->getInput('search', '', 'STRING');
        $params->status = $this->getInput('status', '', 'STRING');
        $params->sortBy = $this->getInput('sortBy', '', 'STRING');
        $params->all    = $this->getInput('all', false);

        $model = $this->getModel('Coupons');
        $items = $model->getCoupons($params);

        $this->sendResponse($items);
    }

    /**
     * Function to get Coupon by Id
     *
     * @param int $id
     * @return void
     */
    private function getCouponById(int $id)
    {
        if (is_null($id)) {
            throw new \Exception('The ID is missing!');
        }

        /** @var CouponsModel $model */
        $model  = $this->getModel('Coupons');
        $result = $model->getCouponById($id);

        $this->sendResponse($result);
    }

    /**
     * Post request for creating Coupon
     *
     * @return void
     */
    private function postCoupons()
    {
        $name       = $this->getInput('name', '', 'STRING');
        $code       = $this->getInput('code', '', 'STRING');
        $start_date = $this->getInput('start_date', '', 'STRING');
        $start_time = $this->getInput('start_time', '', 'STRING');
        $has_date   = $this->getInput('has_date', false);
        $end_date   = $this->getInput('end_date', '', 'STRING');
        $end_time   = $this->getInput('end_time', '', 'STRING');

        if (empty($name)) {
            $message = Text::_('COM_EASYSTORE_APP_COUPON_EMPTY_VALUE_COUPON_NAME');
            $this->sendResponse($message, 400);
        }

        if (empty($code)) {
            $message = Text::_('COM_EASYSTORE_APP_COUPON_EMPTY_VALUE_COUPON_CODE');
            $this->sendResponse($message, 400);
        }

        if (empty($start_date) || empty($start_time)) {
            $message = Text::_('COM_EASYSTORE_APP_COUPON_EMPTY_VALUE_COUPON_START_DATE_TIME');
            $this->sendResponse($message, 400);
        }

        $start_datetime = $this->concatDateTime($start_date, $start_time);
        $end_datetime   = null;

        if ($has_date) {
            if (empty($end_date) || empty($end_time)) {
                $message = Text::_('COM_EASYSTORE_APP_COUPON_EMPTY_VALUE_COUPON_END_DATE_TIME');
                $this->sendResponse($message, 400);
            }

            $end_datetime = $this->concatDateTime($end_date, $end_time);
        }

        $model = $this->getModel('Coupons');

        $response   = new \stdClass();
        $couponInfo = new \stdClass();

        $isValid = $model->couponValidation($code);

        if (!$isValid) {
            $response->status  = false;
            $response->message = Text::_("COM_EASYSTORE_APP_COUPON_ERROR_INVALID_COUPON");

            $this->sendResponse($response);
        }

        $couponInfo->name                        = $name;
        $couponInfo->coupon_category             = $this->getInput('coupon_category', 'discount', 'STRING');
        $couponInfo->code                        = $code;
        $couponInfo->discount_type               = $this->getInput('discount_type', 'percent', 'STRING');
        $couponInfo->discount_value              = $this->formatToNumeric($this->getInput('discount_value'), 'decimal');
        $couponInfo->sale_value                  = $this->formatToNumeric($this->getInput('sale_value'), 'decimal');
        $couponInfo->applies_to                  = $this->getInput('applies_to', 'all_products', 'STRING');
        $couponInfo->country_type                = $this->getInput('country_type', 'all', 'STRING');
        $couponInfo->selected_countries          = $this->getInput('selected_countries', '', 'STRING');
        $couponInfo->applies_to_x                = $this->getInput('applies_to_x', 'all_products', 'STRING');
        $couponInfo->buy_x                       = $this->formatToNumeric($this->getInput('buy_x'));
        $couponInfo->applies_to_y                = $this->getInput('applies_to_y', 'all_products', 'STRING');
        $couponInfo->get_y                       = $this->formatToNumeric($this->getInput('get_y'));
        $couponInfo->coupon_limit_status         = $this->getInput('coupon_limit_status', false);
        $couponInfo->coupon_limit_value          = $this->formatToNumeric($this->getInput('coupon_limit_value'));
        $couponInfo->usage_limit_status          = $this->getInput('usage_limit_status', false);
        $couponInfo->usage_limit_value           = $this->formatToNumeric($this->getInput('usage_limit_value'));
        $couponInfo->purchase_requirements       = $this->getInput('purchase_requirements', '', 'STRING');
        $couponInfo->purchase_requirements_value = $this->formatToNumeric($this->getInput('purchase_requirements_value'), 'decimal');
        $couponInfo->start_date                  = $start_datetime;
        $couponInfo->has_date                    = $has_date;
        $couponInfo->end_date                    = $end_datetime;
        $couponInfo->coupon_status               = $this->getInput('coupon_status', 'inactive', 'STRING');
        $couponInfo->ordering                    = $this->formatToNumeric($this->getInput('ordering'));
        $couponInfo->access                      = $this->formatToNumeric($this->getInput('access'));
        $couponInfo->language                    = $this->getInput('language', '*');

        $couponId = $model->store($couponInfo);

        // Coupon Category map
        $categories = $this->getInput('categories', [], 'ARRAY');

        if (!empty($categories) && $couponInfo->applies_to === 'specific_categories') {
            $model->storeMultipleCategories($categories, $couponId);
        }

        // Coupon Product & Variant map
        $products = $this->getInput('products', '', 'STRING');
        $products = json_decode($products, true);

        if (!empty($products) && $couponInfo->applies_to === 'specific_products') {
            $this->updateCouponMultipleProductMap($products ?? [], $couponId);
        }

        // Buy X Get Y
        // Coupon Category map
        $categoriesX = $this->getInput('categories_x', [], 'ARRAY');
        $categoriesY = $this->getInput('categories_y', [], 'ARRAY');

        if (!empty($categoriesX) && $couponInfo->applies_to_x === 'specific_categories') {
            $model->storeMultipleCategories($categoriesX, $couponId, 'x');
        }

        if (!empty($categoriesY) && $couponInfo->applies_to_y === 'specific_categories') {
            $model->storeMultipleCategories($categoriesY, $couponId, 'y');
        }

        // Coupon Product & Variant map
        $productsX = $this->getInput('products_x', '', 'STRING');
        $productsX = json_decode($productsX, true);
        $productsY = $this->getInput('products_y', '', 'STRING');
        $productsY = json_decode($productsY, true);

        if (!empty($productsX) && $couponInfo->applies_to_x === 'specific_products') {
            $this->updateCouponMultipleProductMap($productsX ?? [], $couponId, 'x');
        }

        if (!empty($productsY) && $couponInfo->applies_to_y === 'specific_products') {
            $this->updateCouponMultipleProductMap($productsY ?? [], $couponId, 'y');
        }

        if ($couponId) {
            $response->status  = true;
            $response->message = Text::_("COM_EASYSTORE_APP_COUPON_COUPON_CREATED");
            $response->id      = $couponId;
        } else {
            $response->status  = false;
            $response->message = Text::_("COM_EASYSTORE_APP_COUPON_FAILED_TO_CREATE_COUPON");
        }

        $this->sendResponse($response);
    }

    /**
     * Update Coupon by Id with PUT request
     *
     * @return void
     */
    private function updateCoupons()
    {
        $id         = $this->getInput('id', 0, 'INT');
        $name       = $this->getInput('name', '', 'STRING');
        $code       = $this->getInput('code', '', 'STRING');
        $start_date = $this->getInput('start_date', '', 'STRING');
        $start_time = $this->getInput('start_time', '', 'STRING');
        $has_date   = $this->getInput('has_date', false);
        $end_date   = $this->getInput('end_date', '', 'STRING');
        $end_time   = $this->getInput('end_time', '', 'STRING');

        $response = new \stdClass();

        if (empty($id)) {
            $response->status  = false;
            $response->message = Text::_("COM_EASYSTORE_APP_COUPON_FAILED_TO_UPDATE_COUPON");

            $this->sendResponse($response);
        }

        if (empty($name)) {
            $message = Text::_('COM_EASYSTORE_APP_COUPON_EMPTY_VALUE_COUPON_NAME');
            $this->sendResponse($message, 400);
        }

        if (empty($code)) {
            $message = Text::_('COM_EASYSTORE_APP_COUPON_EMPTY_VALUE_COUPON_CODE');
            $this->sendResponse($message, 400);
        }

        if (empty($start_date) || empty($start_time)) {
            $message = Text::_('COM_EASYSTORE_APP_COUPON_EMPTY_VALUE_COUPON_START_DATE_TIME');
            $this->sendResponse($message, 400);
        }

        $start_datetime = $this->concatDateTime($start_date, $start_time);
        $end_datetime   = null;

        if ($has_date) {
            if (empty($end_date) || empty($end_time)) {
                $message = Text::_('COM_EASYSTORE_APP_COUPON_EMPTY_VALUE_COUPON_END_DATE_TIME');
                $this->sendResponse($message, 400);
            }

            $end_datetime = $this->concatDateTime($end_date, $end_time);
        }

        /** @var CouponsModel $model */
        $model = $this->getModel('Coupons');

        $couponInfo = new \stdClass();

        $isValid = $model->couponValidation($code, $id);

        if (!$isValid) {
            $response->status  = false;
            $response->message = Text::_("COM_EASYSTORE_APP_COUPON_ERROR_INVALID_COUPON");

            $this->sendResponse($response);
        }

        $couponInfo->id                          = $id;
        $couponInfo->name                        = $name;
        $couponInfo->coupon_category             = $this->getInput('coupon_category', 'discount', 'STRING');
        $couponInfo->code                        = $code;
        $couponInfo->discount_type               = $this->getInput('discount_type', 'percent', 'STRING');
        $couponInfo->discount_value              = $this->formatToNumeric($this->getInput('discount_value'), 'decimal');
        $couponInfo->sale_value                  = $this->formatToNumeric($this->getInput('sale_value'), 'decimal');
        $couponInfo->applies_to                  = $this->getInput('applies_to', 'all_products', 'STRING');
        $couponInfo->country_type                = $this->getInput('country_type', 'all', 'STRING');
        $couponInfo->selected_countries          = $couponInfo->country_type == 'all' ? '' : $this->getInput('selected_countries', '', 'STRING');
        $couponInfo->applies_to_x                = $this->getInput('applies_to_x', 'all_products', 'STRING');
        $couponInfo->buy_x                       = $this->formatToNumeric($this->getInput('buy_x'));
        $couponInfo->applies_to_y                = $this->getInput('applies_to_y', 'all_products', 'STRING');
        $couponInfo->get_y                       = $this->formatToNumeric($this->getInput('get_y'));
        $couponInfo->coupon_limit_status         = $this->getInput('coupon_limit_status', false);
        $couponInfo->coupon_limit_value          = $this->formatToNumeric($this->getInput('coupon_limit_value'));
        $couponInfo->usage_limit_status          = $this->getInput('usage_limit_status', false);
        $couponInfo->usage_limit_value           = $this->formatToNumeric($this->getInput('usage_limit_value'));
        $couponInfo->purchase_requirements       = $this->getInput('purchase_requirements', '', 'STRING');
        $couponInfo->purchase_requirements_value = $this->formatToNumeric($this->getInput('purchase_requirements_value'), 'decimal');
        $couponInfo->start_date                  = $start_datetime;
        $couponInfo->has_date                    = $has_date;
        $couponInfo->end_date                    = $end_datetime;
        $couponInfo->coupon_status               = $this->getInput('coupon_status', 'inactive', 'STRING');
        $couponInfo->ordering                    = $this->formatToNumeric($this->getInput('ordering'));
        $couponInfo->access                      = $this->formatToNumeric($this->getInput('access'));
        $couponInfo->language                    = $this->getInput('language', '*');
        $updateStatus                            = $model->update($couponInfo);

        // Coupon Category map
        $categories = $this->getInput('categories', [], 'ARRAY');

        if (!empty($categories) && $couponInfo->applies_to === 'specific_categories') {
            $model->storeMultipleCategories($categories, $id);
        }

        if (empty($categories)) {
            EasyStoreDatabaseOrm::removeByIds('#__easystore_coupon_category_map', [$id], 'coupon_id');
        }

        // Coupon Product & Variant map
        $products = $this->getInput('products', '', 'STRING');
        $products = json_decode($products, true);

        if (!empty($products) && $couponInfo->applies_to === 'specific_products') {
            $this->updateCouponMultipleProductMap($products ?? [], $id);
        }

        if (empty($products)) {
            EasyStoreDatabaseOrm::removeByIds('#__easystore_coupon_product_map', [$id], 'coupon_id');
        }

        // Buy X Get Y
        // Coupon Category map
        $categoriesX = $this->getInput('categories_x', [], 'ARRAY');
        $categoriesY = $this->getInput('categories_y', [], 'ARRAY');

        if (!empty($categoriesX) && $couponInfo->applies_to_x === 'specific_categories') {
            $model->storeMultipleCategories($categoriesX, $id, 'x');
        }

        if (!empty($categoriesY) && $couponInfo->applies_to_y === 'specific_categories') {
            $model->storeMultipleCategories($categoriesY, $id, 'y');
        }

        // Coupon Product & Variant map
        $productsX = $this->getInput('products_x', '', 'STRING');
        $productsX = json_decode($productsX, true);
        $productsY = $this->getInput('products_y', '', 'STRING');
        $productsY = json_decode($productsY, true);

        if (!empty($productsX) && $couponInfo->applies_to_x === 'specific_products') {
            $this->updateCouponMultipleProductMap($productsX ?? [], $id, 'x');
        }

        if (!empty($productsY) && $couponInfo->applies_to_y === 'specific_products') {
            $this->updateCouponMultipleProductMap($productsY ?? [], $id, 'y');
        }

        if ($updateStatus) {
            $response->status  = true;
            $response->message = Text::_("COM_EASYSTORE_APP_COUPON_COUPON_UPDATED");
            $response->id      = $id;
        } else {
            $response->status  = false;
            $response->message = Text::_("COM_EASYSTORE_APP_COUPON_FAILED_TO_UPDATE_COUPON");
        }

        $this->sendResponse($response);
    }

    /**
     * Update Coupons Status by PATCH request
     *
     * @return void
     */
    private function patchCoupons()
    {
        $id     = $this->getInput('id', 0, 'INT');
        $status = $this->getInput('status', '', 'STRING');

        if (empty($id)) {
            $message = Text::_('COM_EASYSTORE_APP_COUPON_EMPTY_VALUE_COUPON_ID');
            $this->sendResponse($message, 400);
        }

        if (empty($status)) {
            $message = Text::_('COM_EASYSTORE_APP_COUPON_EMPTY_VALUE_COUPON_STATUS');
            $this->sendResponse($message, 400);
        }

        $model = $this->getModel('Coupons');

        $updateStatus = $model->updateStatus($id, $status);
        $response     = new \stdClass();

        if ($updateStatus) {
            $response->status  = true;
            $response->message = Text::_("COM_EASYSTORE_APP_COUPON_COUPON_STATUS_UPDATED");
            $response->id      = $id;
        } else {
            $response->status  = false;
            $response->message = Text::_("COM_EASYSTORE_APP_COUPON_FAILED_TO_UPDATE_COUPON_STATUS");
        }

        $this->sendResponse($response);
    }

    /**
     * Delete Coupon by Ids
     *
     * @return void
     */
    private function deleteCoupons()
    {
        $ids = $this->getInput('ids', '', 'STRING');
        $ids = !empty($ids) ? explode(',', $ids) : [];

        $response = new \stdClass();

        if (empty($ids)) {
            $message = Text::_('COM_EASYSTORE_APP_COUPON_EMPTY_VALUE_COUPON_ID');
            $this->sendResponse($message, 400);
        }

        $model  = $this->getModel('Coupons');
        $result = $model->delete($ids);

        if (!$result) {
            $response->status  = false;
            $response->message = Text::_("COM_EASYSTORE_APP_COUPON_FAILED_TO_DELETE_COUPON");
            $this->sendResponse($response);
        }

        $response->status  = true;
        $response->message = Text::_("COM_EASYSTORE_APP_COUPON_DELETE_COUPON_SUCCESS");
        $this->sendResponse($response);
    }

    /**
     * Function to validate Coupon code
     *
     * @return void
     */
    private function couponValidation()
    {
        $code     = $this->getInput('code', '', 'STRING');
        $couponId = $this->getInput('coupon_id', null, 'int');

        $model = $this->getModel('Coupons');
        $valid = $model->couponValidation($code, $couponId);

        $response          = new \stdClass();
        $response->isValid = false;

        if ($valid) {
            $response->isValid = true;
        }

        $this->sendResponse($response);
    }

    /**
     * Function to duplicate a Coupon by Id
     *
     * @return void
     */
    private function createDuplicateCoupon()
    {
        $id   = $this->getInput('id', 0, 'INT');
        $code = $this->getInput('code', null, 'STRING');

        $response = new \stdClass();

        if (empty($id)) {
            $message = Text::_('COM_EASYSTORE_APP_COUPON_EMPTY_VALUE_COUPON_ID');
            $this->sendResponse($message, 400);
        }

        if (empty($code)) {
            $message = Text::_('COM_EASYSTORE_APP_COUPON_EMPTY_VALUE_COUPON_CODE');
            $this->sendResponse($message, 400);
        }

        $model  = $this->getModel('Coupons');
        $result = $model->duplicateCoupon($id, $code);

        if (!$result) {
            $response->status  = false;
            $response->message = Text::_("COM_EASYSTORE_APP_COUPON_FAILED_TO_DUPLICATE");
            $this->sendResponse($response);
        }

        $response->status  = true;
        $response->message = Text::_("COM_EASYSTORE_APP_COUPON_DUPLICATE_COUPON_SUCCESS");
        $this->sendResponse($response);
    }

    /**
     * Function to process and store multiple Products info with variants in Coupon
     *
     * @param array     $products   Array of Product Ids
     * @param int   $couponId   Coupon Id
     * @param string    $isOffer    Is offer X or Y
     * @return void
     */
    private function updateCouponMultipleProductMap(array $products, int $couponId, string $isOffer = null)
    {
        $productIds = array_map(function ($product) {
            return $product['id'];
        }, $products);

        $model = $this->getModel('Coupons');

        if (!empty($productIds)) {
            $model->storeMultipleProducts($productIds, $couponId, $isOffer);
        }

        foreach ($products as $product) {
            $model->storeMultipleProductSkus($product['variants'] ?? [], $product['id'], $couponId);
        }
    }

    /**
     * Function to get category list
     *
     * @return void
     */
    public function getCategories()
    {
        $params                 = new \stdClass();
        $params->published      = 1;
        $params->excludePresets = 0;

        $model      = $this->getModel('Categories');
        $categories = $model->getCategories($params);

        $this->sendResponse($categories);
    }
}
