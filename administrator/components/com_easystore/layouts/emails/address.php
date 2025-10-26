<?php

/**
 * @package     EasyStore.Administrator
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
<div>
    <?php if (!empty($name)) : ?>
        <p><?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_CUSTOMER_NAME')?> : <?php echo htmlspecialchars($name)?></p>
    <?php endif; ?>
    <?php if (!empty($address_1)) : ?>
        <p><?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_ADDRESS_LINE_1')?> : <?php echo htmlspecialchars($address_1)?></p>
    <?php endif; ?>
    <?php if (!empty($address_2)) : ?>
       <p><?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_ADDRESS_LINE_2')?> : <?php echo htmlspecialchars($address_2)?></p>
    <?php endif; ?>
    <?php if (!empty($city)) : ?>
        <p><?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_CITY')?> : <?php echo htmlspecialchars($city)?></p>
    <?php endif; ?>
    <?php if (!empty($state)) : ?>
        <p><?php echo Text::_('COM_EASYSTORE_ORDER_STATE')?> : <?php echo htmlspecialchars($state)?></p>
    <?php endif; ?>
    <?php if (!empty($zip_code)) : ?>
        <p><?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_ZIP_CODE')?> : <?php echo htmlspecialchars($zip_code)?></p>
    <?php endif; ?>
    <?php if (!empty($country)) : ?>
        <p><?php echo Text::_('COM_EASYSTORE_ORDER_COUNTRY')?> : <?php echo htmlspecialchars($country)?></p>
    <?php endif; ?>
    <?php if (!empty($phone)) : ?>
        <p><?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_CUSTOMER_NUMBER')?> : <?php echo htmlspecialchars($phone)?></p>
    <?php endif; ?>
</div>