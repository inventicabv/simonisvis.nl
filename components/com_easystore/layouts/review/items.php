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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

extract($displayData);
?>

<div class="easystore-reviews">
    <?php if (!empty($item->reviews)) : ?>
        <?php foreach ($item->reviews as $key => $review) : ?>
            <div class="easystore-review-item">
                <div class="easystore-review-ratings">
                    <?php echo LayoutHelper::render('ratings', ['count' => $review->rating, 'review_count' => count($item->reviews), 'showCount' => false, 'showStar' => true]); ?>
                </div>

                <h4 class="easystore-review-title">
                    <?php echo $review->subject; ?>
                </h4>

                <div class="easystore-review-user">
                    <?php echo $review->user_name; ?> on <?php echo HTMLHelper::_('date', $review->created, 'DATE_FORMAT_LC3'); ?>
                </div>

                <?php if (!empty($review->review)) : ?>
                <div class="easystore-review-message">
                    <?php echo $review->review; ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else : ?>
        <p class="easystore-review-empty">
            <?php echo Text::_('COM_EASYSTORE_PRODUCT_REVIEW_NO_REVIEWS'); ?>
        </p>
    <?php endif; ?>
</div>