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

// Required assets
EasyStoreHelper::wa()
    ->useStyle('com_easystore.site')
    ->useStyle('com_easystore.cart.site')
    ->useStyle('com_easystore.cart.drawer.site')
    ->useScript('com_easystore.alpine.site');
?>

<a class="easystore-cart-icon" x-data="easystore_cart" href="#" @click.prevent="$dispatch('drawer.open')" aria-label="<?php echo Text::_('COM_EASYSTORE_CART'); ?>" title="<?php echo Text::_('COM_EASYSTORE_CART'); ?>">
    <?php echo EasyStoreHelper::getIcon('cart'); ?>
    <span class="easystore-cart-count" x-cloak x-show="itemCount > 0" x-text="itemCount"></span>
</a>