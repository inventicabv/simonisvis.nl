<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Supports\Shop;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

$settings            = SettingsHelper::getSettings();
$enableCouponCode    = $settings->get('checkout.enable_coupon_code', true);
$defaultThumbnailSrc = EasyStoreHelper::getPlaceholderImage();

Text::script('COM_EASYSTORE_PER_ITEM_TAX_AMOUNT');

?>

<div x-cloak>
    <div x-show="!!cart?.items" x-transition class="easystore-checkout-items">
        <template x-for="cartItem in cart.items" :key="cartItem.id">
            <div class="easystore-checkout-item">
                <div class="row align-items-start">
                    <!-- Product Image/video -->
                    <div class="col-2">
                        <video class="easystore-checkout-item-thumbnail" x-show="cartItem.isVideo" :src="cartItem.image?.src ?? ''" :alt="cartItem.name" loading="lazy" height="56"></video>
                        <img class="easystore-checkout-item-thumbnail" x-show="!cartItem.isVideo" :src="cartItem.image?.src ? cartItem.image.src : '<?php echo $defaultThumbnailSrc; ?>'" :alt="cartItem.name" loading="lazy" />
                    </div>

                    <div class="col-7">
                        <h3 class="easystore-h4 mb-2" x-text="cartItem.title"></h3>
                        
                        <!-- Product variants -->
                        <div class="easystore-metadata-h">
                            <template x-for="(option, index) in cartItem.options" :key="option.key">
                                <div class="easystore-metadata-item">
                                    <span class="easystore-metadata-key" x-text="option.key + ':'"></span>
                                    <span class="easystore-metadata-value" x-text="option.name"></span>
                                </div>
                            </template>

                            <!-- Product quantity -->
                            <div class="easystore-metadata-item">
                                <span class="easystore-metadata-key">
                                    <?php echo Text::_('COM_EASYSTORE_QUANTITY'); ?>:
                                </span>
                                <span class="easystore-metadata-value" x-text="cartItem.quantity"></span>
                            </div>
                            <!-- Product weight -->
                            <div class="easystore-metadata-item" x-show="cartItem.weight > 0">
                                <span class="easystore-metadata-key">
                                    <?php echo Text::_('COM_EASYSTORE_CHECKOUT_SUMMARY_WEIGHT'); ?>:
                                </span>
                                <span class="easystore-metadata-value" x-text="cartItem.weight_with_unit"></span>
                            </div>
                        </div>

                        <!-- Applied Coupon -->
                        <div class="easystore-metadata-h easystore-small" x-show="!!cartItem?.is_coupon_applicable">
                            <div class="easystore-metadata-key">
                                <svg viewBox="0 0 14 14" width="14" height="14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.394 4.73a1.125 1.125 0 1 1-2.25 0 1.125 1.125 0 0 1 2.25 0Zm2.345 2.843-2.83 2.83-.251.251L7.58 12.73a1.515 1.515 0 0 1-1.636.33 1.465 1.465 0 0 1-.483-.327L1.264 8.541a1.465 1.465 0 0 1-.438-1.055A1.488 1.488 0 0 1 1.26 6.43l2.085-2.085.252-.252L6.419 1.27A1.497 1.497 0 0 1 7.484.828h4.19a1.505 1.505 0 0 1 1.5 1.5v4.19a1.489 1.489 0 0 1-.435 1.055ZM8.6 9.595l.252-.252 2.822-2.821V2.326H7.477L4.405 5.4 2.32 7.485l4.203 4.19 2.079-2.078-.002-.002Z" fill="currentColor"/></svg>
                                <span x-text="cartItem.applied_coupon.code"></span>
                                (−&thinsp;<span x-text="cartItem.final_price.unit_discount_value_with_currency"></span>)
                            </div>
                        </div>

                        <!-- Product unit price if coupon applied -->
                        <template x-if="cartItem?.is_coupon_applicable">
                            <div class="easystore-metadata-h easystore-small">
                                <span x-text="cartItem.final_price.unit_discounted_price_with_currency"></span>
                                <del x-text="cartItem.final_price.unit_product_price_with_currency"></del>
                            </div>
                        </template>

                        <!-- Product unit price if coupon is not applied -->
                        <template x-if="!cartItem?.is_coupon_applicable">
                            <div class="easystore-metadata-h easystore-small">
                                <span x-text="cartItem.final_price.unit_product_price_with_currency"></span>
                            </div>
                        </template>
                    </div>

                    <div class="col d-flex flex-column align-items-end justify-content-md-end">
                        <!-- Product total price if coupon applied -->
                        <template x-if="cartItem?.is_coupon_applicable">
                            <span class="easystore-checkout-item-subtotal" x-text="cartItem.final_price.total_discounted_price_with_currency"></span>
                        </template>

                        <!-- Product total price if coupon is not applied -->
                        <template x-if="!cartItem?.is_coupon_applicable">
                            <span class="easystore-checkout-item-subtotal" x-text="cartItem.final_price.total_product_price_with_currency"></span>
                        </template>

                        <!-- Product taxable amount -->
                        <?php if (!Shop::isTaxEnabled()) : ?>
                            <small x-show="cartItem.taxable_amount > 0" class="text-muted" x-text="Joomla.Text.sprintf('COM_EASYSTORE_PER_ITEM_TAX_AMOUNT', cartItem.taxable_amount_with_currency)"></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div class="easystore-skeleton-container" x-show="loading">
        <span class="easystore-skeleton"></span>
        <span class="easystore-skeleton"></span>
        <span class="easystore-skeleton"></span>
        <span class="easystore-skeleton"></span>
    </div>

    <div class="easystore-checkout-footer easystore-list-section" x-show="!loading">
        <!-- Order subtotal -->
        <div class="easystore-list-item">
            <span class="easystore-list-key fw-bold">
                <?php echo Text::_('COM_EASYSTORE_CART_ORDER_SUMMARY_SUBTOTAL'); ?>
            </span>

            <div class="d-flex flex-column align-items-end">
                <template x-if="cart.discounted_sub_total > 0">
                    <span class="easystore-list-value fw-bold" x-text="cart.discounted_sub_total_with_currency"></span>
                </template>
                <template x-if="cart.discounted_sub_total <= 0">
                    <span class="easystore-list-value fw-bold" x-text="cart.sub_total_with_currency"></span>
                </template>
                <?php if (!Shop::isTaxEnabled()) : ?>
                    <small x-show="cart.taxable_amount > 0" class="text-muted" x-text="Joomla.Text.sprintf('COM_EASYSTORE_PER_ITEM_TAX_AMOUNT', cart.taxable_amount_with_currency)"></small>
                <?php endif; ?>
            </div>
        </div>

        <div class="easystore-list-group">
            <!-- Coupon Code -->
            <?php if ($enableCouponCode) : ?>
                <div class="easystore-list-item" x-show="!cart.coupon_code">
                    <div class="w-100" x-show="!coupon.showCouponInput">
                        <a href="#" @click.prevent="handleAddPromotionClick"><?php echo Text::_('COM_EASYSTORE_CHECKOUT_ADD_PROMOTION_CODE'); ?></a>
                    </div>
                    <div class="w-100" x-show="coupon.showCouponInput" x-transition>
                        <div class="easystore-checkout-coupon-container">
                            <input x-ref="couponInput" type="text" class="form-control" x-model="coupon.code" @keyup.enter="applyCouponCode" @keydown.enter="$event.preventDefault()"
                                @click.outside="handleCouponInputOutsideClick">
                            <button type="button" class="btn" x-show="coupon.code.length > 0" @click.prevent="applyCouponCode">
                                <?php echo Text::_('COM_EASYSTORE_CHECKOUT_COUPON_APPLY_BUTTON'); ?>
                            </button>
                        </div>
                        <span x-show="coupon.message" x-text="coupon.message"></span>
                    </div>
                </div>
                <div class="easystore-list-item" x-show="!!cart.coupon_code">
                    <div class="easystore-checkout-coupon">
                        <span><?php echo Text::_('COM_EASYSTORE_CART_ORDER_SUMMARY_COUPON_DISCOUNT'); ?></span>
                        <div class="easystore-checkout-coupon__info">
                            <span x-text="cart.coupon_code"></span>
                            <span x-show="cart.coupon_category !== 'free_shipping'" class="easystore-checkout-coupon__value" x-text="`(−${cart.coupon_discount_with_currency})`"></span>
                        </div>
                        <button type="button" class="easystore-small easystore-coupon-remove" @click="removeCouponCode">
                            <svg viewBox="0 0 14 14" width="14" height="14" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#a)" fill-rule="evenodd" clip-rule="evenodd" fill="#515151"><path d="M2.8 3.15v9.582c0 .314.254.568.568.568h7.263a.569.569 0 0 0 .569-.568V3.15h.7v9.582c0 .7-.569 1.268-1.27 1.268H3.369c-.7 0-1.268-.568-1.268-1.268V3.15h.7Z"/><path d="M5.95 6.3a.35.35 0 0 1 .35.35v3.5a.35.35 0 1 1-.7 0v-3.5a.35.35 0 0 1 .35-.35ZM8.05 6.3a.35.35 0 0 1 .35.35v3.5a.35.35 0 1 1-.7 0v-3.5a.35.35 0 0 1 .35-.35ZM6.65.7a.35.35 0 0 0-.35.35v.7h-.7v-.7C5.6.47 6.07 0 6.65 0h.7C7.93 0 8.4.47 8.4 1.05v.7h-.7v-.7A.35.35 0 0 0 7.35.7h-.7Z"/><path d="M1.75 2.1a.35.35 0 1 0 0 .7h10.5a.35.35 0 1 0 0-.7H1.75ZM.7 2.45c0-.58.47-1.05 1.05-1.05h10.5a1.05 1.05 0 1 1 0 2.1H1.75C1.17 3.5.7 3.03.7 2.45Z"/></g><defs><clipPath id="a"><path fill="#fff" d="M0 0h14v14H0z"/></clipPath></defs></svg>
                        </button>
                    </div>
                </div>
            <?php endif;?>

            <!-- Shipping Method -->
            <div class="easystore-list-item" x-show="!!cart.shipping_method">
                <div class="easystore-checkout-shipping">
                    <span class="easystore-checkout-shipping__title"><?php echo Text::_('COM_EASYSTORE_CART_ORDER_SUMMARY_SHIPPING'); ?></span>
                    <div>
                        <span class="easystore-checkout-shipping__name" x-text="cart.shipping_method?.name ?? ''"></span>
                        <span class="easystore-checkout-shipping__weight" x-text="`(${cart.total_weight_with_unit})`"></span>
                    </div>
                </div>
                <div class="d-flex flex-column align-items-end">
                    <span class="easystore-list-value" x-text="`${cart.shipping_method?.rate_with_currency ?? ''}`"></span>
                    <?php if (!Shop::isTaxEnabled()) : ?>
                        <small x-show="cart.shipping_tax > 0" class="text-muted" x-text="Joomla.Text.sprintf('COM_EASYSTORE_PER_ITEM_TAX_AMOUNT', cart.shipping_tax_with_currency)"></small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tax Breakdown -->
            <?php if (Shop::isTaxEnabled()) : ?>
                <?php echo EasyStoreHelper::loadLayout('checkout.tax-rates'); ?>
            <?php endif; ?>
        </div>

        <!-- Total Amount -->
        <div class="easystore-list-group-footer">
            <span class="easystore-list-key easystore-text-bold"><?php echo Text::_('COM_EASYSTORE_CART_ORDER_SUMMARY_TOTAL'); ?></span>
            <span class="easystore-list-value easystore-text-bold" x-text="cart.total_with_currency"></span>
        </div>

        
    </div>
</div>
