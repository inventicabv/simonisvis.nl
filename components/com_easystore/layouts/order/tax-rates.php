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

<div class="easystore-list-item" x-data="{ isOpen: false }" x-cloak x-effect="document.body.style.overflow = isOpen ? 'hidden': 'initial'">
    <div class="easystore-list-key easystore-checkout-show-tax-rate">
        <?php echo Text::_('COM_EASYSTORE_CART_ORDER_SUMMARY_TAXES'); ?>
        <?php if (isset($item->is_legacy_order) && (int) $item->is_legacy_order === 0) : ?>
            <button type="button" data-tax-breakdown class="easystore-button-reset" @click="isOpen = true"><?php echo Text::_('COM_EASYSTORE_TAX_BREAKDOWN_SHOW_TAX_RATE'); ?></button>
        <?php endif; ?>
    </div>
    <span class="easystore-list-value"><?php echo $item->taxable_amount_with_currency; ?></span>
    <div class="easystore-breakdown__modal" role="dialog" aria-modal="true" tabindex="-1" x-show="isOpen">
        <div class="easystore-breakdown__backdrop"></div>
        <div class="easystore-breakdown__content" @click.outside="isOpen = false">
            <div class="easystore-breakdown__content-header">
                <p><?php echo Text::_('COM_EASYSTORE_TAX_BREAKDOWN_MODAL_HEADER'); ?></p>
                <button type="button" @click="isOpen = false">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M18.2903 3.20377C16.3594 5.16759 14.4256 7.1374 12.4678 9.1282C12.2099 9.38905 11.7962 9.39205 11.5383 9.1282C9.58951 7.14939 7.64968 5.17658 5.70685 3.20377C5.449 2.94292 5.03525 2.94592 4.78041 3.20676C4.30969 3.69247 3.84797 4.17518 3.36826 4.66988C3.11341 4.93373 3.1194 5.35947 3.37425 5.62031C5.26611 7.54516 7.20895 9.52097 9.17876 11.5268C9.43661 11.7876 9.43361 12.2103 9.17876 12.4742C7.22394 14.465 5.27511 16.4468 3.34127 18.4136C3.08642 18.6745 3.08642 19.0972 3.34727 19.3551L4.78041 20.7912C5.03825 21.049 5.458 21.049 5.71284 20.7882L11.5353 14.8727C11.7932 14.6119 12.2069 14.6119 12.4648 14.8727C14.4046 16.8456 16.3385 18.8124 18.2873 20.7942C18.5451 21.055 18.9619 21.058 19.2167 20.7972C19.6964 20.3115 20.1672 19.8318 20.6439 19.3491C20.9017 19.0882 20.9017 18.6625 20.6469 18.4016C18.734 16.4528 16.7942 14.477 14.8244 12.4712C14.5665 12.2104 14.5665 11.7846 14.8244 11.5238C16.7792 9.53296 18.725 7.55115 20.6559 5.58134C20.9137 5.32049 20.9107 4.89775 20.6529 4.6369L19.2197 3.19477C18.9649 2.93993 18.5481 2.94292 18.2903 3.20377Z"/></svg>
                </button>
                
            </div>
            <div class="easystore-breakdown__content-body">
                <?php foreach ($item->products as $product) : ?>
                    <?php $cartItem = $product->cart_item; ?>

                    <?php if ($cartItem->taxable_amount > 0) : ?>
                    <div class="easystore-breakdown__item">
                        <span><?php echo $product->title; ?></span>
                        <span><?php echo $cartItem->tax_rate . '%'; ?></span>
                        <span><?php echo $cartItem->taxable_amount_with_currency; ?></span>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if ($item->shipping_tax > 0) : ?>
                    <div class="easystore-breakdown__item">
                        <span><?php echo Text::_('COM_EASYSTORE_TAX_BREAKDOWN_SHIPPING') . '(' . ($item->shipping->name ?? '') . ')'; ?></span>
                        <span><?php echo $item->shipping_tax_rate . '%'; ?></span>
                        <span><?php echo $item->shipping_tax_with_currency; ?></span>
                    </div>
                <?php endif; ?>
                <div class="easystore-breakdown__item is-total">
                    <span><?php echo Text::_('COM_EASYSTORE_TAX_BREAKDOWN_TOTAL_TAX') ?></span>
                    <span></span>
                    <span><?php echo $item->taxable_amount_with_currency; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
