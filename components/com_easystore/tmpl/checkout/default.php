<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/** @var CMSApplication */
$app      = Factory::getApplication();
$document = $app->getDocument();

$wa = $document->getWebAssetManager();
$wa->useStyle('com_easystore.site')
    ->useStyle('com_easystore.checkout.site')
    ->useStyle('com_easystore.order.site');

$page_title = $this->escape($this->params->get('page_heading', Text::_('COM_EASYSTORE_CHECKOUT')));
?>

<?php if ($this->params->get('show_page_heading')) : ?>
    <div class="page-header">
        <h1><?php echo $page_title; ?></h1>
    </div>
<?php endif;?>

<form class="easystore-checkout-wrapper" x-ref="checkoutForm" x-data="easystore_checkout" @submit.prevent="onSubmitPayment">
    <div class="row mt-4">
        <div class="col-lg-7 col-xl-6">
            <div class="easystore-checkout-cart">
                <?php echo EasyStoreHelper::loadLayout('checkout.summary'); ?>
            </div>
        </div>

        <div class="col-lg-5 ms-auto">
            <div class="easystore-checkout-steps-wrapper d-flex flex-column gap-4" x-on:input="handleInputChange">
                <div class="easystore-checkout-step">
                    <?php echo EasyStoreHelper::loadLayout('checkout.contact'); ?>
                </div>

                <div class="easystore-checkout-step">
                    <?php echo EasyStoreHelper::loadLayout('checkout.information', ['address' => 'shipping', 'allow_guest_checkout' => $this->allowGuestCheckout]); ?>
                </div>


                <div class="easystore-checkout-step">
                    <?php echo EasyStoreHelper::loadLayout('checkout.customer-note') ?>
                </div>

                <div class="easystore-checkout-step">
                    <?php echo EasyStoreHelper::loadLayout('checkout.shipping') ?>
                </div>

                <div class="easystore-checkout-step">
                    <?php echo EasyStoreHelper::loadLayout('checkout.payment') ?>
                </div>

                <div class="easystore-checkout-step">
                    <?php echo EasyStoreHelper::loadLayout('checkout.information', ['address' => 'billing']) ?>
                </div>

                <div class="easystore-checkout-step">
                    <?php echo EasyStoreHelper::loadLayout('checkout.legal-information') ?>
                </div>

                <button class="btn btn-primary btn-lg w-100 mb-4" :class="(loading || loadingShipping) ? 'easystore-spinner' : ''" :disabled="isDisabledPayButton" >
                    <?php echo Text::_('COM_EASYSTORE_CHECKOUT_PAY_BUTTON'); ?>
                </button>
            </div>
        </div>
    </div>
</form>