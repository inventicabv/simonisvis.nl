<?php

/**
 * @package SP Page Builder
 * @author JoomShaper https://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

//no direct access
defined('_JEXEC') or die('Restricted access');

use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

class SppagebuilderAddonEasystoreSinglePrice extends SppagebuilderAddons
{
    public function render()
    {
        $settings = $this->addon->settings;

        return EasyStoreHelper::loadLayout(
            'price',
            [
                'item' => $this->addon->easystoreItem,
                'origin' => 'single',
                'custom_text' => isset($settings->tax_label_custom_text) ? $settings->tax_label_custom_text : '',
            ]
        );
    }

    public function css()
    {
        $css = '';

        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        // General
        $css .= $cssHelper->generateStyle('.easystore-product-price', $settings, [
            'spacing'     => 'gap',
            'align_items' => 'align-items',
            'padding'     => 'padding',
            'margin'      => 'margin',
        ], ['align_items' => false, 'padding' => false, 'margin' => false]);

        // Price
        $css .= $cssHelper->generateStyle('.easystore-price-current', $settings, [
            'price_color' => 'color',
        ], false);
        $css .= $cssHelper->generateStyle('.easystore-price-current .easystore-price-symbol', $settings, [
            'symbol_color'      => 'color',
            'symbol_align_self' => 'align-self',
            'symbol_spacing'    => 'margin-right',
        ], ['symbol_color' => false, 'symbol_align_self' => false]);
        $css .= $cssHelper->generateStyle('.easystore-price-current .easystore-price-decimal', $settings, [
            'decimal_color'      => 'color',
            'decimal_align_self' => 'align-self',
        ], false);
        $css .= $cssHelper->typography('.easystore-price-current', $settings, 'price_typography');
        $css .= $cssHelper->typography('.easystore-price-current .easystore-price-symbol', $settings, 'symbol_typography');
        $css .= $cssHelper->typography('.easystore-price-current .easystore-price-decimal', $settings, 'decimal_typography');

        // Original Price
        $css .= $cssHelper->generateStyle('.easystore-price-original', $settings, [
            'original_price_color' => 'color',
        ], false);
        $css .= $cssHelper->generateStyle('.easystore-price-original .easystore-price-symbol', $settings, [
            'original_symbol_color'      => 'color',
            'original_symbol_align_self' => 'align-self',
            'original_symbol_spacing'    => 'margin-right',
        ], ['original_symbol_color' => false, 'symbol_align_self' => false]);
        $css .= $cssHelper->generateStyle('.easystore-price-original .easystore-price-decimal', $settings, [
            'original_decimal_color'      => 'color',
            'original_decimal_align_self' => 'align-self',
        ], false);
        $css .= $cssHelper->typography('.easystore-price-original', $settings, 'original_price_typography');
        $css .= $cssHelper->typography('.easystore-price-original .easystore-price-symbol', $settings, 'original_symbol_typography');
        $css .= $cssHelper->typography('.easystore-price-original .easystore-price-decimal', $settings, 'original_decimal_typography');

        // stroke
        $css .= $cssHelper->generateStyle('.easystore-price-original:before', $settings, [
            'original_stroke_color'     => 'border-bottom-color',
            'original_stroke_thickness' => 'border-bottom-width',
            'original_stroke_angle'     => 'rotate',
        ], false);

        if (isset($settings->original_stroke_angle) && $settings->original_stroke_angle) {
            $css .= '#sppb-addon-{{ data.id }} .easystore-price-original:before {transform: rotate(' . $settings->original_stroke_angle . 'deg);}';
        }

        // Tax label
        $css .= $cssHelper->typography('.easystore-product-taxable-price-status', $settings, 'tax_label_typography');
        $css .= $cssHelper->generateStyle('.easystore-product-taxable-price-status', $settings, [
            'tax_label_color' => 'color',
        ], false);

        return $css;
    }
}
