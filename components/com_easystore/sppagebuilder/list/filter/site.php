<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2024 JoomShaper <https://www.joomshaper.com> . All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class SppagebuilderAddonEasystoreListFilter extends SppagebuilderAddons
{
    public function render()
    {
        $settings = $this->addon->settings;

        return EasyStoreHelper::loadLayout(
            'products.filter',
            ['settings' => $settings ?? []]
        );
    }

    public function css()
    {
        $css = '';

        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        $css .= $cssHelper->generateStyle('.easystore-filter-container', $settings, [
            'direction' => 'flex-direction',
            'gap'       => 'gap',
        ], ['direction' => false]);

        $css .= $cssHelper->generateStyle('.easystore-filter-header', $settings, [
           'title_gap' => 'margin-bottom',
        ]);

        $css .= $cssHelper->generateStyle('.easystore-filter-title', $settings, [
            'title_color' => 'color',
        ], false);

        $css .= $cssHelper->typography('.easystore-filter-title', $settings, 'title_typography');

        $css .= $cssHelper->generateStyle('[easystore-filter-reset]', $settings, [
            'reset_btn_color' => 'color',
        ], false);

        // Checkbox
        $css .= $cssHelper->generateStyle('.easystore-checkbox-checkmark', $settings, [
            'checkbox_radio_size'             => ['width', 'height'],
            'checkbox_radio_background_color' => 'background-color',
        ], ['checkbox_radio_background_color' => false]);

        $css .= $cssHelper->generateStyle('.easystore-checkbox-checkmark:not(.is-radio)', $settings, [
            'checkbox_border_radius' => 'border-radius',
        ], false);

        $css .= $cssHelper->border('.easystore-checkbox-checkmark', $settings, 'checkbox_radio_border');

        // Checked
        $css .= $cssHelper->generateStyle('.easystore-checkbox-label > input[type="checkbox"]:checked ~ .easystore-checkbox-checkmark, .easystore-checkbox-label > input[type="radio"]:checked ~ .easystore-checkbox-checkmark', $settings, [
            'checkbox_radio_check_color'              => 'color',
            'checkbox_radio_background_color_checked' => 'background-color',
        ], false);

        $css .= $cssHelper->border('.easystore-checkbox-label > input[type="checkbox"]:checked ~ .easystore-checkbox-checkmark, .easystore-checkbox-label > input[type="radio"]:checked ~ .easystore-checkbox-checkmark', $settings, 'checkbox_radio_border_checked');

        // list item
        $css .= $cssHelper->generateStyle('ul', $settings, [
            'list_item_gap' => 'gap',
        ]);

        $css .= $cssHelper->generateStyle('.easystore-filter-item-label', $settings, [
            'list_item_color' => 'color',
        ], false);

        $css .= $cssHelper->generateStyle('.easystore-filter-item-count', $settings, [
            'list_item_count_color' => 'color',
        ], false);

        $css .= $cssHelper->typography('.easystore-filter-item-label', $settings, 'list_item_typography');

        // Price Range
        $css .= $cssHelper->generateStyle('.easystore-slider-track, .easystore-slider-track-inactive', $settings, [
            'slider_height' => '--easystore-track-height',
        ]);

        $css .= $cssHelper->generateStyle('.easystore-slider-track-inactive', $settings, [
            'slider_background' => '--easystore-range-border',
        ], false);

        $css .= $cssHelper->generateStyle('.easystore-slider-track', $settings, [
            'slider_active_background' => '--easystore-range-foreground',
        ], false);

        $css .= $cssHelper->generateStyle('.easystore-slider-thumb', $settings, [
            'thumb_size' => ['width', 'height'],
        ]);

        $css .= $cssHelper->generateStyle('.easystore-slider-thumb:before', $settings, [
            'thumb_color' => '--easystore-range-foreground',
        ], false);

        $css .= $cssHelper->generateStyle('.easystore-slider-thumb.is-dragging:after', $settings, [
            'thumb_color' => '--easystore-range-foreground',
        ], false);

        $css .= $cssHelper->generateStyle('.easystore-range-symbol', $settings, [
            'symbol_color' => 'color',
            'symbol_size'  => 'font-size',
        ], ['symbol_color' => false]);

        $css .= $cssHelper->generateStyle('.easystore-range-control-wrapper', $settings, [
            'symbol_gap' => 'gap',
        ]);

        $css .= $cssHelper->generateStyle('.easystore-range-separator', $settings, [
            'range_separator_color' => 'color',
        ], false);

        // Dropdown
        $css .= $cssHelper->generateStyle('.easystore-form-control, .easystore-form-select', $settings, [
            'input_background'    => 'background-color',
            'input_color'         => 'color',
            'input_border_radius' => 'border-radius',
            'input_padding'       => 'padding',
        ], ['input_background' => false, 'input_color' => false, 'input_padding' => false]);

        $css .= $cssHelper->border('.easystore-form-control, .easystore-form-select', $settings, 'input_border');

        return $css;
    }
}
