<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

?>

<?php if (count((array) $item->tags)) : ?>
<ul class="easystore-product-tags list-inline">
    <?php foreach ($item->tags as $tag) : ?>
    <li class="list-inline-item easystore-product-tag-<?php echo $tag->id;?>">
        <a href="<?php echo Route::_('index.php?option=com_easystore&view=products&filter_tags=' . $tag->alias)?>" title="<?php echo $this->escape(ucfirst($tag->title) ?? ''); ?>">
        <?php echo $this->escape(ucfirst($tag->title) ?? ''); ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>