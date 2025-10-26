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

$unit               = SettingsHelper::getSettings()->get('products.standardUnits.dimension', 'm');
$title              = isset($dimension_title) && $dimension_title ? $dimension_title : Text::_('COM_EASYSTORE_PRODUCT_FIELD_DIMENSION');
$dimensionSeparator = isset($separator) && $separator ? $separator : ':';

?>

<?php if (isset($item->dimension) && $item->dimension) : ?>
    <div class="easystore-product-dimension">
        <span class="easystore-product-dimension-title">
            <?php echo $title; ?>
        </span>
        <span class="easystore-product-dimension-title-value-separator">
            <?php echo $dimensionSeparator; ?>
        </span>
        <span class="easystore-product-dimension-value">
            <?php echo sprintf('%s %s', $item->dimension, $unit); ?>
        </span>
    </div>
<?php endif;?>