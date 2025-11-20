<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Administrator\Supports\Shop;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);
?>
<style>
.easystore-summary-table,
td,
th {
    border-bottom: 1px solid #000;
    border-left: 1px solid #000;
    border-collapse: collapse
}

.easystore-summary-table {
    width: 100%
}

.easystore-summary-table th {
    padding: 8px;
    text-align: center;
    min-height: 48px;
}

.easystore-summary-table td {
    padding: 8px;
    text-align: left;
    min-height: 48px;
}

.text-center {
    text-align: center !important
}

.text-end {
    text-align: right !important
}

.easystore-summary-table thead {
    background: #000;
    color: #fff
}

.easystore-summary-table small {
    color: #5a5a5a
}
.d-flex {
    display: flex !important;
}
.flex-column {
    flex-direction: column !important;
}

.align-items-end {
    align-items: end !important;
}
.align-items-start {
    align-items: start !important;
}
.align-items-center {
    align-items: center !important;
}

.text-muted {
    color: #6c757d !important;
}

</style>

<table class="easystore-summary-table" style="border: 1px solid;">
    <thead>
        <tr>
            <th><?php echo Text::_('COM_EASYSTORE_CART_PRODUCT'); ?></th>
            <th><?php echo Text::_('COM_EASYSTORE_CART_QUANTITY'); ?></th>
            <th><?php echo Text::_('COM_EASYSTORE_CART_PRICE'); ?></th>
            <th><?php echo Text::_('COM_EASYSTORE_CART_TOTAL'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($products as $product): ?>
            <?php $cartItem = $product->cart_item;?>
        <tr>
            <td>
                <?php echo $product->title; ?>
                <?php if (!empty($cartItem)): ?>
                    <?php if (!empty($cartItem->options)): ?>
                    <br>
                    <small>
                        <?php foreach ($cartItem->options as $option) : ?>
                            <?php echo "<b>" . $option->key . ":</b> " . $option->name . ' ';?>
                        <?php endforeach; ?>
                    </small>
                    <?php endif;?>
                    <?php if (!empty($cartItem->weight_with_unit)): ?>
                    <br>
                    <small>
                        <?php echo "<b>" . Text::_('COM_EASYSTORE_PRODUCT_WEIGHT') . ':</b> ' . $cartItem->weight_with_unit; ?>
                    </small>
                    <?php endif;?>
                    <?php if (!empty($product->sku)): ?>
                        <small><b><?php echo Text::_('SKU'); ?>:</b> <?php echo $product->sku; ?></small>
                    <?php endif ?>
                <?php endif;?>
            </td>
            <td class="text-center"><?php echo $product->quantity; ?></td>
            <?php if (!empty($cartItem->is_coupon_applicable)) : ?>
            <td class="text-end">
                <span><?php echo $cartItem->final_price->unit_discounted_price_with_currency; ?></span>
                <del><?php echo $cartItem->final_price->unit_product_price_with_currency; ?></del>
                <div class="easystore-coupon-badge">
                    <svg viewBox="0 0 14 14" width="14" height="14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.394 4.73a1.125 1.125 0 1 1-2.25 0 1.125 1.125 0 0 1 2.25 0Zm2.345 2.843-2.83 2.83-.251.251L7.58 12.73a1.515 1.515 0 0 1-1.636.33 1.465 1.465 0 0 1-.483-.327L1.264 8.541a1.465 1.465 0 0 1-.438-1.055A1.488 1.488 0 0 1 1.26 6.43l2.085-2.085.252-.252L6.419 1.27A1.497 1.497 0 0 1 7.484.828h4.19a1.505 1.505 0 0 1 1.5 1.5v4.19a1.489 1.489 0 0 1-.435 1.055ZM8.6 9.595l.252-.252 2.822-2.821V2.326H7.477L4.405 5.4 2.32 7.485l4.203 4.19 2.079-2.078-.002-.002Z" fill="currentColor"/></svg>
                    <span><?php echo $cartItem->applied_coupon->code; ?></span>
                </div>
            </td>
            <?php else: ?>
                <td class="text-end">
                    <?php echo $cartItem->final_price->unit_product_price_with_currency; ?>
                </td>
            <?php endif;?>
            <td class="text-end d-flex flex-column align-items-end">
                <?php if ($cartItem->is_coupon_applicable) : ?>
                    <span>
                        <?php echo $cartItem->final_price->total_discounted_price_with_currency; ?>
                    </span>
                <?php else: ?>
                    <span>
                        <?php echo $cartItem->final_price->total_product_price_with_currency; ?>
                    </span>
                    <?php if ($is_tax_included_in_price && $cartItem->taxable_amount > 0): ?>
                        <small class="text-muted">
                            <?php echo Text::sprintf('COM_EASYSTORE_PER_ITEM_TAX_AMOUNT', $cartItem->taxable_amount_with_currency); ?>
                        </small>
                    <?php endif;?>
                <?php endif;?>
            </td>
        </tr>
        <?php endforeach;?>

    </tbody>
    <tfoot>
        <!-- Subtotal -->
        <tr>
            <th colspan="3" class="text-end"><?php echo Text::_('COM_EASYSTORE_ORDER_PRODUCT_PRICE_SUBTOTAL') ?></th>
            <th class="text-end d-flex flex-column align-items-end">
                <span>
                    <?php echo $sub_total_with_currency; ?>
                </span>
                <?php if ($is_tax_included_in_price && $taxable_amount > 0): ?>
                    <small class="text-muted">
                        <?php echo Text::sprintf('COM_EASYSTORE_PER_ITEM_TAX_AMOUNT', $taxable_amount_with_currency); ?>
                    </small>
                <?php endif;?>
            </th>
        </tr>

        <!-- Coupon discount -->
        <?php if (!empty(floatval($coupon_discount))): ?>
            <tr>
                <th colspan="3" class="text-end"><?php echo Text::_('COM_EASYSTORE_ORDER_COUPON_AMOUNT') ?></th>
                <th class="text-end"><?php echo Shop::asNegative($coupon_discount_with_currency); ?></th>
            </tr>
        <?php endif;?>

        <!-- Shipping Cost -->
        <tr>
            <th colspan="3" class="text-end">
                <?php echo Text::_('COM_EASYSTORE_ORDER_SHIPPING_COST_EXCLUDING_TAX'); ?>
                <?php if (!empty($shipping_method)): ?>
                    <br><small><?php echo Text::_('COM_EASYSTORE_SHIPPING_METHOD'); ?>: <?php echo $shipping_method; ?>
                    <?php if (!empty($pickup_date)): ?>
                        <br><?php echo Text::_('COM_EASYSTORE_PICKUP_DATE'); ?>: <?php echo date('d-m-Y', strtotime($pickup_date)); ?>
                    <?php endif; ?>
                    </small>
                <?php endif; ?>
            </th>
            <th class="text-end d-flex flex-column align-items-end">
                <span>
                    <?php echo $shipping_cost_with_currency; ?>
                </span>
                <?php if ($is_tax_included_in_price): ?>
                    <small class="text-muted">
                        <?php echo Text::sprintf('COM_EASYSTORE_PER_ITEM_TAX_AMOUNT', $shipping_tax_with_currency); ?>
                    </small>
                <?php endif;?>
            </th>
        </tr>

        <!-- Tax Breakdown -->
        <?php if (!$is_tax_included_in_price): ?>
            <tr>
                <th colspan="3" class="text-end"><?php echo Text::_('COM_EASYSTORE_CART_ORDER_SUMMARY_SALES_TAX') ?></th>
                <th class="text-end"><?php echo $sales_tax_with_currency; ?></th>
            </tr>
            <tr>
                <th colspan="3" class="text-end"><?php echo Text::_('COM_EASYSTORE_CART_ORDER_SUMMARY_SHIPPING_TAX') ?></th>
                <th class="text-end"><?php echo $shipping_tax_with_currency; ?></th>
            </tr>
        <?php endif;?>

        <!-- Total paid -->
        <tr>
            <th colspan="3" class="text-end"><?php echo Text::_('COM_EASYSTORE_ORDER_TOTAL_PRICE') ?></th>
            <th class="text-end"><?php echo $total_with_currency; ?></th>
        </tr>
    </tfoot>
</table>
