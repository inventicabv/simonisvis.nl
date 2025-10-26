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

use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

extract($displayData);
?>
<div class="easystore-product-item">
    <?php echo EasyStoreHelper::loadLayout('thumbnail', ['item' => $item, 'link' => true]); ?>
    <?php echo EasyStoreHelper::loadLayout('price', ['item' => $item]); ?>
    <?php echo EasyStoreHelper::loadLayout('title', ['item' => $item, 'selector' => 'h3', 'link' => true]); ?>
    <?php echo EasyStoreHelper::loadLayout('addtocart', ['item' => $item, 'context' => 'product-list']); ?>
</div>