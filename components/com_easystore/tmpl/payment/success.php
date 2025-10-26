<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

 // phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

?>

<div class="easystore-page-wrapper">
    <svg viewBox="0 0 121 112" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M47 91c24.3 0 44-19.7 44-44S71.3 3 47 3 3 22.7 3 47s19.7 44 44 44Z" fill="#E9FCE4" />
        <path d="m31 46.406 10.707 11.046c.26.268.69.266.949-.004l20.272-21.237" stroke="#4FC67F" stroke-width="6" stroke-linecap="round" />
        <path d="M98.874 74.586H53.818v33.408h45.056V74.586Z" fill="#FFC571" />
        <path
            d="M64.97 106.242a.178.178 0 0 1-.175-.176v-29.53c0-.097.08-.177.176-.177.096 0 .176.08.176.176v29.531c0 .096-.08.176-.176.176ZM85.084 106.236a.177.177 0 0 1-.176-.176V86.41c0-.096.08-.176.176-.176.096 0 .176.08.176.176v19.65a.172.172 0 0 1-.176.176Z"
            fill="#EDA64A" />
        <path
            d="M89.36 85.396a.196.196 0 0 1-.127-.048l-2.19-2.19-1.816 1.814a.184.184 0 0 1-.247 0L83.1 83.094l-2.166 2.166a.188.188 0 0 1-.192.04.174.174 0 0 1-.112-.16V75.027c0-.096.08-.175.176-.175.096 0 .176.08.176.175v9.681l1.99-1.99a.184.184 0 0 1 .248 0l1.879 1.879 1.814-1.815a.184.184 0 0 1 .248 0l2.015 2.014v-9.769c0-.096.08-.175.175-.175.096 0 .176.08.176.175V85.22c0 .072-.04.136-.112.16-.016.016-.04.016-.055.016Z"
            fill="#F7F1EB" />
        <path d="M95.189 52.992H66.082v21.584h29.107V52.992Z" fill="#EDA64A" />
        <path
            d="M73.285 73.504a.177.177 0 0 1-.176-.176V54.246c0-.096.08-.176.176-.176.096 0 .176.08.176.176V73.32a.18.18 0 0 1-.176.184ZM86.283 73.507a.177.177 0 0 1-.176-.175V60.637c0-.096.08-.176.176-.176.096 0 .176.08.176.176v12.687a.18.18 0 0 1-.176.183Z"
            fill="#FFC571" />
        <path
            d="M89.041 60.048a.196.196 0 0 1-.127-.048l-1.368-1.375-1.127 1.127a.184.184 0 0 1-.247 0l-1.168-1.167-1.35 1.36a.188.188 0 0 1-.192.04.174.174 0 0 1-.112-.16v-6.532c0-.096.08-.176.175-.176.096 0 .176.08.176.176v6.108l1.175-1.176a.184.184 0 0 1 .248 0l1.168 1.168 1.127-1.128a.184.184 0 0 1 .248 0l1.19 1.192v-6.164c0-.096.08-.176.177-.176.095 0 .175.08.175.176v6.587c0 .072-.04.136-.112.16-.008 0-.032.008-.055.008Z"
            fill="#F7F1EB" />
        <path d="M73.795 108.28h43.704l-6.819-37.749H82.293l-8.498 37.749Z" fill="#807A47" />
        <path
            d="M92.181 105.215a.171.171 0 0 1-.168-.136c-1.31-5.5-7.85-32.976-7.762-33.472a.18.18 0 0 1 .208-.144.177.177 0 0 1 .144.192c.032.784 5.084 22.136 7.754 33.344a.176.176 0 0 1-.128.216h-.048ZM75.778 106.871a.171.171 0 0 1-.152-.088.179.179 0 0 1 .064-.24l7.259-4.125V72.52c0-.096.08-.176.176-.176.096 0 .175.08.175.176v30.002c0 .064-.031.12-.087.152l-7.355 4.173c-.016.016-.048.024-.08.024Z"
            fill="#F7F1EB" />
        <path
            d="M89.384 72.93a.498.498 0 0 0 .496-.495v-7.187a3.05 3.05 0 0 1 3.046-3.046h6.027A3.05 3.05 0 0 1 102 65.248v7.187c0 .272.224.495.496.495a.497.497 0 0 0 .495-.495v-7.187a4.04 4.04 0 0 0-4.037-4.037h-6.027a4.041 4.041 0 0 0-4.037 4.037v7.187c0 .272.224.495.495.495Z"
            fill="#827637" />
    </svg>
    <div class="page-content">
        <h3><?php echo Text::_('COM_EASYSTORE_PAYMENT_SUCCESS_PAGE_TITLE'); ?></h3>
        <p><?php echo Text::_('COM_EASYSTORE_PAYMENT_SUCCESS_PAGE_SUBTITLE'); ?></p>
        <?php if (isset($this->manualPaymentData)) : ?>
            <p>
                <?php echo EasyStoreHelper::loadLayout('payment.payment_instructions', ['manual_payment' => $this->manualPaymentData]); ?>
            </p>            
        <?php endif; ?>
    </div>

    <div class=" page-content-footer">
        <a href="<?php echo $this->continueShoppingUrl; ?>" class="btn btn-primary"><?php echo Text::_('COM_EASYSTORE_PAYMENT_SUCCESS_PAGE_CONTINUE_SHOPPING'); ?></a>

        <?php if (!$this->isGuestUser) : ?>
            <a href="<?php echo $this->orderHistoryUrl; ?>" class="btn btn-outline"><?php echo Text::_('COM_EASYSTORE_PAYMENT_SUCCESS_PAGE_MY_ORDERS'); ?></a>
        <?php else : ?>
            <a href="<?php echo $this->orderDetails; ?>" class="btn btn-primary"><?php echo Text::_('COM_EASYSTORE_PAYMENT_SUCCESS_PAGE_MY_ORDER'); ?></a>
        <?php endif; ?>

    </div>
</div>