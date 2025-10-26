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

extract($displayData);
?>
<h3>Shipping Carrier Added</h3>

<h3>Dear Customer,</h3>

<p>We are excited to inform you that a shipping carrier has been added to your order. Here are the details: </p>

<ul>
    <li>Order Number: <?php echo $order_id; ?></li>
    <li>Shipping Carrier: <?php echo $shipping_carrier; ?></li>
</ul>

<p>You can track your order's status by clicking the following link:</p>
<p>Track Your Order: <a href="<?php echo $tracking_url; ?>" target="_blank" rel="noreferrer noopener"><?php echo $tracking_url; ?></a></p>

<p>If you have any questions or need assistance, please feel free to contact our customer support.</p>