<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Layout\LayoutHelper;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

 // phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

EasyStoreHelper::attachRequiredAssets();

$pagination = isset($this->pagination->pagesTotal) && $this->pagination->pagesTotal > 1 ? $this->pagination : null;
?>

<?php if ($this->pageBuilderData) : ?>
    <?php $page = $this->pageBuilderData; ?>
    <?php $content = SppagebuilderHelperSite::initView($page); ?>
    <div class="sp-page-builder page-<?php echo $page->id; ?>" x-data="easystoreProductList">
        <div class="easystore-product-content page-content">
            <?php echo AddonParser::viewAddons($content, 0, 'page-' . $page->id, 1, true, ['easystoreItem' => null, 'easystoreList' => [], 'easystorePagination' => []]); ?>
        </div>
    </div>
<?php else : ?>
    <div class="easystore-product-list" x-data="easystoreProductDetails">
        <?php if (!empty($this->items->products)) : ?>
            <div class="easystore-grid easystore-grid-4">
                <?php foreach ($this->items->products as $item) : ?>
                    <div class="easystore-grid-item">
                        <?php echo LayoutHelper::render('products.item', ['item' => $item]); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($this->pagination->pagesTotal > 1) :  ?>
            <div class="mt-5">
                <?php echo LayoutHelper::render('products.pagination', ['pagination' => $this->pagination]); ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>