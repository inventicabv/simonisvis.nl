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
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

extract($displayData);

?>

<div class="easystore-list-group">
    <div class="easystore-list-group-header">
        <span class="easystore-list-key"><strong><?php echo Text::_('COM_EASYSTORE_ORDER_PRODUCT_PRICE_SUBTOTAL') ?></strong></span>
        <div class="easystore-list-value">
            <div class="d-flex flex-column align-items-end">
                <?php echo $item->discounted_sub_total > 0 ? $item->discounted_sub_total_with_currency : $item->sub_total_with_currency;  ?>
                <?php if ($item->is_tax_included_in_price && $item->taxable_amount > 0) : ?>
                    <small class="text-muted"><?php echo Text::sprintf('COM_EASYSTORE_PER_ITEM_TAX_AMOUNT', $item->taxable_amount_with_currency); ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($item->coupon_code)) : ?>
    <div class="easystore-list-item">
        <div class="easystore-checkout-coupon">
            <span><?php echo Text::_('COM_EASYSTORE_CART_ORDER_SUMMARY_COUPON_DISCOUNT'); ?></span>
            <div class="easystore-checkout-coupon__info">
                <span><?php echo $item->coupon_code; ?></span>
                <?php if ($item->coupon_category !== 'free_shipping') : ?>
                    <span class="easystore-checkout-coupon__value" >(−&thinsp;<?php echo $item->coupon_discount_with_currency; ?>)</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($item->shipping)) : ?>
    <div class="easystore-list-item">
        <div class="easystore-checkout-shipping">
            <span class="easystore-checkout-shipping__title"><?php echo Text::_('COM_EASYSTORE_CART_ORDER_SUMMARY_SHIPPING'); ?></span>
            <div>
                <span class="easystore-checkout-shipping__name">
                    <?php echo $item->shipping->name ?? ''; ?>
                    <?php if (!empty($item->pickup_date)): ?>
                        <br><small><?php echo Text::_('COM_EASYSTORE_PICKUP_DATE'); ?>: <?php echo date('d-m-Y', strtotime($item->pickup_date)); ?></small>
                    <?php endif; ?>
                </span>
                <span class="easystore-checkout-shipping__weight">(<?php echo $item->total_weight_with_unit; ?>)</span>
            </div>
        </div>
        <div class="easystore-list-value">
            <div class="d-flex flex-column align-items-end">
                <?php echo $item->shipping->rate_with_currency ?? ''; ?>
                <?php if ($item->is_tax_included_in_price && $item->shipping_tax > 0) : ?>
                    <small class="text-muted"><?php echo Text::sprintf('COM_EASYSTORE_PER_ITEM_TAX_AMOUNT', $item->shipping_tax_with_currency); ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- If the "tax included in product price" setting is enabled in the tax settings, we still need to display the applicable tax rates for previously placed orders. -->
    <?php if (!$item->is_tax_included_in_price && $item->taxable_amount > 0) : ?>
        <?php echo EasyStoreHelper::loadLayout('order.tax-rates', ['item' => $item]); ?>
    <?php endif; ?>

    <div class="easystore-list-item">
        <span class="easystore-list-key"><strong><?php echo Text::_('COM_EASYSTORE_ORDER_TOTAL_PRICE') ?></strong></span>
        <span class="easystore-list-value"><strong><?php echo $item->total_with_currency; ?></strong></span>
    </div>
</div>
