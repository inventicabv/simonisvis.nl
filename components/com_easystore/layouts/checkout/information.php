<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

$user                    = Factory::getApplication()->getIdentity();
$settings                = SettingsHelper::getSettings();
$allowGuestCheckout      = $settings->get('checkout.allow_guest_checkout', false);
$phoneNumberCondition    = $settings->get('checkout.phone_number', 'optional');
$addressLineTwoCondition = $settings->get('checkout.address_line_two', 'optional');
$isGuestCheckout         = $user->guest && $allowGuestCheckout;
?>

<div class="easystore-checkout-information">
    <div class="easystore-checkout-form">
        <?php if ($address === 'shipping') : ?>
            <div class="easystore-widget">
                <h3 class="easystore-widget-title"><?php echo Text::_('COM_EASYSTORE_ADDRESS_SHIPPING'); ?></h3>
                <div class="easystore-compact-form">
                    <div>
                        <input type="text" class="form-control" name="shipping_customer_name" placeholder="<?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_CUSTOMER_NAME'); ?>" x-model="information.shipping_address.name" 
                        :class="errors.shippingAddressError.shipping_customer_name ? 'easystore-checkout-item-error-input' : ''"
                        
                        />
                    </div>
                    <div>
                        <select name="shipping_country" class="form-select form-control" x-model="information.shipping_address.country"
                        :class="errors.shippingAddressError.shipping_country ? 'easystore-checkout-item-error-input' : ''">
                            <option value=""><?php echo Text::_('COM_EASYSTORE_SELECT_COUNTRY_PLACEHOLDER'); ?></option>
                            <template x-for="(country, index) in countries" :key="country.value">
                                <option :value="country.value" x-text="country.label" :selected="country.value === information.shipping_address.country || countries.length === 1"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <input name="shipping_address_line_1" type="text" class="form-control" x-model="information.shipping_address.address_1"  :class="errors.shippingAddressError.shipping_address_line_1 ? 'easystore-checkout-item-error-input' : ''"
                        placeholder="<?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_ADDRESS_LINE_1'); ?>" :disabled="!information.shipping_address.country"/>
                    </div>

                    <?php if ($addressLineTwoCondition !== 'do-not-include') : ?>
                        <div>
                            <input name="shipping_address_line_2" type="text" class="form-control" placeholder="<?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_ADDRESS_LINE_2'); ?>" x-model="information.shipping_address.address_2" 
                            :class="errors.shippingAddressError.shipping_address_line_2 ? 'easystore-checkout-item-error-input' : ''"
                            :disabled="!information.shipping_address.country"/>
                        </div>
                    <?php endif;?>

                    <div class="easystore-half-width">
                        <input name="shipping_city" type="text" class="form-control" placeholder="<?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_CITY'); ?>"  x-model="information.shipping_address.city" 
                        :class="errors.shippingAddressError.shipping_city ? 'easystore-checkout-item-error-input' : ''"
                        :disabled="!information.shipping_address.country" @input.debounce.500ms="handleShippingCityChange($event.target.value)"/>
                    </div>
                    <div class="easystore-half-width">
                        <input name="shipping_zip_code" type="text" class="form-control" placeholder="<?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_ZIP_CODE'); ?>" x-model="information.shipping_address.zip_code" 
                        :class="errors.shippingAddressError.shipping_zip_code? 'easystore-checkout-item-error-input' : ''"
                        :disabled="!information.shipping_address.country" @input.debounce.500ms="handleShippingZipCodeChange($event.target.value)" />
                    </div>
                    <div x-show="shipping_states.length > 0">
                        <select name="shipping_state" class="form-select form-control" x-model="information.shipping_address.state" 
                        :class="errors.shippingAddressError.shipping_state ? 'easystore-checkout-item-error-input' : ''"
                        :disabled="!information.shipping_address.country">
                            <option value=""><?php echo Text::_('COM_EASYSTORE_SELECT_STATE_PLACEHOLDER'); ?></option>
                            <template x-for="(state, index) in shipping_states" :key="state.value">
                                <option :value="state.value" x-text="state.label" :selected="state.value == information.shipping_address.state" ></option>
                            </template>
                        </select>
                    </div>
                    <?php if ($phoneNumberCondition !== 'do-not-include') : ?>
                        <div>
                            <input name="shipping_phone" type="text" class="form-control" placeholder="<?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_CUSTOMER_NUMBER'); ?>" x-model="information.shipping_address.phone" 
                            :class="errors.shippingAddressError.shipping_phone ? 'easystore-checkout-item-error-input' : ''"
                            :disabled="!information.shipping_address.country"/>
                        </div>
                    <?php endif;?>
                </div>

                <?php if ($isGuestCheckout) : ?>
                    <div class="form-check form-check-inline mt-3">
                        <input type="checkbox" name="save_address" class="form-check-input" id="save-for-next-time" x-model="information.save_guest_shipping" />
                        <label for="save-for-next-time" class="form-check-label">
                            <?php echo Text::_('COM_EASYSTORE_CHECKOUT_SAVE_SHIPPING_ADDRESS'); ?>
                        </label>
                    </div>
                <?php endif;?>
            </div>
        <?php endif;?>

        <?php if ($address === 'billing') : ?>
            <div x-data="{ showBillingErrors: true }">
            <div class="easystore-checkout-billing-address">

                <div class="form-check form-check-inline mb-3">
                    <input class="form-check-input" type="checkbox" id="is_billing_and_shipping_address_same" name="is_billing_and_shipping_address_same" x-model="information.is_billing_and_shipping_address_same" 
                    @change="showBillingErrors = !information.is_billing_and_shipping_address_same"

                    :checked="!!information.is_billing_and_shipping_address_same">
                    <label class="form-check-label" for="is_billing_and_shipping_address_same"><?php echo Text::_('COM_EASYSTORE_ADDRESS_SAME_AS_SHIPPING'); ?></label>
                </div>

                <div class="easystore-compact-form" x-cloak x-show="!information.is_billing_and_shipping_address_same">
                    <div>
                        <input type="text" class="form-control" name="billing_customer_name" placeholder="<?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_CUSTOMER_NAME'); ?>" x-model="information.billing_address.name"
                        :class="errors.billingAddressError.billing_customer_name ? 'easystore-checkout-item-error-input' : ''"  />
                    </div>
                    <div>
                        <select name="billing_country" class="form-select form-control" x-model="information.billing_address.country"
                        :class="errors.billingAddressError.billing_country ? 'easystore-checkout-item-error-input' : ''">
                            <option value=""><?php echo Text::_('COM_EASYSTORE_SELECT_COUNTRY_PLACEHOLDER'); ?></option>
                            <template x-for="(country, index) in countries" :key="country.value">
                                <option :value="country.value" x-text="country.label"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <input name="billing_address_line_1" type="text" class="form-control" x-model="information.billing_address.address_1" 
                        :class="errors.billingAddressError.billing_address_line_1 ? 'easystore-checkout-item-error-input' : ''"
                         placeholder="<?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_ADDRESS_LINE_1'); ?>" :disabled="!information.billing_address.country" />
                    </div>

                    <?php if ($addressLineTwoCondition !== 'do-not-include') : ?>
                        <?php $isAddressTwoRequired = $addressLineTwoCondition === 'required';?>
                        <div>
                            <input name="billing_address_line_2" type="text" class="form-control" placeholder="<?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_ADDRESS_LINE_2'); ?>" x-model="information.billing_address.address_2" 
                            :class="errors.billingAddressError.billing_address_line_2 ? 'easystore-checkout-item-error-input' : ''"
                            :disabled="!information.billing_address.country"  />
                        </div>
                    <?php endif;?>
                    <div class="easystore-half-width">
                        <input name="billing_city" type="text" class="form-control" placeholder="<?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_CITY'); ?>"
                         x-model="information.billing_address.city" 
                         :class="errors.billingAddressError.billing_city ? 'easystore-checkout-item-error-input' : '' "
                         :disabled="!information.billing_address.country"/>
                    </div>
                    <div class="easystore-half-width">
                        <input name="billing_zip_code" type="text" class="form-control" placeholder="<?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_ZIP_CODE'); ?>" x-model="information.billing_address.zip_code" 
                        :class="errors.billingAddressError.billing_zip_code ? 'easystore-checkout-item-error-input' : ''"
                        :disabled="!information.billing_address.country"/>
                    </div>
                    <div x-show="billing_states.length > 0">
                        <select name="billing_state" class="form-select form-control" x-model="information.billing_address.state" 
                        :class="errors.billingAddressError.billing_state ? 'easystore-checkout-item-error-input' : ''"
                        :disabled="!information.billing_address.country">
                            <option value=""><?php echo Text::_('COM_EASYSTORE_SELECT_STATE_PLACEHOLDER'); ?></option>
                            <template x-for="(state, index) in billing_states" :key="state.value">
                                <option :value="state.value" x-text="state.label" :selected="state.value == information.billing_address.state"></option>
                            </template>
                        </select>
                    </div>
                    <?php if ($phoneNumberCondition !== 'do-not-include') : ?>
                        <?php $isPhoneRequired = $phoneNumberCondition === 'required';?>
                        <div>
                            <input name="billing_phone" type="text" class="form-control" placeholder="<?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_CUSTOMER_NUMBER'); ?>" x-model="information.billing_address.phone" 
                            :class="errors.billingAddressError.billing_phone ? 'easystore-checkout-item-error-input' : ''"
                            :disabled="!information.billing_address.country" />
                        </div>
                    <?php endif;?>
                </div>
                <div class="easystore-checkout-item-error-wrapper">
                    <div x-show="showBillingErrors" class="easystore-checkout-item-error-section">   
                        <template x-for="error in Object.values(errors.billingAddressError)">
                            <div x-show="!!error" class="easystore-checkout-item-error-text">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"><path fill="#DC3545" fill-rule="evenodd" d="M4.916 12a7.091 7.091 0 0 1 7.083-7.083A7.091 7.091 0 0 1 19.083 12a7.091 7.091 0 0 1-7.084 7.084A7.091 7.091 0 0 1 4.916 12Zm1.287 0c0 3.196 2.6 5.796 5.796 5.796s5.795-2.6 5.795-5.795c0-3.196-2.6-5.796-5.795-5.796a5.802 5.802 0 0 0-5.796 5.796ZM12 7.923a.86.86 0 0 0 0 1.717.86.86 0 0 0 0-1.717Zm-.644 3.649a.644.644 0 0 1 1.288 0v3.864a.644.644 0 0 1-1.288 0V11.57Z" clip-rule="evenodd"/></svg>
                                <span x-text="error"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            </div>
        <?php endif;?>
    </div>
</div>