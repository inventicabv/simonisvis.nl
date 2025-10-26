<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

$separator = $separator ?? ':';

if ($item->has_variants && !empty($item->active_variant)) {
    $variantOptionMap = isset($item->active_variant->variant_option_map)
        ? $item->active_variant->variant_option_map
        : null;

    $selectedOptions = [];
    $checkedValues = array_values((array)$item->active_variant->variant_option_map);
    $checkedValues = array_map(function ($item) {
        return strtolower($item);
    }, $checkedValues);
}

$settings = SettingsHelper::getSettings();
$variantLayout = $settings->get('products.variantLayout', 'list');
$origin = $origin ?? 'single';
?>

<?php if ($item->has_variants && !empty($item->active_variant)) : ?>
    <div class="easystore-product-variants" x-ref="option_wrapper_<?php echo $item->id; ?>">
        <?php if (!empty($item->options)) : ?>
            <?php foreach ($item->options as $outerIndex => $option) : ?>
                <?php
                if ($outerIndex > 0) {
                    $selectedOptions[] = $checkedValues[$outerIndex - 1];
                }
                ?>
                <?php if ($variantLayout === 'list') : ?>
                    <div class="easystore-product-variant easystore-product-variant-<?php echo $option->type; ?>">
                        <div class="easystore-variant-title easystore-block-label">
                            <span class="easystore-option-name"><?php echo $option->name; ?></span>
                            <span class="easystore-option-name-separator"><?php echo $separator; ?></span>
                            <span class="easystore-option-value-name"><?php echo $variantOptionMap->{$option->name}; ?></span>
                        </div>

                        <div class="easystore-variant-options" easystore-variant-options>
                            <?php if (!empty($option->values)) : ?>
                                <?php foreach ($option->values as $index => $value) : ?>
                                    <?php
                                    $radioInputName = str_replace(' ', '_', strtolower($option->name)) . '_' . $item->id;
                                    $radioInputValue = strtolower($value->name);
                                    $isActive = strtolower($variantOptionMap->{$option->name}) === $radioInputValue;
                                    $isSoldOut = false;

                                    if ($outerIndex === 0) {
                                        $isSoldOut = !$item->availability->$radioInputValue->availability;
                                    } else {
                                        $combinationArray = array_merge($selectedOptions, [$radioInputValue]);
                                        natcasesort($combinationArray);
                                        $combination = array_filter($combinationArray, function ($item) {
                                            return !empty($item);
                                        });
                                        $combination = array_values($combination);
                                        $combination = implode(';', $combination);

                                        $isSoldOut = !$item->availability->$combination->availability;
                                    }
                                    ?>

                                    <span class="easystore-variant-option<?php echo $isSoldOut ? ' disabled' : ''; ?>" easystore-variant-option>
                                        <label>
                                            <input type="radio" @change="handleRadioChange('<?php echo $origin; ?>', <?php echo $item->id; ?>)" name="<?php echo $radioInputName; ?>" value="<?php echo $radioInputValue; ?>" <?php echo $isActive ? 'checked' : ''; ?>>

                                            <?php if ($option->type === 'color') : ?>
                                                <span class="easystore-variant-option-color" style="background-color: <?php echo $value->color ?? 'transparent'; ?>;" area-labelBy="<?php echo $value->name; ?>" title="<?php echo $value->name; ?>"></span>
                                            <?php else : ?>
                                                <span class="easystore-variant-option-value"><?php echo $value->name; ?></span>
                                            <?php endif; ?>
                                        </label>
                                    </span>

                                <?php endforeach; ?>
                            <?php endif; ?>

                        </div>

                    </div>
                <?php else : ?>
                    <div class="easystore-product-variant easystore-product-variant-<?php echo $option->type; ?>">
                        <div class="easystore-variant-title easystore-block-label">
                            <span class="easystore-option-name"><?php echo $option->name; ?></span>
                            <span class="easystore-option-name-separator"><?php echo $separator; ?></span>
                            <span class="easystore-option-value-name"><?php echo $variantOptionMap->{$option->name}; ?></span>
                        </div>

                        <div class="easystore-variant-options" easystore-variant-options>
                            <?php $radioInputName = str_replace(' ', '_', strtolower($option->name)) . '_' . $item->id; ?>
                            <?php if (!empty($option->values)) : ?>
                                <select class="easystore-form-select" @change="handleSelectChange('<?php echo $origin; ?>', <?php echo $item->id; ?>)" name="<?php echo $radioInputName; ?>">
                                    <?php foreach ($option->values as $index => $value) : ?>
                                        <?php
                                        $radioInputValue = strtolower($value->name);
                                        $isActive = strtolower($variantOptionMap->{$option->name}) === $radioInputValue;
                                        $isSoldOut = false;

                                        if ($outerIndex === 0) {
                                            $isSoldOut = !$item->availability->$radioInputValue->availability;
                                        } else {
                                            $combinationArray = array_merge($selectedOptions, [$radioInputValue]);
                                            natcasesort($combinationArray);
                                            $combination = array_filter($combinationArray, function ($item) {
                                                return !empty($item);
                                            });
                                            $combination = array_values($combination);
                                            $combination = implode(';', $combination);

                                            $isSoldOut = !$item->availability->$combination->availability;
                                        }
                                        ?>

                                        <option value="<?php echo $radioInputValue; ?>" <?php echo ($isActive && !$isSoldOut) ? 'selected' : ''; ?> <?php echo $isSoldOut ? ' disabled' : ''; ?>><?php echo $value->name; ?></option>

                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        <textarea class="easystore-hide" x-ref="product_variants_<?php echo $item->id; ?>"><?php echo json_encode($item->variants); ?></textarea>
    </div>
<?php endif; ?>