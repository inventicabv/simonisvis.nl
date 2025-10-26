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
use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

extract($displayData);

$link = Uri::root() . urlencode($item->link);
$show_label = $show_label ?? true;
?>

<div class="easystore-social-share-container">
    <?php if ($show_label) : ?>
        <div class="easystore-block-label"><?php echo Text::_('COM_EASYSTORE_SHARE'); ?></div>
    <?php endif; ?>
    <ul class="easystore-social-share">
        <li class="easystore-share-facebook">
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $link; ?>" target="_blank" rel="noopener noreferrer"><?php echo EasyStoreHelper::getIcon('facebook'); ?></a>
        </li>
        
        <li class="easystore-share-twitter">
            <a href="https://twitter.com/intent/tweet?url=<?php echo $link; ?>&text=<?php echo urldecode($item->title); ?>" target="_blank" rel="noopener noreferrer"><?php echo EasyStoreHelper::getIcon('x'); ?></a>
        </li>
        
        <li class="easystore-share-pinterest">
            <a href="https://pinterest.com/pin/create/button/?url=<?php echo $link; ?>&media=<?php echo $item->media->thumbnail->src ?? ""; ?>&description=<?php echo $item->title; ?>" target="_blank" rel="noopener noreferrer"><?php echo EasyStoreHelper::getIcon('pinterest'); ?></a>
        </li>
    </ul>
</div>