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

extract($displayData);

/** @var CMSApplication $app */
$app = Factory::getApplication();

$wa = $app->getDocument()->getWebAssetManager();
$wa->useScript('com_easystore.product.site');
$wa->useStyle('com_easystore.product.site');

?>
<div class="easystore-order-items">
    <div class="easystore-order-item">
        <div class="easystore-order-item-row d-flex">
            <div class="col-lg-1"></div>
            <div class="col-lg-5">
                <strong><?php echo Text::_('COM_EASYSTORE_ORDER_ITEM') ?></strong>
            </div>
            <div class="col-lg-2 d-flex justify-content-center">
                <strong class="m-0"><?php echo Text::_('COM_EASYSTORE_ORDER_PRODUCT_QUANTITY') ?></strong>
            </div>
            <div class="col-lg-2 d-flex justify-content-center">
                <strong><?php echo Text::_('COM_EASYSTORE_ORDER_PRODUCT_PRICE') ?></strong>
            </div>
            <div class="col-lg-2 d-flex justify-content-end">
                <strong><?php echo Text::_('COM_EASYSTORE_ORDER_PRODUCT_PRICE_TOTAL') ?></strong>
            </div>
        </div>
    </div>
    <?php foreach ($products as $product) : ?>
        <?php
            $cartItem = $product->cart_item ?? null;
            $productPrice = !is_null($cartItem) ? $cartItem->final_price : null;
        ?>
        <div class="easystore-order-item">
            <div class="easystore-order-item-row d-flex">
                <div class="col-lg-1">
                    <?php if (!empty($product->image)) :?>
                        <?php
                        $imageSrc          = !empty($product->image) ? $product->image : $defaultThumbnailSrc;
                        $validVideoFormats = array('mp4', 'avi', 'mov', 'mkv', 'flv', 'wmv', 'webm');
                        $fileExtension = pathinfo($product->image, PATHINFO_EXTENSION);
                        ?>
                        <?php if (in_array($fileExtension, $validVideoFormats)) : ?>
                            <video class="easystore-product-thumbnail" src="<?php echo $imageSrc; ?>" alt="<?php echo $this->escape($product->title); ?>"></video>
                        <?php else : ?>
                            <img class="easystore-product-thumbnail"  src="<?php echo $imageSrc; ?>" alt="<?php echo $this->escape($product->title); ?>">
                        <?php endif; ?>
                    <?php endif;?>
                </div>

                <div class="col-lg-5">
                    <div class="easystore-order-item-title mb-2">
                        <strong><?php echo $product->title; ?></strong>
                    </div>

                    <?php if (!empty($product->weight)) : ?>
                        <div class="easystore-metadata-h">
                            <div class="easystore-metadata-item">
                                <span class="easystore-metadata-value"><?php echo Text::sprintf('COM_EASYSTORE_PRODUCT_UNIT_WEIGHT', $product->weight_with_unit); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="easystore-metadata-h">
                        <div class="easystore-metadata-item">
                            <span class="easystore-metadata-value"><?php echo $product->combination_name; ?></span>
                        </div>
                    </div>
                </div>      

                <div class="col-lg-2">
                    <div class="easystore-metadata">
                        <div class="easystore-metadata-item justify-content-center">
                            <span class="easystore-metadata-value"><?php echo $product->quantity; ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-2">
                    <div class="easystore-metadata">
                        <div class="easystore-metadata-item justify-content-center">
                            <span class="easystore-metadata-value"><?php echo $productPrice->unit_product_price_with_currency; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-2">
                    <div class="easystore-metadata">
                        <div class="easystore-metadata-item justify-content-end">
                            <span class="easystore-metadata-value"><?php echo $productPrice->total_product_price_with_currency; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
