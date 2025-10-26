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

$item = $this->item;

EasyStoreHelper::wa()
    ->useStyle('com_easystore.site')
    ->useStyle('com_easystore.profile.site')
    ->useStyle('com_easystore.checkout.site')
    ->useStyle('com_easystore.order.site');
?>

<div class="row justify-content-center">
    <?php if (empty($item->is_guest_order)) : ?>
    <div class="col-lg-4 col-xl-3 mb-4 mb-lg-0">
        <?php echo EasyStoreHelper::loadLayout('profile.sidebar', ['view' => 'orders']); ?>
    </div>
    <?php endif;?>

    <div class="col-lg-8 col-xl-9">
        <div class="easystore-card easystore-card-border my-4">
            <div class="easystore-card-header">
                <div>
                    <h4 class="easystore-h4">
                        <?php echo Text::_('COM_EASYSTORE_ORDERS_ORDER_DETAILS') ?>
                        <span class="badge rounded-pill text-bg-<?php echo EasyStoreHelper::getPaymentBadgeColor($item->payment_status); ?>"><?php echo EasyStoreHelper::getPaymentStatusString($item->payment_status); ?></span>
                    </h4>
                    <small class="text-muted"><?php echo Text::_('COM_EASYSTORE_ORDER_ORDER_PLACED_ON') ?> <?php echo HTMLHelper::_('date', $item->created, 'DATE_FORMAT_LC2'); ?></small>
                    <div>
                        <small>
                            <b><?php echo Text::_('COM_EASYSTORE_ORDERS_ORDER_ID'); ?>:</b> <?php echo OrderHelper::formatOrderNumber($item->id); ?>
                        </small>
                    </div>
                    <?php if (!empty($item->company_name)) :?>
                        <div>
                            <small>
                                <b><?php echo Text::_('COM_EASYSTORE_ORDER_COMPANY_NAME'); ?>:</b> <?php echo $item->company_name; ?>
                            </small>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($item->company_id)) :?>
                        <div>
                            <small>
                                <b><?php echo Text::_('COM_EASYSTORE_ORDER_COMPANY_ID'); ?>:</b> <?php echo $item->company_id; ?>
                            </small>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($item->vat_information)) :?>
                        <div>
                            <small>
                                <b><?php echo Text::_('COM_EASYSTORE_ORDER_VATIN'); ?>:</b> <?php echo $item->vat_information; ?>
                            </small>
                        </div>    
                    <?php endif; ?>
                    <div>
                        <small>
                            <b><?php echo Text::_('COM_EASYSTORE_ORDER_PAYMENT_METHOD'); ?>:</b> <?php echo EasyStoreHelper::getPaymentMethodString($item->payment_method); ?>
                        </small>
                    </div>
                    <?php if (isset($item->seller_tax_id) && !empty($item->seller_tax_id)) :?>
                    <div>
                        <small>
                            <?php echo $item->seller_tax_id; ?>
                        </small>
                    </div>
                    <?php endif;?>
                </div>
                <span x-data="easystorePaynow"><?php echo LayoutHelper::render('order.invoice', ['item' => $item]); ?></span>
            </div>

            <div class="easystore-card-body">
                <?php echo LayoutHelper::render('order.products', ['products' => $item->products, 'item' => $item]); ?>
                <?php echo LayoutHelper::render('order.summary', ['item' => $item]); ?>
            </div>
        </div>
        
        <?php echo LayoutHelper::render('order.customer-note', ['customer_note' => $item->customer_note]); ?>
        <?php echo LayoutHelper::render('order.address', ['item' => $item]); ?>
        <?php echo LayoutHelper::render('order.history', ['history' => $item->activities]); ?>
        <?php echo LayoutHelper::render('order.payment_instructions', ['item' => $item]); ?>
    </div>
</div>
