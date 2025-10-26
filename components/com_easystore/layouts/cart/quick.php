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
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

extract($displayData);
$origin = $origin ?? 'single';
?>

<div class="easystore-quick-cart" x-data="easystoreProductDetails">
    <div class="row align-items-center">
        <div class="col-lg-6">
            <?php echo EasyStoreHelper::loadLayout('thumbnail', ['item' => $product, 'link' => true, 'origin' => 'single']); ?>
        </div>

        <div class="col-lg-6">
            <div class="easystore-quick-cart-content">
                <?php echo EasyStoreHelper::loadLayout('ratings', ['count' => $product->reviewData->rating, 'show_count' => true, 'review_count' => $product->reviewData->count, 'show_label' => true, 'origin' => 'single']); ?>
                <?php echo EasyStoreHelper::loadLayout('title', ['item' => $product, 'selector' => 'div', 'link' => true, 'origin' => 'single']); ?>
                <?php echo EasyStoreHelper::loadLayout('price', ['item' => $product, 'origin' => 'single']); ?>
                <?php echo EasyStoreHelper::loadLayout('variants', ['item' => $product, 'origin' => $origin]); ?>
                <?php echo EasyStoreHelper::loadLayout('quantity', ['item' => $product, 'origin' => 'single']); ?>
                <div class="d-flex easystore-quick-cart-actions">
                    <div>
                        <?php echo EasyStoreHelper::loadLayout('addtocart', ['item' => $product, 'context' => 'quick-add-modal', 'origin' => 'single']); ?>
                    </div>
                    <div class="ms-2">
                        <a class="btn btn-outline-primary" href="<?php echo $product->link; ?>"><?php echo Text::_('COM_EASYSTORE_DETAILS'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>