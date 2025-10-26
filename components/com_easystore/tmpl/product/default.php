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

Text::script('COM_EASYSTORE_PRODUCT_ADD_TO_WISHLIST');
Text::script('COM_EASYSTORE_PRODUCT_ADDED_TO_WISHLIST');
Text::script('COM_EASYSTORE_PRODUCT_ADD_TO_WISHLIST_NO_USER');
Text::script('COM_EASYSTORE_PRODUCT_REVIEW_ADDED');
Text::script('COM_EASYSTORE_PRODUCT_REVIEW_CUSTOMER_REVIEWS');
Text::script('COM_EASYSTORE_PRODUCT_REVIEW_RATINGS');
Text::script('COM_EASYSTORE_PRODUCT_REVIEW_SELECT_RATING');

$item          = $this->item;
$firstImageSrc = $activeClass = '';
$icon          = 'fa fa-heart';
$text          = Text::_('COM_EASYSTORE_PRODUCT_ADD_TO_WISHLIST');

$isDefault = !$this->pageBuilderData;
?>

<div class="easystore-product-single" x-data="easystoreProductDetails">
    <?php if (!$isDefault) : ?>
        <?php $page = $this->pageBuilderData; ?>
        <?php $content = SppagebuilderHelperSite::initView($page); ?>
        <div class="sp-page-builder page-<?php echo $page->id; ?>">
            <div class="easystore-product-content page-content" id="easystore-product-detail-sppb">
                <?php echo AddonParser::viewAddons($content, 0, 'page-' . $page->id, 1, true, ['easystoreItem' => $this->item, 'easystoreList' => []]); ?>
            </div>
        </div>
    <?php else : ?>
        <div id="easystore-product-detail-default">
            <?php echo EasyStoreHelper::loadLayout('product.default', ['item' => $item]); ?>
        </div>
    <?php endif; ?>
</div>