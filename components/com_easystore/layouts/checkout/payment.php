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
<div class="easystore-widget easystore-checkout-information" x-cloak x-show="payments?.length > 0">
    <h3 class="easystore-widget-title easystore-checkout-title"><?php echo Text::_('COM_EASYSTORE_PAYMENT_METHOD'); ?></h3>
    <ul class="easystore-payment-methods">
        <template x-for="(payment, index) in payments" :key="index">
            <li :class="`${payment_method === payment.name ? 'is-active': ''}`" @click="showAdditionalInfo = true" x-data="{showAdditionalInfo: index === 0}">
                <label class="easystore-payment-image-wrapper is-active" x-id="['payment-method']" :for="$id('payment-method')">
                    <input type="radio" class="form-check-input" :id="$id('payment-method')" :checked="payment_method === payment.name" name="payment_method" :value="payment.name"
                        x-model="payment_method" />
                    <img class="easystore-payment-vendor-brand" x-show="!!payment.logo" :src="payment.logo" :alt="payment.title" />
                    <span class="easystore-payment-vendor-name" x-text="payment.title"></span>
                </label>
                <div x-show="!!showAdditionalInfo && payment_method === payment.name && !!payment.additional_information" class="easystore-payment-additional-information-wrapper">
                    <span class="easystore-payment-additional-information" x-text="payment.additional_information"></span>
                </div>
            </li>
        </template>
    </ul>
</div>