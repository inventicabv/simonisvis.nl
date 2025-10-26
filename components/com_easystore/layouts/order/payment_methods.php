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

<div class="easystore-widget easystore-checkout-information px-4 py-4 d-flex flex-column gap-2" x-data="easystorePaymentMethods">
    <h3 class="easystore-widget-title easystore-checkout-title"><?php echo Text::_('COM_EASYSTORE_PAYMENT_METHODS'); ?></h3>
    <ul class="easystore-payment-methods mb-2" @change="handlePaymentChange">
        <?php foreach ($payment_methods->list as $payment_method) : ?>
        <li>
            <label>
                <input type="radio" class="form-check-input" name="payment_method" value="<?php echo $payment_method->name; ?>">
                <img class="easystore-payment-vendor-brand" src="<?php echo $payment_method->logo; ?>" />
                <span class="easystore-payment-vendor-name"><?php echo $payment_method->title; ?></span>
            </label>
        </li>
        <?php endforeach;?>
    </ul>
    <button class="btn btn-primary py-3 fw-bold" :disabled="!selectedPaymentMethod" @click="handlePaymentSubmit"><?php echo Text::_('COM_EASYSTORE_ORDER_PROCEED_TO_PAYMENT'); ?></button>
</div>
