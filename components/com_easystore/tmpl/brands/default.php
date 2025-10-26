<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

 // phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

EasyStoreHelper::attachRequiredAssets();

$columnCount = $this->params->get('easystore_columns_count', 4);
$columnClass = 'easystore-grid-' . $columnCount;

?>

<div class="easystore-brand-list">
    <?php if (!empty($this->items)) : ?>
        <div class="easystore-grid easystore-brand-grid <?php echo $columnClass; ?>">
            <?php foreach ($this->items as $item) : ?>
            <?php 
                $image = EasyStoreHelper::getBrandImage($item->id) ?? EasyStoreHelper::getPlaceholderImage();
                $link = EasyStoreHelper::getBrandLink($item->id) ?? '#';
                $productCount = EasyStoreHelper::getBrandProductCount($item->id);
            ?>
                <div class="easystore-grid-item">
                    <div class="easystore-brand">
                        <?php if ($this->params->get('show_brand_image')) : ?>
                        <?php if ($this->params->get('show_brand_image_link')) : ?>
                            <a href="<?php echo $link; ?>" class="easystore-brand-image-link">
                        <?php endif; ?>
                                <div class="easystore-brand-image-wrapper">
                                    <img src="<?php echo $image; ?>" alt="<?php echo $item->title; ?>" class="easystore-brand-image" loading="lazy" />
                                </div>
                        <?php if ($this->params->get('show_brand_image_link')) : ?>
                            </a>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="easystore-brand-content">
                            <?php if ($this->params->get('show_brand_title')) : ?>
                                <div class="easystore-brand-name">
                                    <?php if ($this->params->get('show_brand_title_link')) : ?>
                                        <a href="<?php echo $link; ?>" class="easystore-brand-title-link">
                                            <?php echo $item->title; ?>
                                        </a>
                                    <?php else : ?>
                                        <span class="easystore-brand-title">
                                            <?php echo $item->title; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($this->params->get('show_brand_product_count')) :?>
                            <?php if ($productCount > 0) : ?>
                                <div class="easystore-brand-product-count">
                                    <?php echo Text::plural('COM_EASYSTORE_N_ITEMS_BRAND_PRODUCT_COUNT', $productCount); ?>
                                </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($this->pagination->pagesTotal > 1) :  ?>
            <div class="mt-5 easystore-pagination">
                <?php echo $this->pagination->getPagesLinks(); ?>
            </div>
    <?php endif; ?>
</div>