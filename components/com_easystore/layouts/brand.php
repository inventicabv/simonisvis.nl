<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

?>
<?php if (!empty($item->brand_title)) : ?>
<div class="easystore-product-brands list-inline">
    <div class="list-inline-item easystore-product-brand-<?php echo $item->brand_id; ?>">
        <?php if ($show_brand_image): ?>
            <?php $image = EasyStoreHelper::getBrandImage($item->brand_id) ?? EasyStoreHelper::getPlaceholderImage(); ?>
            <?php if ($show_brand_link): ?>
            <a href="<?php echo Route::_('index.php?option=com_easystore&view=products&filter_brands=' . $item->brand_alias) ?>" title="<?php echo $this->escape(ucfirst($item->brand_title ?? '') ?? ''); ?>" class="easystore-product-brand-image-link">
            <?php endif; ?>
                <img src="<?php echo $image; ?>" class="easystore-product-brand-image" alt="<?php echo $this->escape(ucfirst($item->brand_title ?? '') ?? ''); ?>" class="img-fluid" />
            <?php if ($show_brand_link): ?>
            </a>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($show_brand_name): ?>
            <div class="easystore-product-brand-name">
                <?php if ($show_brand_label): ?>
                <span class="easystore-product-brand-name-label"><strong><?php echo Text::_('COM_EASYSTORE_BRAND'); ?></strong></span>
                <?php endif; ?>
                <?php if ($show_brand_link): ?>
                    <a href="<?php echo Route::_('index.php?option=com_easystore&view=products&filter_brands=' . $item->brand_alias) ?>" title="<?php echo $this->escape(ucfirst($item->brand_title ?? '') ?? ''); ?>" class="easystore-product-brand-name-link">
                <?php endif; ?>
                
                    <span class="easystore-product-brand-name-text">
                        <?php echo $this->escape(ucfirst($item->brand_title ?? '') ?? ''); ?>
                    </span>
                <?php if ($show_brand_link): ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>