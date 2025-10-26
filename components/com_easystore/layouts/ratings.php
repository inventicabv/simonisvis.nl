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

$star = EasyStoreHelper::getIcon('star');
$halfStar = EasyStoreHelper::getIcon('half-star');
$emptyStar = EasyStoreHelper::getIcon('empty-star');

$showCount = $show_count ?? $showCount;
$showLabel = $show_label ?? 1;

?>

<div class="easystore-ratings-container">
    <div class="easystore-rating-stars">
        <?php
        $fullStars = floor($count);

        echo str_repeat($star, $fullStars);  // Display filled stars

        if ($count - $fullStars >= 0.5) {  // Check for half star
            echo $halfStar;
            $fullStars++;  // Account for the half star in total stars count
        }

        echo str_repeat($emptyStar, 5 - $fullStars);  // Display unfilled stars
        ?>
    </div>
    <?php if ($showCount) : ?>
        <small class="easystore-rating-count">
            (<?php echo $showLabel ? Text::sprintf('COM_EASYSTORE_MANAGER_N_ITEM_REVIEWS', $review_count) : $review_count; ?>)
        </small>
    <?php elseif(!$showCount && $showStar) : ?>
        <small class="easystore-rating-count">
            (<?php echo $showLabel ? Text::sprintf('COM_EASYSTORE_MANAGER_N_ITEM_STAR', $count) : $count; ?>)
        </small>
    <?php endif; ?>
</div>
