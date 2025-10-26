<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

?>

<div class="easystore-product-single">
    <div class="easystore-product-single-top">
        <div class="row">
            <div class="col-lg-6">
                <?php echo EasyStoreHelper::loadLayout('thumbnail', ['item' => $item, 'origin' => 'single']); ?>
                <?php echo EasyStoreHelper::loadLayout('gallery', ['item' => $item, 'origin' => 'list']); ?>
            </div>

            <div class="col-lg-6 ps-4">
                <div class="easystore-product-content">
                    <?php echo EasyStoreHelper::loadLayout('category', ['item' => $item]); ?>
                    <?php echo EasyStoreHelper::loadLayout('ratings', ['count' => $item->reviewData->rating, 'show_count' => true, 'review_count' => $item->reviewData->count, 'show_label' => true]); ?>
                    <?php echo EasyStoreHelper::loadLayout('title', ['item' => $item]); ?>
                    <?php echo EasyStoreHelper::loadLayout('price', ['item' => $item, 'origin' => 'single']); ?>
                    <?php echo EasyStoreHelper::loadLayout('quantity', ['item' => $item]); ?>
                    <?php echo EasyStoreHelper::loadLayout('variants', ['item' => $item]); ?>
                    <?php echo EasyStoreHelper::loadLayout('availability', ['item' => $item]); ?>
                    <?php echo EasyStoreHelper::loadLayout('sku', ['item' => $item]); ?>
                    <?php echo EasyStoreHelper::loadLayout('weight', ['item' => $item]); ?>
                    <?php echo EasyStoreHelper::loadLayout('dimension', ['item' => $item]);?>
                    <?php echo EasyStoreHelper::loadLayout('addtocart', ['item' => $item, 'context' => 'product-details']); ?>
                    <?php echo EasyStoreHelper::loadLayout('addtowishlist', ['item' => $item]); ?>
                    <?php echo EasyStoreHelper::loadLayout('tags', ['item' => $item]); ?>
                    <?php echo EasyStoreHelper::loadLayout('social', ['item' => $item]); ?>
                    <?php echo EasyStoreHelper::loadLayout('description', ['item' => $item]); ?>
                </div>
            </div>
        </div>
    </div>

    <?php echo EasyStoreHelper::loadLayout('review', ['item' => $item]); ?>
</div>