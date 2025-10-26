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

$show_label = $show_label ?? true;
?>

<div class="easystore-quantity-container">
    <?php if ($show_label) : ?>
        <div class="easystore-block-label"><?php echo Text::_('COM_EASYSTORE_QUANTITY'); ?></div>
    <?php endif; ?>
    <div class="easystore-quantity-selector">
        <button type="button" @click="decrement" class="easystore-quantity-selector-btn easystore-button-reset">-</button>
        <input type="number" class="form-control easystore-product-quantity" :value="quantity" x-model="quantity" min="1" placeholder="0">
        <button type="button" @click="increment" class="easystore-quantity-selector-btn easystore-button-reset">+</button>
    </div>
</div>
