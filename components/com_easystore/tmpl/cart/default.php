<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Supports\Shop;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects
EasyStoreHelper::attachRequiredAssets();

EasyStoreHelper::wa()
    ->useStyle('com_easystore.site')
    ->useStyle('com_easystore.cart.site');

$page_title = $this->escape($this->params->get('page_heading', Text::_('COM_EASYSTORE_CART')));
$settings   = SettingsHelper::getSettings();
$shopPage   = $settings->get('products.shopPage', 'index.php');

?>

<div class="easystore-cart my-4" x-data="easystore_cart">
    <?php if (empty($this->item->items)): ?>
        <div class="text-center easystore-empty-cart">
            <h3 class="mb-3 easystore-h3"><?php echo Text::_('COM_EASYSTORE_CART_EMPTY') ?></h3>
            <a href="<?php echo Route::_($shopPage, false); ?>" class="btn btn-outline-primary"><?php echo Text::_('COM_EASYSTORE_CART_CONTINUE_SHOPPING') ?></a>
        </div>
    <?php else: ?>
        <?php if ($this->params->get('show_page_heading')): ?>
            <div class="page-header">
                <h1><?php echo $page_title; ?></h1>
            </div>
        <?php endif; ?>
        <table class="easystore-cart-table" x-show="!!item?.items?.length">
            <thead>
                <tr>
                    <th colspan="2" scope="col"><?php echo Text::_('COM_EASYSTORE_CART_PRODUCT'); ?></th>
                    <th scope="col"><?php echo Text::_('COM_EASYSTORE_CART_PRICE'); ?></th>
                    <th class="d-none d-lg-table-cell" scope="col"><?php echo Text::_('COM_EASYSTORE_CART_QUANTITY'); ?></th>
                    <th class="d-none d-lg-table-cell" scope="col"><?php echo Text::_('COM_EASYSTORE_CART_TOTAL'); ?></th>
                </tr>
            </thead>

            <?php if (!empty($this->item->items)): ?>
            <tbody>
                <template x-for="(cartItem, index) in item.items" :key="index">
                <tr class="easystore-cart-item">
                    <td class="easystore-cart-item-thumbnail">
                        <video x-show="cartItem.isVideo" :src="cartItem.image?.src ?? ''" :alt="cartItem.title" loading="lazy" width="72"></video>
                        <img x-show="!cartItem.isVideo" :src="cartItem.image?.src ?? ''" :alt="cartItem.title" loading="lazy" />
                    </td>
                    <td class="easystore-cart-item-info">
                        <h3 class="easystore-cart-item-title" x-text="cartItem.title"></h3>
                        <div class="easystore-metadata-h">
                            <template x-for="option in cartItem.options" :key="option.key">
                                <div class="easystore-metadata-item">
                                    <span class="easystore-metadata-key" x-text="option.key + ':'"></span>
                                    <span class="easystore-metadata-value" x-text="option.name"></span>
                                </div>
                            </template>
                            <template x-if="cartItem.weight > 0">
                                <div class="easystore-metadata-item">
                                    <span class="easystore-metadata-key"><?php echo Text::_('COM_EASYSTORE_PRODUCT_WEIGHT') ?>: </span>
                                    <span class="easystore-metadata-value" x-text="cartItem.weight_with_unit"></span>
                                </div>
                            </template>
                        </div>
                    </td>
                    <td class="easystore-cart-item-price">
                        <?php if (Shop::displayTaxPercentage()): ?>
                            <div class="easystore-cart-item-subtotal" x-text="cartItem.final_price.unit_product_price_with_tax_with_currency"></div>
                            <small class="easystore-price-with-tax" x-show="cartItem.tax_rate > 0"><?php echo Text::_('COM_EASYSTORE_PRICE_WITH_TAX_MINI_CART') ?> (<span x-text="cartItem.tax_rate"></span>%)</small>
                        <?php else: ?>
                            <div class="easystore-cart-item-subtotal" x-text="cartItem.final_price.unit_product_price_with_currency"></div>
                        <?php endif; ?>
                    </td>
                    <td class="easystore-cart-item-quantity">
                        <div class="easystore-cart-quantity-selector">
                            <div class="mb-1 easystore-quantity-selector">
                                <button type="button" @click="decrementQuantity(index)" class="easystore-quantity-selector-btn easystore-button-reset">-</button>
                                <input type="number" class="form-control form-control-sm easystore-product-quantity" :value="cartItem.quantity" @change="handleQuantityChange($event, index)" min="1" placeholder="0">
                                <button type="button"  @click="incrementQuantity(index)" class="easystore-quantity-selector-btn easystore-button-reset">+</button>
                            </div>

                            <div class="easystore-cart-remove-item">
                                <a href="#" @click.prevent="removeCartItem(index)">
                                    <?php echo Text::_('COM_EASYSTORE_CART_REMOVE'); ?>
                                </a>
                            </div>
                        </div>
                    </td>
                    <td class="easystore-cart-item-total d-none d-lg-table-cell">
                        <?php if (Shop::displayTaxPercentage()): ?>
                        <div class="mb-1 easystore-cart-item-subtotal fw-bold" x-text="cartItem.final_price.total_product_price_with_tax_with_currency"></div>
                        <?php else: ?>
                        <div class="mb-1 easystore-cart-item-subtotal fw-bold" x-text="cartItem.final_price.total_product_price_with_currency"></div>
                        <?php endif; ?>
                    </td>
                </tr>
                </template>
            </tbody>
            <?php endif; ?>
        </table>

        <div class="my-4 row">
            <div class="col-lg-4 ms-auto">
                <div class="easystore-cart-summary">
                    <div class="mb-2" x-show="!isLoading">
                    <?php if (Shop::displayTaxPercentage()): ?>
                        <strong><?php echo Text::_('COM_EASYSTORE_CART_SUBTOTAL'); ?>: </strong> <span x-text="item.sub_total_with_taxable_amount_with_currency"></span>
                        <small class="easystore-price-with-tax"><?php echo Text::_('COM_EASYSTORE_PRICE_WITH_TAX_MINI_CART') ?></small>
                        <?php else: ?>
                        <strong><?php echo Text::_('COM_EASYSTORE_CART_SUBTOTAL'); ?>: </strong> <span x-text="item.sub_total_with_currency"></span>
                        <?php endif; ?>
                    </div>

                    <template x-if="item.total_weight > 0">
                        <div class="mb-2" x-show="!isLoading">
                            <strong><?php echo Text::_('COM_EASYSTORE_CART_TOTAL_WEIGHT'); ?>: </strong> <span x-text="item.total_weight_with_unit"></span>
                        </div>
                    </template>

                    <?php if (!Shop::isPriceDisplayedWithTax()): ?>
                    <div class="mb-4 text-muted" x-show="!isLoading"><?php echo Text::_('COM_EASYSTORE_CART_TAX_SHIPPING'); ?></div>
                    <?php endif; ?>

                    <div class="easystore-skeleton-container" x-show="isLoading">
                        <span class="easystore-skeleton"></span>
                        <span class="easystore-skeleton"></span>
                    </div>

                    <?php echo LayoutHelper::render('checkout', ['token' => $this->getToken()]); ?>
                    <a class="btn btn-link d-block" href="<?php echo Route::_($shopPage, false); ?>"><?php echo Text::_('COM_EASYSTORE_CART_CONTINUE_SHOPPING'); ?></a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($this->item->cross_sells)): ?>
<div class="easystore-product-list" x-data="easystoreProductDetails">
    <h3 class="easystore-h3 mb-3"><?php echo Text::_('COM_EASYSTORE_CROSS_SELLS'); ?></h3>
    <div class="easystore-grid easystore-grid-4">
        <?php foreach ($this->item->cross_sells as $item): ?>
            <div class="easystore-grid-item">
                <?php echo LayoutHelper::render('products.item', ['item' => $item]); ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
