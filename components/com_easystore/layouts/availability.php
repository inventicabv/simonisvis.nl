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
use JoomShaper\Component\EasyStore\Site\Helper\ProductStock;


extract($displayData);

$show_icon = $show_icon ?? true;
$iconClass = '';

switch ($item->stock->status) {
    case ProductStock::OUT_OF_STOCK:
        $iconClass = 'easystore-no-stock';
        break;
    case ProductStock::UNAVAILABLE:
        $iconClass = 'easystore-product-unavailable';
        break;
    case ProductStock::IN_STOCK:
    default:
        $iconClass = 'easystore-available-stock';
        break;
}

?>
<div class="easystore-product-inventory">
    <?php if ($show_icon) : ?>
        <span class="easystore-product-inventory-icon <?php echo $iconClass; ?>" area-hidden="true"></span>
    <?php endif; ?>
    
    <?php if ($item->stock->status === ProductStock::OUT_OF_STOCK) : ?>
        <span class="easystore-product-inventory-label"><?php echo Text::_('COM_EASYSTORE_PRODUCT_OUT_OF_STOCK') ?></span>
    <?php endif; ?>
    
    <?php if ($item->stock->status === ProductStock::IN_STOCK && $item->stock->amount > 0) : ?>
        <span class="easystore-product-inventory-value"><?php echo Text::sprintf('COM_EASYSTORE_PRODUCT_IN_STOCK_WITH_AMOUNT', $item->stock->amount) ?></span>
    <?php endif; ?>
    
    <?php if ($item->stock->status === ProductStock::IN_STOCK && $item->stock->amount === 0) : ?>
        <span class="easystore-product-inventory-value"><?php echo Text::_('COM_EASYSTORE_PRODUCT_IN_STOCK') ?></span>
    <?php endif; ?>
    
    <?php if ($item->stock->status === ProductStock::UNAVAILABLE) : ?>
        <span class="easystore-product-unavailable"><?php echo Text::_('COM_EASYSTORE_PRODUCT_UNAVAILABLE') ?></span>
    <?php endif; ?>
</div>