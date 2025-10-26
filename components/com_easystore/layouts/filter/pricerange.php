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
$app   = Factory::getApplication();
$input = $app->input;
EasyStoreHelper::wa()->useScript('com_easystore.alpine.site');

$minPrice = $app->input->get('filter_min_price', $options['min'], 'FLOAT');
$maxPrice = $app->input->get('filter_max_price', $options['max'], 'FLOAT');

$options['min_value'] = $minPrice ?? 0;
$options['max_value'] = $maxPrice ?? 100;
$options['distance']  = $options['distance'] ?? 1;

$showTitle          = $settings->show_title ?? 1;
$showCount          = $settings->show_count ?? 1;
$rageSeparatorLabel = $settings->range_separator_label ?? ':';
$componentContext   = $input->get('option') . '.' . $input->get('view');

?>

<div class="easystore-product-filter" easystore-filter-range x-data="price_range(<?php echo htmlspecialchars(json_encode($options)); ?>)" <?php echo $componentContext === 'com_sppagebuilder.ajax' ? 'x-ignore' : ''; ?>>
    <?php if ($showTitle) : ?>
        <div class="easystore-filter-header">
            <h4 class="easystore-filter-title easystore-h4"><?php echo $settings->title ?? Text::_('COM_EASYSTORE_PRICE_RANGE'); ?></h4>
            <span easystore-filter-reset><?php echo Text::_('COM_EASYSTORE_FILTER_RESET'); ?></span>
        </div>
    <?php endif;?>

    <div class="easystore-range-control row">
        <div class="col">
            <div class="easystore-range-control-wrapper">
                <span class="easystore-range-symbol"><?php echo $options['currency']; ?></span>
                <input class="easystore-form-control form-control" type="number" name="filter_min_price" :value="lowerValue" @change.debounce="updateValue($el, 'lower')" />
            </div>
        </div>

        <div class="easystore-range-separator col-auto">
            <?php echo htmlspecialchars($rageSeparatorLabel, ENT_QUOTES, 'UTF-8'); ?>
        </div>

        <div class="col">
            <div class="easystore-range-control-wrapper">
                <span class="easystore-range-symbol"><?php echo $options['currency']; ?></span>
                <input class="easystore-form-control form-control" type="number" name="filter_max_price" :value="upperValue" @change.debounce="updateValue($el, 'upper')" />
            </div>
        </div>
    </div>

   <div
    class="easystore-range-slider"
    x-ref="rangeSlider"
    :style="sliderStyles"
>
    <div class="easystore-slider-track"></div>
    <div class="easystore-slider-track-inactive"></div>

    <!-- Lower Thumb -->
    <span
        class="easystore-slider-thumb is-lower"
        :class="{'is-dragging': lowerThumbDragStart}"
        x-ref="lowerThumb"
        @mouseenter="showTooltip('lower', true)"
        @mouseleave="showTooltip('lower', false)"
        @mousedown="startDrag('lower')"
        @mouseup="endDrag('lower')"
        @touchstart="startDrag('lower')"
        @touchend="endDrag('lower')"
    >
        <span
            class="easystore-range-value"
            x-cloak
            x-show="tooltipVisible.lower || lowerThumbDragStart"
            x-text="lowerThumbValue"
        ></span>
    </span>

    <!-- Upper Thumb -->
    <span
        class="easystore-slider-thumb is-upper"
        :class="{'is-dragging': upperThumbDragStart}"
        x-ref="upperThumb"
        @mouseenter="showTooltip('upper', true)"
        @mouseleave="showTooltip('upper', false)"
        @mousedown="startDrag('upper')"
        @mouseup="endDrag('upper')"
        @touchstart="startDrag('upper')"
        @touchend="endDrag('upper')"
    >
        <span
            class="easystore-range-value"
            x-cloak
            x-show="tooltipVisible.upper || upperThumbDragStart"
            x-text="upperThumbValue"
        ></span>
    </span>
</div>

</div>