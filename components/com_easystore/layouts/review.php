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

use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

extract($displayData);

$link = urlencode($item->link);
$show_label = $show_label ?? true;
?>

<div class="easystore-review-container">
    <div class="row">
        <div class="col-lg-3">
            <?php echo EasyStoreHelper::loadLayout('review.summary', ['item' => $item]); ?>
            <?php echo EasyStoreHelper::loadLayout('review.form', ['item' => $item]); ?>
        </div>
    
        <div class="col-lg-9">
            <?php echo EasyStoreHelper::loadLayout('review.items', ['item' => $item]); ?>
        </div>
    </div>
</div>
