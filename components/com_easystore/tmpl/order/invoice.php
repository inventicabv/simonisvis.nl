<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Site\Helper\OrderHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

EasyStoreHelper::wa()
    ->useStyle('com_easystore.site')
    ->useStyle('com_easystore.profile.site')
    ->useStyle('com_easystore.checkout.site')
    ->useStyle('com_easystore.order.site');

$item = $this->item;
?>

<div easystore-invoice-content x-data="easystore_invoice">
    <div class="d-flex justify-content-between border-bottom mt-4">
        <div class="easystore-invoice-order-info pb-3">
            <div class="mb-1"><strong><?php echo Text::_('COM_EASYSTORE_ORDERS_ORDER_PLACED'); ?>:</strong> <?php echo HTMLHelper::_('date', $item->created, 'DATE_FORMAT_LC2'); ?></div>
            <div class="mb-1">
                <strong><?php echo Text::_('COM_EASYSTORE_ORDERS_ORDER_ID'); ?>:</strong> <?php echo OrderHelper::formatOrderNumber($item->id); ?>
            </div>
            <?php if (!empty($item->seller_tax_id)) : ?>
                <div class="mb-1"><?php echo $item->seller_tax_id; ?></div>
            <?php endif;?>
            <?php if (!empty($item->company_name)) : ?>
                <div class="mb-1"><strong><?php echo Text::_('COM_EASYSTORE_ORDER_COMPANY_NAME'); ?>:</strong> <?php echo $item->company_name; ?></div>
            <?php endif;?>
            <?php if (!empty($item->company_id)) : ?>
                <div class="mb-1"><strong><?php echo Text::_('COM_EASYSTORE_ORDER_COMPANY_ID'); ?>:</strong> <?php echo $item->company_id; ?></div>
            <?php endif;?>
            <?php if (!empty($item->vat_information)) : ?>
                <div class="mb-1"><strong><?php echo Text::_('COM_EASYSTORE_ORDER_VATIN'); ?>:</strong> <?php echo $item->vat_information; ?></div>
            <?php endif;?>
            <div class="mb-1"><strong><?php echo Text::_('COM_EASYSTORE_ORDER_PAYMENT_METHOD'); ?>:</strong> <?php echo EasyStoreHelper::getPaymentMethodString($item->payment_method); ?></div>
            <div><strong><?php echo Text::_('COM_EASYSTORE_ORDERS_ORDER_TOTAL'); ?>:</strong> <?php echo $item->total_with_currency; ?></div>
        </div>
        <div>
            <button @click="easystorePrintInvoice" class="btn btn-primary" easystore-print-invoice>
                <?php echo Text::_('COM_EASYSTORE_ORDER_INVOICE_PRINT'); ?>
            </button>
        </div>
    </div>

    <?php echo LayoutHelper::render('order.products', ['products' => $item->products, 'item' => $item]); ?>
    <?php echo LayoutHelper::render('order.summary', ['item' => $item]); ?>
    <?php echo LayoutHelper::render('invoice.customer-note', ['customer_note' => $item->customer_note]); ?>
    <?php echo LayoutHelper::render('order.address', ['item' => $item]); ?>
    <?php echo LayoutHelper::render('order.payment_instructions', ['item' => $item]); ?>
</div>
