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

extract($displayData);

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

$reviews = $item->reviews ?? [];
$totalNumOfRating = count((array) $reviews);
$averageRating = EasyStoreHelper::getAverageRating($item->id, $totalNumOfRating);
$ratingOnScale = '/5.0';
?>
<?php if ($totalNumOfRating) : ?>
    <div class="easystore-reviews-summary">
        <div class="easystore-summary-count">
            <span class="easystore-summary-value">
                <?php echo $averageRating; ?>
            </span>
            <span class="easystore-summary-total"><?php echo $ratingOnScale; ?></span>
        </div>

        <div class="easystore-summary-stars">          
            <?php echo LayoutHelper::render('ratings', ['count' => $averageRating, 'show_count' => false, 'showStar' => false]); ?>  
        </div>

        <div class="easystore-summary-content">
            <?php echo Text::sprintf('COM_EASYSTORE_REVIEW_SUMMARY', $averageRating, $totalNumOfRating); ?>
        </div>
    </div>
<?php endif; ?>