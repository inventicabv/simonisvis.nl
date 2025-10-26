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

extract($displayData);

$paymentConfig = [
    'banktransfer' => [
        'key'      => 'banktransfer',
        'title'    => 'COM_EASYSTORE_BANK_TRANSFER_INSTRUCTIONS_TITLE',
    ],
    'cheque' => [
        'key'      => 'cheque',
        'title'    => 'COM_EASYSTORE_PAYMENT_CHEQUE_INSTRUCTIONS_TITLE',
    ],
    'custom' => [
        'key'      => 'custom',
        'title'    => 'COM_EASYSTORE_PAYMENT_CUSTOM_INSTRUCTIONS_TITLE',
    ],
];

$method = $item->payment_method ?? '';
?>
<?php if (in_array($method, array_keys($paymentConfig))) : ?>
   <?php $config = $paymentConfig[$method]; ?>
   <?php $info = EasyStoreHelper::getManualPaymentInfo($config['key']); ?>

    <?php if (!empty($info->payment_instruction) || !empty($info->additional_information)) : ?>
        <div class="easystore-card easystore-card-border my-4">
            <div class="easystore-card-body">
                <?php if (!empty($info->payment_instruction)) : ?>
                    <h5><?php echo Text::_($config['title']); ?></h5>
                    <div><?php echo $info->payment_instruction; ?></div>
                <?php endif; ?>

                <?php if (!empty($info->additional_information)) : ?>
                    <div class="mt-2"><?php echo $info->additional_information; ?></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif;?>
<?php endif;?>