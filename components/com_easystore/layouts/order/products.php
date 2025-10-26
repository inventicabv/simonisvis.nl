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
use JoomShaper\Component\EasyStore\Administrator\Supports\Shop;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

extract($displayData);

/** @var CMSApplication */
$app = Factory::getApplication();

$wa = $app->getDocument()->getWebAssetManager();
$wa->useScript('com_easystore.product.site');
$wa->useStyle('com_easystore.product.site');


?>
<div class="easystore-order-items mb-4">
<div class="easystore-order-item">
    <div class="row flex-grow-1 justify-content-between">
   
    <div class="col-1 easystore-custom-col"></div>
        <div class="col-4">
            <strong ><?php echo Text::_('COM_EASYSTORE_ORDER_ITEM') ?></strong>
        </div>
        <div class="col-1 d-flex justify-content-center">
            <strong class="m-0"><?php echo Text::_('COM_EASYSTORE_ORDER_PRODUCT_QUANTITY') ?></strong>
        </div>
        <div class="col-2 d-flex justify-content-center ">
            <strong><?php echo Text::_('COM_EASYSTORE_ORDER_PRODUCT_PRICE') ?></strong>
        </div>
        <div class="col-2 col-md-3 col-lg-4 d-flex justify-content-lg-end">
            <strong><?php echo Text::_('COM_EASYSTORE_ORDER_PRODUCT_PRICE_TOTAL') ?></strong>
        </div>
    </div>
</div>
    <?php $defaultThumbnailSrc = EasyStoreHelper::getPlaceholderImage();
    foreach ($products as $product) :
        $imageSrc          = !empty($product->image) ? $product->image : $defaultThumbnailSrc;
        $validVideoFormats = ['mp4', 'avi', 'mov', 'mkv', 'flv', 'wmv', 'webm'];
        $fileExtension     = pathinfo($imageSrc, PATHINFO_EXTENSION);

        $cartItem = $product->cart_item ?? null;
        $productPrice = !is_null($cartItem) ? $cartItem->final_price : null;

        ?>
        <div class="easystore-order-item">
            <div class="row flex-grow-1 justify-content-end">
            <div class="col-1 easystore-custom-col">
            <?php if (in_array($fileExtension, $validVideoFormats)) : ?>
                        <video class="easystore-product-thumbnail" src="<?php echo $imageSrc; ?>" alt="<?php echo $this->escape($product->title); ?>"></video>
            <?php else : ?>
                        <img class="easystore-product-thumbnail" src="<?php echo $imageSrc; ?>" alt="<?php echo $this->escape($product->title); ?>" />
            <?php endif; ?>
                
            </div>
                <div class="col-4 d-flex gap-3">
                   
                    <div>
                    <div class="easystore-order-item-title mb-2">
                        <strong><?php echo $product->title; ?></strong>
                    </div>

                    <div class="easystore-metadata-h">
                        <div class="easystore-metadata-item">
                            <span class="easystore-metadata-value"><?php echo $product->combination_name; ?></span>
                        </div>
                    </div>

                    <div class="easystore-metadata-h">
                        <div class="easystore-metadata-item">
                            <span class="easystore-metadata-value"><?php echo Text::sprintf('COM_EASYSTORE_PRODUCT_UNIT_WEIGHT', $product->weight_with_unit); ?></span>
                        </div>
                    </div>
                       

                    <?php if ($cartItem->is_coupon_applicable) : ?>
                    <div class="easystore-metadata-h easystore-small">
                        <div class="easystore-metadata-key">
                            <svg viewBox="0 0 14 14" width="14" height="14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.394 4.73a1.125 1.125 0 1 1-2.25 0 1.125 1.125 0 0 1 2.25 0Zm2.345 2.843-2.83 2.83-.251.251L7.58 12.73a1.515 1.515 0 0 1-1.636.33 1.465 1.465 0 0 1-.483-.327L1.264 8.541a1.465 1.465 0 0 1-.438-1.055A1.488 1.488 0 0 1 1.26 6.43l2.085-2.085.252-.252L6.419 1.27A1.497 1.497 0 0 1 7.484.828h4.19a1.505 1.505 0 0 1 1.5 1.5v4.19a1.489 1.489 0 0 1-.435 1.055ZM8.6 9.595l.252-.252 2.822-2.821V2.326H7.477L4.405 5.4 2.32 7.485l4.203 4.19 2.079-2.078-.002-.002Z" fill="currentColor"/></svg>
                            <span><?php echo $cartItem->applied_coupon->code ?? ''; ?></span>
                            <span>(<?php echo Shop::asNegative($productPrice->unit_discount_value_with_currency); ?>)</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($cartItem->is_coupon_applicable) : ?>
                    <div class="easystore-metadata-h easystore-small">
                        <span><?php echo $productPrice->unit_discounted_price_with_currency; ?></span>
                        <del><?php echo $productPrice->unit_product_price_with_currency; ?></del>
                    </div>
                    <?php else : ?>
                    <div class="easystore-metadata-h easystore-small">
                        <span><?php echo $productPrice->unit_product_price_with_currency; ?></span>
                    </div>
                    <?php endif; ?>
                    </div>
                    
                </div>

                <div class="col-1">
                    <div class="easystore-metadata">
                        <div class="easystore-metadata-item justify-content-center">
                            <span class="easystore-metadata-value"><?php echo $product->quantity; ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-2">
                    <div class="easystore-metadata">
                        <div class="easystore-metadata-item justify-content-center">
                            <?php if ($cartItem->is_coupon_applicable) : ?>
                                <span class="easystore-metadata-value"><?php echo $productPrice->unit_discounted_price_with_currency; ?></span>
                            <?php else : ?>
                                <span class="easystore-metadata-value"><?php echo $productPrice->unit_product_price_with_currency; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-2 col-md-3 col-lg-4 d-flex justify-content-lg-end">
                    <div class="easystore-metadata justify-content-start">
                        <div class="easystore-metadata-item d-flex flex-column align-items-end justify-content-start">
                            <?php if ($cartItem->is_coupon_applicable) : ?>
                                <span class="easystore-metadata-value"><?php echo $productPrice->total_discounted_price_with_currency; ?></span>
                            <?php else : ?>
                                <span class="easystore-metadata-value"><?php echo $productPrice->total_product_price_with_currency; ?></span>
                            <?php endif; ?>
                            <?php if ($item->is_tax_included_in_price && $cartItem->taxable_amount > 0) : ?>
                                <small class="text-muted text-end"><?php echo Text::sprintf('COM_EASYSTORE_PER_ITEM_TAX_AMOUNT', $cartItem->taxable_amount_with_currency); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
