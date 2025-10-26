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

<h1>Your Order Confirmation - Order #<?php echo  $order_id;?></h1>

<h3>Dear <?php echo  $customer_name;?>,</h3>

<p>We are thrilled to inform you that your payment for order #<?php echo  $order_id;?> has been successfully processed. Your purchase is now complete, and we are in the process of fulfilling your order.</p>

<h4>Here are the details of your order:</h4>

<p>Order Number: #<?php echo  $order_id;?></p>
<p>Order Date: <?php echo  $order_date;?></p>


<h3>Payment Details Info</h3>
<p>Payment Method: <?php echo  $payment_method;?></p>
<p>Payment Status: <?php echo  $payment_status;?></p>

<?php if ($payment_method === 'banktransfer') : ?>
<?php $bankTransferInfo = \JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper::getManualPaymentInfo('banktransfer'); ?>
<?php if (!empty($bankTransferInfo->payment_instruction) || !empty($bankTransferInfo->additional_information)) : ?>
<h3><?php echo Text::_('COM_EASYSTORE_BANK_TRANSFER_INSTRUCTIONS_TITLE'); ?></h3>
<?php if (!empty($bankTransferInfo->payment_instruction)) : ?>
<div>
    <?php echo str_replace(['{AMOUNT}', '{ORDER_ID}'], [$order_total, $order_id], $bankTransferInfo->payment_instruction); ?>
</div>
<?php endif; ?>
<?php if (!empty($bankTransferInfo->additional_information)) : ?>
<div>
    <?php echo $bankTransferInfo->additional_information; ?>
</div>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>

<p>Thank you for choosing us for your purchase. We greatly appreciate your trust and confidence in our products.</p>

<a href="<?php echo $order_link;?>" target="_blank" title="Order Details">Order Details</a>