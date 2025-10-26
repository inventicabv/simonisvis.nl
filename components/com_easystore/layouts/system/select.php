<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);
$showTitle = $settings->show_title ?? 1;
$input     = Factory::getApplication()->input;
$initial   = $initial ?? array_key_first((array) $options);
$selected  = $input->get('filter_' . $key, $initial, 'STRING');
?>

<div class="easystore-product-filter" easystore-filter-sort>
    <?php if ($showTitle) : ?>
        <div class="easystore-filter-header">
            <h4 class="easystore-filter-title easystore-h4"><?php echo $settings->title ?? Text::_('COM_EASYSTORE_PRICE_RANGE'); ?></h4>
            <span easystore-filter-reset><?php echo Text::_('COM_EASYSTORE_FILTER_RESET'); ?></span>
        </div>
    <?php endif;?>

    <select class="easystore-form-select form-select custom-select" name="filter_sortby" easystore-sort-by>
        <?php foreach ($options as $option) : ?>
            <option value="<?php echo $option->value; ?>" <?php echo $option->value === $selected ? 'selected="selected"' : ''; ?> ><?php echo $option->name; ?></option>
        <?php endforeach;?>
    </select>
</div>