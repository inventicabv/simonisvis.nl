<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use JoomShaper\Component\EasyStore\Site\Traits\Api;
use JoomShaper\Component\EasyStore\Site\Model\CouponModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Controller handling operations related to coupons in the EasyStore application.
 *
 * This controller provides methods to apply and remove coupon codes from carts.
 * It extends the BaseController and uses the Api trait for common API functionality.
 *
 * @since 1.2.0
 */
class CouponController extends BaseController
{
    use Api;

    /**
     * Applies a coupon code to the specified cart.
     *
     * This method handles the application of a coupon code to a cart based on the provided code, cart ID,
     * country ID, and shipping amount. It validates the HTTP request method to ensure it's not one of the
     * disallowed methods (GET, PUT, PATCH, DELETE). It retrieves input parameters such as the coupon code,
     * cart ID, country ID, and shipping amount from the request.
     * If any required information (coupon code or cart ID) is missing, it sends a 400 Bad Request response.
     * It retrieves the CouponModel instance and attempts to fetch the coupon details using the provided code.
     * If the coupon is not found, it sends a 404 Not Found response.
     * It then applies the coupon code to the cart using the CouponModel's applyCouponCode method.
     * If an error occurs during the application process, it sends a 500 Internal Server Error response
     * with the error message.
     * If successful, it sends a success response indicating that the coupon code has been applied.
     *
     * @return void
     *
     * @throws \Exception Throws any exception encountered during the coupon application process.
     *
     * @since 1.2.0
     */
    public function applyCoupon()
    {
        $requestMethod = $this->getInputMethod();
        $this->checkNotAllowedMethods(['GET', 'PUT', 'PATCH', 'DELETE'], $requestMethod);

        $code           = $this->getInput('code', '', 'STRING');
        $cartId         = $this->getInput('cart_id', 0, 'INT');
        $countryId      = $this->getInput('country', 0, 'STRING');
        $shippingAmount = $this->getInput('shipping', 0, 'STRING');

        if (!$code || !$cartId) {
            $this->sendResponse(['message' => 'Missing required information.'], 400);
        }

        /** @var CouponModel $model */
        $model = $this->getModel('Coupon', 'Site');

        try {
            $coupon = $model->getCouponByCode($code);
        } catch (\Exception $error) {
            $this->sendResponse(['message' => $error->getMessage()], 500);
        }

        if (is_null($coupon)) {
            $this->sendResponse(['message' => Text::_('COM_EASYSTORE_CART_COUPON_INVALID')], 404);
        }

        try {
            $model->applyCouponCode($cartId, $coupon, $countryId, $shippingAmount);
        } catch (\Exception $error) {
            $this->sendResponse(['message' => $error->getMessage()]);
        }

        $this->sendResponse(['success' => true]);
    }

    /**
     * Removes a coupon code from the cart based on the provided cart ID.
     *
     * This method handles the removal of a coupon code associated with the specified cart ID.
     * It first checks the HTTP request method to ensure it's not one of the disallowed methods
     * (GET, PUT, PATCH, DELETE). If the cart ID is missing or invalid, it sends a 400 Bad Request response.
     * It then retrieves the CouponModel instance and attempts to remove the coupon code using the cart ID.
     * If successful, it sends a success response; if an error occurs during the removal process, it sends
     * a 500 Internal Server Error response with the error message.
     *
     * @return void
     *
     * @throws \Exception Throws any exception encountered during the coupon removal process.
     *
     * @since 1.2.0
     */
    public function removeCode()
    {
        $requestMethod = $this->getInputMethod();
        $this->checkNotAllowedMethods(['GET', 'PUT', 'PATCH', 'DELETE'], $requestMethod);

        $cartId = $this->getInput('cart_id', 0, 'INT');

        if (!$cartId) {
            $this->sendResponse(['message' => 'Missing required information.'], 400);
        }

        /** @var CouponModel $model */
        $model = $this->getModel('Coupon', 'Site');

        try {
            $model->removeCouponCode($cartId);
        } catch (\Exception $error) {
            $this->sendResponse(['message' => $error->getMessage()], 500);
        }

        $this->sendResponse(['success' => true]);
    }
}
