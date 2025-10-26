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

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

extract($displayData);

$canReview  = $item->currentUser->id && $item->canReview && !$item->hasGivenReview;
$return_url = base64_encode(Uri::getInstance());
?>

<?php if ($item->currentUser->guest) : ?>
<a href="<?php echo Route::_('index.php?option=com_users&view=login&return=' . $return_url, false); ?>" class="btn btn-outline-primary easystore-btn-review-form"
    <?php echo $canReview ? ' easystore-review-form-handler' : ''; ?>>
    <?php echo Text::_('COM_EASYSTORE_REVIEW_WRITE'); ?>
</a>
<?php endif; ?>

<?php if ($canReview || ($item->currentUser->id && !$item->hasGivenReview)) : ?>
<div x-data="{ open: false }">
    <button class="btn btn-outline-primary easystore-btn-review-form" @click="open = !open">
        <?php echo Text::_('COM_EASYSTORE_REVIEW_WRITE'); ?>
    </button>
    <form name="reviewForm" id="reviewForm" x-show="open" x-transition x-data="easystore_product_review({productId: <?php echo $item->id; ?>})" @submit.prevent="submitReview">
        <div class="form-group">
            <label for="easystore-title"><?php echo Text::_('JFIELD_MEDIA_TITLE_LABEL'); ?></label>
            <input type="text" class="form-control" required placeholder="<?php echo Text::_('COM_EASYSTORE_REVIEW_TITLE_PLACE_HOLDER'); ?>" x-model="data.title">
        </div>

        <div class="form-group">
            <label for="easystore-ratings"><?php echo Text::_('COM_EASYSTORE_PRODUCT_REVIEW_RATINGS'); ?></label>
            <select class="form-control" x-model="data.ratings" required>
                <option value="">
                    <?php echo Text::_('COM_EASYSTORE_PRODUCT_REVIEW_SELECT_RATING'); ?>
                </option>
                <option value="1"><?php echo Text::_('COM_EASYSTORE_REVIEW_RATING_ONE_STAR'); ?></option>
                <option value="2"><?php echo Text::_('COM_EASYSTORE_REVIEW_RATING_TWO_STAR'); ?></option>
                <option value="3"><?php echo Text::_('COM_EASYSTORE_REVIEW_RATING_THREE_STAR'); ?></option>
                <option value="4"><?php echo Text::_('COM_EASYSTORE_REVIEW_RATING_FOUR_STAR'); ?></option>
                <option value="5"><?php echo Text::_('COM_EASYSTORE_REVIEW_RATING_FIVE_STAR'); ?></option>
            </select>
        </div>

        <div class="form-group">
            <label for="easystore-message"><?php echo Text::_('COM_EASYSTORE_REVIEW_MESSAGE'); ?>:</label>
            <textarea class="form-control" rows="4" placeholder="<?php echo Text::_('COM_EASYSTORE_REVIEW_MESSAGE_PLACEHOLDER') ?>" x-model="data.message"></textarea>
        </div>

        <button type="submit" class="btn btn-primary" easystore-add-review><?php echo Text::_('JSUBMIT'); ?></button>
    </form>
</div>
<?php endif;
