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
<div>
    <?php if (!empty($name)) : ?>
        <div><?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_CUSTOMER_NAME')?> : <?php echo htmlspecialchars($name)?></div>
    <?php endif; ?>
    <?php if (!empty($address_1)) : ?>
        <div><?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_ADDRESS_LINE_1')?> : <?php echo htmlspecialchars($address_1)?></div>
    <?php endif; ?>
    <?php if (!empty($address_2)) : ?>
       <div><?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_ADDRESS_LINE_2')?> : <?php echo htmlspecialchars($address_2)?></div>
    <?php endif; ?>
    <?php if (!empty($city)) : ?>
        <div><?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_CITY')?> : <?php echo htmlspecialchars($city)?></div>
    <?php endif; ?>
    <?php if (!empty($state)) : ?>
        <div><?php echo Text::_('COM_EASYSTORE_ORDER_STATE')?> : <?php echo htmlspecialchars($state)?></div>
    <?php endif; ?>
    <?php if (!empty($zip_code)) : ?>
        <div><?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_ZIP_CODE')?> : <?php echo htmlspecialchars($zip_code)?></div>
    <?php endif; ?>
    <?php if (!empty($country)) : ?>
        <div><?php echo Text::_('COM_EASYSTORE_ORDER_COUNTRY')?> : <?php echo htmlspecialchars($country)?></div>
    <?php endif; ?>
    <?php if (!empty($phone)) : ?>
        <div><?php echo Text::_('COM_EASYSTORE_CHECKOUT_SHIPPING_CUSTOMER_NUMBER')?> : <?php echo htmlspecialchars($phone)?></div>
    <?php endif; ?>
</div>