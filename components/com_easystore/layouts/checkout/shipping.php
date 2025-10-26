<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);
?>

<div class="easystore-widget easystore-checkout-information" x-cloak>
    <h3 class="easystore-checkout-title easystore-widget-title">
        <?php echo Text::_('COM_EASYSTORE_SHIPPING_METHOD'); ?>
    </h3>

    <div class="easystore-skeleton-container" x-show="loadingShipping">
        <span class="easystore-skeleton"></span>
        <span class="easystore-skeleton"></span>
        <span class="easystore-skeleton"></span>
        <span class="easystore-skeleton"></span>
    </div>

    <ul class="easystore-shipping-methods" x-ref="shippingMethod" x-show="shipping?.length > 0 && !loadingShipping">
        <template x-for="(shippingItem, index) in shipping" :key="shippingItem.uuid">
            <li :class="{'is-active': (cart.shipping_method?.uuid === shippingItem.uuid)}">
                <label x-id="['shipping-method']" :for="$id('shipping-method')">
                    <input
                        class="form-check-input"
                        type="radio"
                        name="shipping_method"
                        :id="$id('shipping-method')"
                        :value="JSON.stringify(shippingItem)"
                        :checked="(cart.shipping_method?.uuid === shippingItem.uuid)"
                        required
                        @change="active_shipping = shippingItem.uuid"
                    />
                    
                    <div class="easystore-shipping-method">
                        <div class="easystore-shipping-method-name" x-text="shippingItem.name"></div>
                        <div class="easystore-shipping-method-description" x-text="shippingItem.estimate"></div>
                        <div x-show="cart.shipping_method?.address && cart.shipping_method?.uuid === shippingItem.uuid" class="easystore-shipping-method-address-wrapper">
                            <div class="easystore-shipping-method-address">
                                <select class="form-select form-control" name="shipping_pickup_address">
                                    <option value="" selected disabled>
                                        <?php echo Text::_('COM_EASYSTORE_SELECT_COLISSIOM_SHIPPING_ADDRESS'); ?>
                                    </option>
                                    <template x-for="(address, idx) in cart.shipping_method?.address" :key="idx">
                                            <option x-text="address.name" :value="JSON.stringify(address)"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>
                    <span class="easystore-shipping-price">
                    <span x-show="cart.coupon_category === 'free_shipping' && cart.coupon_code && cart.coupon_discount_with_currency" x-text="cart.coupon_discount_with_currency"></span>
                    <span x-show="cart.coupon_category !== 'free_shipping'" x-text="shippingItem.rate_with_currency"></span>
                    </span>
                </label>

            </li>
        </template>
    </ul>
   
    <div class="easystore-no-shipping" x-show="!shipping?.length && !loadingShipping"><?php echo Text::_('COM_EASYSTORE_CART_NO_SHIPPING_AVAILABLE'); ?></div>
</div>
