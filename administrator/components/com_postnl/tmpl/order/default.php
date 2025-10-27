<?php

/**
 * @package     COM_POSTNL
 * @subpackage  Template
 *
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$item = $this->item;
$shippingAddress = $item->shipping_address_data;
$billingAddress = $item->billing_address_data;

?>
<form action="<?php echo Route::_('index.php?option=com_postnl&view=order&id=' . (int) $item->id); ?>" method="post" name="adminForm" id="adminForm">

    <div class="row">
        <div class="col-md-8">
            <!-- Order Info -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title"><?php echo Text::_('COM_POSTNL_ORDER_INFORMATION'); ?></h3>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3"><?php echo Text::_('COM_POSTNL_ORDER_NUMBER'); ?>:</dt>
                        <dd class="col-sm-9"><strong><?php echo $this->escape($item->order_number ?? '#' . $item->id); ?></strong></dd>

                        <dt class="col-sm-3"><?php echo Text::_('COM_POSTNL_CUSTOMER_EMAIL'); ?>:</dt>
                        <dd class="col-sm-9"><?php echo $this->escape($item->customer_email); ?></dd>

                        <dt class="col-sm-3"><?php echo Text::_('COM_POSTNL_ORDER_STATUS'); ?>:</dt>
                        <dd class="col-sm-9">
                            <span class="badge bg-primary"><?php echo $this->escape($item->order_status); ?></span>
                        </dd>

                        <dt class="col-sm-3"><?php echo Text::_('COM_POSTNL_ORDER_DATE'); ?>:</dt>
                        <dd class="col-sm-9"><?php echo HTMLHelper::_('date', $item->creation_date, Text::_('DATE_FORMAT_LC4')); ?></dd>
                    </dl>
                </div>
            </div>

            <!-- Shipping Address -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title"><?php echo Text::_('COM_POSTNL_SHIPPING_ADDRESS'); ?></h3>
                </div>
                <div class="card-body">
                    <?php if ($shippingAddress) : ?>
                        <address>
                            <?php echo $this->escape($shippingAddress->first_name ?? ''); ?>
                            <?php echo $this->escape($shippingAddress->last_name ?? ''); ?><br>
                            <?php if (!empty($shippingAddress->company)) : ?>
                                <?php echo $this->escape($shippingAddress->company); ?><br>
                            <?php endif; ?>
                            <?php echo $this->escape($shippingAddress->address_1 ?? ''); ?><br>
                            <?php if (!empty($shippingAddress->address_2)) : ?>
                                <?php echo $this->escape($shippingAddress->address_2); ?><br>
                            <?php endif; ?>
                            <?php echo $this->escape($shippingAddress->postcode ?? ''); ?>
                            <?php echo $this->escape($shippingAddress->city ?? ''); ?><br>
                            <?php echo $this->escape($shippingAddress->country_code ?? ''); ?>
                        </address>
                    <?php else : ?>
                        <p class="text-muted"><?php echo Text::_('COM_POSTNL_NO_ADDRESS'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Items -->
            <?php if (!empty($this->orderItems)) : ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title"><?php echo Text::_('COM_POSTNL_ORDER_ITEMS'); ?></h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th><?php echo Text::_('COM_POSTNL_ITEM_NAME'); ?></th>
                                <th class="text-center"><?php echo Text::_('COM_POSTNL_ITEM_QTY'); ?></th>
                                <th class="text-end"><?php echo Text::_('COM_POSTNL_ITEM_PRICE'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->orderItems as $orderItem) : ?>
                            <tr>
                                <td>
                                    <?php echo $this->escape($orderItem->product_title); ?>
                                    <?php if (!empty($orderItem->variant_title)) : ?>
                                        <small class="text-muted">(<?php echo $this->escape($orderItem->variant_title); ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo (int) $orderItem->quantity; ?></td>
                                <td class="text-end"><?php echo number_format((float) $orderItem->price, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <!-- PostNL Tracking -->
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <h3 class="card-title"><?php echo Text::_('COM_POSTNL_TRACKING_INFO'); ?></h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($item->tracking_number)) : ?>
                        <dl>
                            <dt><?php echo Text::_('COM_POSTNL_TRACKING_NUMBER'); ?>:</dt>
                            <dd><strong><?php echo $this->escape($item->tracking_number); ?></strong></dd>

                            <?php if (!empty($item->tracking_url)) : ?>
                            <dt><?php echo Text::_('COM_POSTNL_TRACKING_URL'); ?>:</dt>
                            <dd>
                                <a href="<?php echo $this->escape($item->tracking_url); ?>" target="_blank" class="btn btn-sm btn-primary">
                                    <span class="icon-out-2" aria-hidden="true"></span>
                                    <?php echo Text::_('COM_POSTNL_VIEW_TRACKING'); ?>
                                </a>
                            </dd>
                            <?php endif; ?>
                        </dl>
                    <?php else : ?>
                        <p class="alert alert-warning">
                            <span class="icon-info-circle" aria-hidden="true"></span>
                            <?php echo Text::_('COM_POSTNL_NO_TRACKING_YET'); ?>
                        </p>
                        <p><?php echo Text::_('COM_POSTNL_CREATE_LABEL_HELP'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PostNL Shipments -->
            <?php if (!empty($item->postnl_shipments)) : ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title"><?php echo Text::_('COM_POSTNL_SHIPMENTS'); ?></h3>
                </div>
                <div class="card-body">
                    <?php foreach ($item->postnl_shipments as $shipment) : ?>
                    <div class="border-bottom pb-2 mb-2">
                        <strong><?php echo $this->escape($shipment->barcode); ?></strong><br>
                        <small class="text-muted">
                            <?php echo HTMLHelper::_('date', $shipment->created_date, Text::_('DATE_FORMAT_LC4')); ?>
                        </small><br>
                        <span class="badge bg-<?php echo $shipment->status === 'created' ? 'success' : 'secondary'; ?>">
                            <?php echo $this->escape($shipment->status); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <input type="hidden" name="id" value="<?php echo $item->id; ?>">
    <input type="hidden" name="order_id" value="<?php echo $item->id; ?>">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
