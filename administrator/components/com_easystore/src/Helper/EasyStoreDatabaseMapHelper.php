<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Helper;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Helper\ContentHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * EasyStore component helper.
 *
 * @since  1.0.0
 */
class EasyStoreDatabaseMapHelper extends ContentHelper
{
    /**
     * Function to get Discount types
     * @param string $key
     *
     * @return array|string
     */
    public static function getDiscountTypes($key = null)
    {
        $discountTypes = [
            'percent' => Text::_('COM_EASYSTORE_DISCOUNT_TYPE_PERCENT'),
            'amount'  => Text::_('COM_EASYSTORE_DISCOUNT_TYPE_AMOUNT'),
        ];

        if (!is_null($key)) {
            return array_key_exists($key, $discountTypes) ? $discountTypes[$key] : 'not_found';
        }

        return $discountTypes;
    }

    /**
     * Function to get Payment status
     * @param string $key
     *
     * @return array|string
     */
    public static function getPaymentStatus($key = null)
    {
        $paymentStatus = [
            'paid'               => Text::_('COM_EASYSTORE_PAYMENT_STATUS_PAID'),
            'unpaid'             => Text::_('COM_EASYSTORE_PAYMENT_STATUS_UNPAID'),
            'refunded'           => Text::_('COM_EASYSTORE_PAYMENT_STATUS_REFUNDED'),
            'pending'            => Text::_('COM_EASYSTORE_PAYMENT_STATUS_PENDING'),
            'canceled'           => Text::_('COM_EASYSTORE_PAYMENT_STATUS_CANCELED'),
            'failed'             => Text::_('COM_EASYSTORE_PAYMENT_STATUS_FAILED'),
            'partially_refunded' => Text::_('COM_EASYSTORE_PAYMENT_STATUS_PARTIALLY_REFUNDED'),
        ];

        if (!is_null($key)) {
            return array_key_exists($key, $paymentStatus) ? $paymentStatus[$key] : 'not_found';
        }

        return $paymentStatus;
    }

    /**
     * Function to get Fulfilment
     * @param string $key
     *
     * @return array|string
     */
    public static function getFulfilment($key = null)
    {
        $fulfilment = [
            'unfulfilled' => Text::_('COM_EASYSTORE_FULFILMENT_UNFULFILLED'),
            'cancelled'   => Text::_('COM_EASYSTORE_FULFILMENT_CANCELLED'),
            'fulfilled'   => Text::_('COM_EASYSTORE_FULFILMENT_FULFILLED'),
        ];

        if (!is_null($key)) {
            return array_key_exists($key, $fulfilment) ? $fulfilment[$key] : 'not_found';
        }

        return $fulfilment;
    }

    /**
     * Function to get Gender
     * @param string $key
     *
     * @return array|string
     */
    public static function getGender($key = null)
    {
        $gender = [
            'male'   => Text::_('COM_EASYSTORE_GENDER_MALE'),
            'female' => Text::_('COM_EASYSTORE_GENDER_FEMALE'),
            'other'  => Text::_('COM_EASYSTORE_GENDER_OTHER'),
        ];

        if (!is_null($key)) {
            return array_key_exists($key, $gender) ? $gender[$key] : 'not_found';
        }

        return $gender;
    }

    /**
     * Function to get Address types
     * @param string $key
     *
     * @return array|string
     */
    public static function getAddressTypes($key = null)
    {
        $addressTypes = [
            'shipping' => Text::_('COM_EASYSTORE_ADDRESS_TYPE_SHIPPING'),
            'billing'  => Text::_('COM_EASYSTORE_ADDRESS_TYPE_BILLING'),
        ];

        if (!is_null($key)) {
            return array_key_exists($key, $addressTypes) ? $addressTypes[$key] : 'not_found';
        }

        return $addressTypes;
    }

    /**
     * Function to get Order activity types
     * @param string $key
     *
     * @return array|string
     */
    public static function getOrderActivities($key = null)
    {
        $orderActivities = [
            'order_created'                 => Text::_('COM_EASYSTORE_ORDER_ACTIVITY_ORDER_CREATED'),
            'tracking_number_added'         => Text::_('COM_EASYSTORE_ORDER_ACTIVITY_TRACKING_NUMBER_ADDED'),
            'tracking_number_edited'        => Text::_('COM_EASYSTORE_ORDER_ACTIVITY_TRACKING_NUMBER_EDITED'),
            'marked_as_paid'                => Text::_('COM_EASYSTORE_ORDER_ACTIVITY_MARKED_AS_PAID'),
            'marked_as_unpaid'              => Text::_('COM_EASYSTORE_ORDER_ACTIVITY_MARKED_AS_UNPAID'),
            'marked_as_refunded'            => Text::_('COM_EASYSTORE_ORDER_ACTIVITY_MARKED_AS_REFUNDED'),
            'marked_as_partially_refunded'  => Text::_('COM_EASYSTORE_ORDER_ACTIVITY_MARKED_AS_PARTIALLY_REFUNDED'),
            'marked_as_unfulfilled'         => Text::_('COM_EASYSTORE_ORDER_ACTIVITY_MARKED_AS_UNFULFILLED'),
            'marked_as_cancelled'           => Text::_('COM_EASYSTORE_ORDER_ACTIVITY_MARKED_AS_CANCELLED'),
            'marked_as_fulfilled'           => Text::_('COM_EASYSTORE_ORDER_ACTIVITY_MARKED_AS_FULFILLED'),
            'marked_as_active'              => Text::_('COM_EASYSTORE_ORDER_ACTIVITY_MARKED_AS_ACTIVE'),
            'marked_as_draft'               => Text::_('COM_EASYSTORE_ORDER_ACTIVITY_MARKED_AS_DRAFT'),
            'marked_as_archived'            => Text::_('COM_EASYSTORE_ORDER_ACTIVITY_MARKED_AS_ARCHIVED'),
            'comment'                       => Text::_('COM_EASYSTORE_ORDER_ACTIVITY_COMMENT'),
        ];

        if (!is_null($key)) {
            return array_key_exists($key, $orderActivities) ? $orderActivities[$key] : 'not_found';
        }

        return $orderActivities;
    }
}
