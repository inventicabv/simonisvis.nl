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

extract($displayData);

$skuSeparator = isset($separator) && $separator ? $separator : ':';
?>

<?php if (isset($item->stock->sku) && $item->stock->sku) : ?>
<div class="easystore-product-sku">
    <span class="easystore-product-sku-title"><?php echo Text::_('COM_EASYSTORE_PRODUCT_FIELD_SKU'); ?></span>
    <span class="easystore-product-sku-title-value-separator"><?php echo $skuSeparator; ?></span>
    <span class="easystore-product-sku-value"><?php echo $item->stock->sku; ?></span>
</div>
<?php endif; ?>