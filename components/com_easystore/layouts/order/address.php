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

<div class="row mb-4">
    <div class="col-lg-6 mb-4 mb-lg-0">
        <div class="easystore-card easystore-card-border h-100">
            <div class="easystore-card-body">
                <h4 class="easystore-h4"><?php echo Text::_('COM_EASYSTORE_ADDRESS_SHIPPING') ?></h4>
                <div><?php echo $item->shipping_address->name; ?></div>
                <address>
                    <?php echo $item->user->shipping_address->address_1 ?? ''; ?>
                    <?php if (!empty($item->user->shipping_address->address_2)) : ?>
                        <br>
                        <?php echo $item->user->shipping_address->address_2; ?>
                    <?php endif; ?>
                    <br>
                    <?php echo $item->user->shipping_address->city ?? ''; ?>
                    <br>
                    <?php echo $item->user->shipping_address->state ?? ''; ?> <?php echo $item->user->shipping_address->zip_code ?? ''; ?>
                    <br>
                    <?php echo $item->user->shipping_address->country ?? ''; ?>
                    <br>
                    <?php echo $item->user->shipping_address->phone ?? ''; ?>
                </address>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="easystore-card easystore-card-border h-100">
            <div class="easystore-card-body">
                <h4 class="easystore-h4"><?php echo Text::_('COM_EASYSTORE_ADDRESS_BILLING') ?></h4>
                <?php if ($item->user->is_billing_and_shipping_address_same) :?>
                    <div>
                        <?php echo Text::_('COM_EASYSTORE_ORDER_SAME_AS_SHIPPING') ?>
                    </div>
                <?php else : ?>
                    <div><?php echo $item->billing_address->name; ?></div>
                    <address>
                        <?php echo $item->user->billing_address->address_1 ?? ''; ?>
                        <?php if (!empty($item->user->billing_address->address_2)) : ?>
                            <br>
                            <?php echo $item->user->billing_address->address_2; ?>
                        <?php endif; ?>
                        <br>
                        <?php echo $item->user->billing_address->city ?? ''; ?>
                        <br>
                        <?php echo $item->user->billing_address->state ?? ''; ?> <?php echo $item->user->billing_address->zip_code ?? ''; ?>
                        <br>
                        <?php echo $item->user->billing_address->country ?? ''; ?>
                        <br>
                        <?php echo $item->user->billing_address->phone ?? ''; ?>
                    </address>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>