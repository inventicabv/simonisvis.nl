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

$isActive    = $item->inWishList ?? false;
$activeClass = ' active';
$icon = $isActive ? 'heart' : 'heart-o';
$text = $isActive ? 'COM_EASYSTORE_PRODUCT_ADDED_TO_WISHLIST' : 'COM_EASYSTORE_PRODUCT_ADD_TO_WISHLIST';

$layout   = $layout ?? 'text';
$showText = $layout !== 'icon';
$showIcon = $layout !== 'text';

$isWishlist = $isWishlist ?? false;

if ($isWishlist) {
    $text = $isActive ? 'COM_EASYSTORE_PRODUCT_DELETE_FROM_WISHLIST' : 'COM_EASYSTORE_PRODUCT_ADD_TO_WISHLIST';
}

$initialData = json_encode([
    'productId'     => $item->id,
    'hasText'       => $showText,
    'hasIcon'       => $showIcon,
    'isWishlist'    => $isWishlist,
]);
?>

<button type="button" x-data="easyStoreWishlist"
    @click='addToWishList(<?php echo htmlspecialchars($initialData); ?>)'
    class="btn btn-outline-secondary easystore-btn-add-to-wishlist <?php echo $isActive ? $activeClass : ''; ?>"
    :class="`${isLoading ? 'easystore-spinner': ''}`"
>
    <?php if ($showIcon) : ?>
        <span x-ref="easystoreWishlistIcon" class="easystore-btn-icon easystore-svg">
            <?php echo EasyStoreHelper::getIcon($icon); ?>
        </span>
    <?php endif;?>

    <?php if ($showText) : ?>
        <span x-ref="easystoreWishlistText" class="easystore-btn-text">
            <?php echo Text::_($text); ?>
        </span>
    <?php endif;?>
</button>