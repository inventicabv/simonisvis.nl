<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

EasyStoreHelper::wa()
    ->useStyle('com_easystore.cart.drawer.site')
    ->useStyle('com_easystore.site');
?>

<div class="easystore-drawer-wrapper" x-data="cartDrawer" @drawer-clear.dot.window="clearCart" @drawer-open.dot.window="openDrawer" @drawer-close.dot.window="closeDrawer" :class="open ? 'open': ''"  tabindex="-1"  aria-modal="true" role="dialog" aria-live="true" aria-busy="false">
    <div class="easystore-drawer easystore-drawer-right" x-ref="drawerContainer">
        <div class="easystore-drawer-header">
            <span><?php echo Text::_('COM_EASYSTORE_CART'); ?></span>
            <button class="easystore-drawer-close-button" @click="$dispatch('drawer.close')">&times;</button>
        </div>
        <div class="easystore-drawer-content" x-ref="drawerContent"></div>
        <div class="easystore-drawer-footer" easystore-drawer-footer>
            <button class="easystore-cart-clear btn btn-danger" @click="$dispatch('drawer.clear')">
                <?php echo Text::_('COM_EASYSTORE_CART_CLEAR'); ?>
            </button>
            <a href="<?php echo Route::_('index.php?option=com_easystore&view=cart', false); ?>" class="easystore-cart-visit btn btn-primary">
                <?php echo Text::_('COM_EASYSTORE_CART_VISIT_CART'); ?>
            </a>
            <a :href="checkout_url" class="easystore-cart-checkout btn btn-secondary">
                <?php echo Text::_('COM_EASYSTORE_CART_FLOATING_CHECKOUT'); ?>
            </a>
        </div>
    </div>

    <div class="easystore-drawer-overlay" @click="$dispatch('drawer.close')"></div>
</div>
