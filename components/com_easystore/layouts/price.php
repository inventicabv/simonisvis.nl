<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Administrator\Supports\Shop;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

$text = Text::sprintf("COM_EASYSTORE_PRICE_WITH_TAX", $item->tax_rate . '%');

if (!empty($custom_text)) {
    $text = sprintf($custom_text, $item->tax_rate . '%');
}

$taxRate = !empty($item->has_variants) && !empty($item->active_variant) ? $item->active_variant->tax_rate : $item->tax_rate;

?>

<div class="easystore-product-price">
    <div class="easystore-product-price-block">
    <?php if ($item->has_sale && (float) $item->discount_value > 0) : ?>
        <span class="easystore-price-current" aria-hidden="true">
            <?php echo EasyStoreHelper::loadLayout('price_segments', ['segments' => $item->prices->discounted_price_with_segments]); ?>
        </span>
        <span class="easystore-visually-hidden"><?php echo $item->prices->discounted_price_with_currency; ?></span>
        <span class="easystore-price-original" aria-hidden="true">
            <?php echo EasyStoreHelper::loadLayout('price_segments', ['segments' => $item->prices->price_with_segments]); ?>
        </span>
    <?php else : ?>
        <span class="easystore-price-current" aria-hidden="true">
            <?php echo EasyStoreHelper::loadLayout('price_segments', ['segments' => $item->prices->price_with_segments]); ?>
        </span>
    <?php endif; ?>
        <span class="easystore-visually-hidden"><?php echo $item->prices->price_with_currency; ?></span>
    </div>
    <?php if (Shop::displayTaxPercentage() && $taxRate > 0) : ?>
        <div class="easystore-product-taxable-price-status"><?php echo $text; ?> </div>
    <?php endif; ?>
</div>
