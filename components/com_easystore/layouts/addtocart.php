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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Site\Helper\ProductStock;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

EasyStoreHelper::wa()->useStyle('com_easystore.site');

extract($displayData);

$app              = Factory::getApplication();
$input            = $app->input;
$option           = $input->get('option', '', 'STRING');
$optionView       = $input->get('view', '', 'STRING');
$componentContext = $option . '.' . $optionView;
$isEditor         = ($componentContext === 'com_sppagebuilder.form' || $componentContext === 'com_sppagebuilder.ajax');

$isListView  = $context === 'product-list';
$hasVariants = $item->has_variants && !empty($item->variants);

$layout     = $layout ?? 'text';
$showText   = $layout !== 'icon';
$showIcon   = $layout !== 'text';
$isQuickAdd = $isListView && $hasVariants;

$btnClass = $isQuickAdd ? 'btn-outline-primary' : 'btn-primary';
$btnIcon  = $isQuickAdd ? 'plus' : 'cart';

$textKey = '';

switch ($item->stock->status) {
    case ProductStock::IN_STOCK:
        $textKey = 'COM_EASYSTORE_PRODUCT_ADD_TO_CART';
        break;
    case ProductStock::OUT_OF_STOCK:
        $textKey = 'COM_EASYSTORE_PRODUCT_SOLD_OUT';
        break;
    case ProductStock::UNAVAILABLE:
        $textKey = 'COM_EASYSTORE_PRODUCT_UNAVAILABLE';
        break;
}

if ($isQuickAdd) {
    $textKey = 'COM_EASYSTORE_PRODUCT_QUICK_ADD_BUTTON';
}

$clickEvent = '';
$isDisabled = in_array($item->stock->status, [ProductStock::OUT_OF_STOCK, ProductStock::UNAVAILABLE], true);

if (!$isEditor) {
    if ($isQuickAdd) {
        $isDisabled = in_array($item->overallStock, [ProductStock::OUT_OF_STOCK, ProductStock::UNAVAILABLE], true);
        $clickEvent = '@click="openQuickAddModal(' . $item->id . ')"';
    } else {
        $productId = $item->id;
        $variantId = !empty($item->active_variant->id) ? $item->active_variant->id : 0;
        $params    = $item->id . ',' . $variantId;
        $clickEvent = '@click="addToCart(' . $params . ')"';
    }
}

$htmlAttributes = '';

if (!$showText) {
    $htmlAttributes .= 'title="' . Text::_($textKey) . '" ';
    $htmlAttributes .= 'aria-hidden="true" ';
    $htmlAttributes .= 'aria-labelby="' . Text::_($textKey) . '"';
}

?>

<button
    type="button"
    class="btn <?php echo $btnClass; ?> easystore-btn-add-to-cart"
    <?php echo $isDisabled ? 'disabled' : '' ?>
    <?php echo $clickEvent; ?>
    <?php echo 'add-to-cart-button-' . $item->id; ?>
    <?php echo !$showText ? 'title="' . Text::_($textKey) . '"' : ''; ?>
    <?php echo $htmlAttributes; ?>
>
    <?php if ($showIcon) : ?>
        <span class="easystore-btn-icon easystore-svg">
            <?php echo EasyStoreHelper::getIcon($btnIcon); ?>
        </span>
    <?php endif;?>
    <?php if ($showText) : ?>
        <span class="easystore-btn-text">
            <?php echo Text::_($textKey); ?>
        </span>
    <?php endif;?>
</button>
