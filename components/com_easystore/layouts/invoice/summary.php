<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;

extract($displayData);
?>

<div class="easystore-list-group">
    <div class="easystore-list-group-header">
        <span class="easystore-list-key"><?php echo Text::_('COM_EASYSTORE_ORDER_PRODUCT_PRICE_SUBTOTAL') ?></span>
        <span class="easystore-list-value"><?php echo $item->sub_total_with_currency; ?></span>
    </div>

    <?php if (!empty($item->coupon_discount)) : ?>
    <div class="easystore-list-item">
        <div class="easystore-coupon-wrapper">
            <span class="easystore-list-key"><?php echo Text::_('Coupon discount') ?></span>
            <div class="easystore-coupon-badge">
                <svg viewBox="0 0 14 14" width="14" height="14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.394 4.73a1.125 1.125 0 1 1-2.25 0 1.125 1.125 0 0 1 2.25 0Zm2.345 2.843-2.83 2.83-.251.251L7.58 12.73a1.515 1.515 0 0 1-1.636.33 1.465 1.465 0 0 1-.483-.327L1.264 8.541a1.465 1.465 0 0 1-.438-1.055A1.488 1.488 0 0 1 1.26 6.43l2.085-2.085.252-.252L6.419 1.27A1.497 1.497 0 0 1 7.484.828h4.19a1.505 1.505 0 0 1 1.5 1.5v4.19a1.489 1.489 0 0 1-.435 1.055ZM8.6 9.595l.252-.252 2.822-2.821V2.326H7.477L4.405 5.4 2.32 7.485l4.203 4.19 2.079-2.078-.002-.002Z" fill="currentColor"/></svg>
                <span><?php echo $item->coupon_code; ?></span>
            </div>
        </div>
        <span class="easystore-list-value">-<?php echo $item->coupon_discount_with_currency; ?></span>
    </div>
    <?php endif;?>

    <?php if (!empty($item->shipping_cost)) : ?>
    <div class="easystore-list-item">
        <span class="easystore-list-key"><?php echo Text::_('COM_EASYSTORE_ORDER_SHIPPING_COST') ?></span>
        <span class="easystore-list-value"><?php echo $item->shipping_cost_with_currency; ?></span>
    </div>
    <?php endif; ?>

    <?php if (!empty($item->sale_tax)) : ?>
    <div class="easystore-list-item">
        <span class="easystore-list-key"><?php echo Text::_('COM_EASYSTORE_CART_ORDER_SUMMARY_SALES_TAX') ?></span>
        <span class="easystore-list-value"><?php echo $item->taxable_amount_with_currency; ?></span>
    </div>
    <?php endif; ?>

    <div class="easystore-list-item">
        <span class="easystore-list-key"><strong><?php echo Text::_('COM_EASYSTORE_ORDER_TOTAL_PRICE') ?></strong></span>
        <span class="easystore-list-value"><strong><?php echo $item->total_with_currency; ?></strong></span>
    </div>
</div>
