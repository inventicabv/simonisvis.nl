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
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;

extract($displayData);

$unit            = $item->stock->unit ? $item->stock->unit :  SettingsHelper::getSettings()->get('products.standardUnits.weight', 'kg');
$title           = isset($weight_title) && $weight_title ? $weight_title : Text::_('COM_EASYSTORE_PRODUCT_WEIGHT');
$weightSeparator = isset($separator) && $separator ? $separator : ':';
?>

<?php if (isset($item->stock->weight) && $item->stock->weight) : ?>
    <div class="easystore-product-weight">
        <span class="easystore-product-weight-title"><?php echo $title; ?></span>
        <span class="easystore-product-weight-title-value-separator"><?php echo $weightSeparator; ?></span>
        <span class="easystore-product-weight-value">
            <?php echo sprintf('%s %s', $item->stock->weight, $unit); ?>
        </span>
    </div>
<?php endif;?>