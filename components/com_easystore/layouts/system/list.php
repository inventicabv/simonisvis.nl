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
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

$app           = Factory::getApplication();
$selectedItems = $app->input->get('filter_' . $filter, '', 'STRING');
$selectedItems = ($selectedItems) ? explode(',', $selectedItems) : [];
$type          = $type ?? 'checkbox';
$showTitle     = $settings->show_title ?? 1;
$showCount     = $settings->show_count ?? 1;
$enableSingleSelection = $settings->enable_single_selection ?? 0;

if ($enableSingleSelection) {
    $type = 'radio';
}
?>

<?php if (!empty($options)) : ?>
<div class="easystore-product-filter" data-easystore-filter-by="filter_<?php echo $filter; ?>">
    <?php if ($showTitle) : ?>
    <div class="easystore-filter-header">
        <div class="easystore-filter-title-wrapper">
            <?php echo EasyStoreHelper::getIcon('caret'); ?>
            <h4 class="easystore-filter-title easystore-h4"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h4>
        </div>
        <span easystore-filter-reset><?php echo Text::_('COM_EASYSTORE_FILTER_RESET'); ?></span>
    </div>
    <?php endif;?>
    <ul class="easystore-filter-list">
        <?php foreach ($options as $option) : ?>
            <?php $field_name = 'filter_' . $filter . ($type == 'checkbox' ? '[]' : '');?>
            <?php
            $optionType = isset($option->type) && $option->type == 'color' ? 'color' : 'item';
            $hexCode    = $optionType == 'color' ? $option->hex_code : '';
            ?>
            <li class="easystore-filter-list-<?php echo $optionType; ?>">
                <label for="filter_<?php echo $key; ?>_<?php echo $option->value; ?>" class="easystore-checkbox-label">
                    <input <?php echo EasyStoreHelper::isChecked($option->value, $selectedItems); ?> type="<?php echo $this->escape($type); ?>" id="filter_<?php echo $key; ?>_<?php echo $option->value; ?>" name="<?php echo $field_name; ?>" value="<?php echo $option->value; ?>">
                    <span class="easystore-checkbox-checkmark<?php echo $type == 'radio' ? ' is-radio' : ''; ?>"><?php echo EasyStoreHelper::getIcon('checkmark'); ?></span>
                    <div class="easystore-filter-item-label">
                        <span class="easystore-filter-item-name">
                            <?php if ($hexCode) : ?>
                                <span class="easystore-filter-item-hex-code" style="--variant-color-code: <?php echo $hexCode; ?>" title="<?php echo $option->value; ?>"></span>
                            <?php endif;?>
                            <?php echo ($filter == 'variants') ? $option->value : $option->name; ?>
                        </span>
                        <?php if ($showCount && (isset($option->count))) : ?>
                            <span class="easystore-filter-item-count">
                                (<?php echo (int) $option->count; ?>)
                            </span>
                        <?php endif;?>
                    </div>
                </label>
            </li>
        <?php endforeach;?>
    </ul>
</div>
<?php endif;?>