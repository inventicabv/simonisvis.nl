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

class SppagebuilderAddonEasystoreSingleVariants extends SppagebuilderAddons
{
    public function render()
    {
        $settings = $this->addon->settings;

        return EasyStoreHelper::loadLayout(
           'variants',
            [
                'item'      => $this->addon->easystoreItem,
                'separator' => $settings->label_value_separator ?? ':',
            ]
        );
    }

    public function css()
    {
        $css = '';

        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        // general
        $css .= $cssHelper->generateStyle('.easystore-product-variants', $settings, [
            'spacing' => 'gap',
        ]);

        $css .= $cssHelper->generateStyle('.easystore-variant-title', $settings, [
            'title_spacing' => 'margin-bottom',
        ]);

        $css .= $cssHelper->generateStyle('.easystore-variant-title', $settings, [
            'label_color'                   => 'color',
            'label_value_separator_spacing' => 'gap',
        ], ['label_color' => false]);

        $css .= $cssHelper->typography('.easystore-variant-title', $settings, 'title_typography');

        $css .= $cssHelper->generateStyle('.easystore-option-value-name', $settings, [
            'value_color' => 'color',
        ], false);

        $css .= $cssHelper->typography('.easystore-option-value-name', $settings, 'value_typography');

        $css .= $cssHelper->generateStyle('.easystore-option-name-separator', $settings, [
            'label_value_separator_color' => 'color',
        ], false);

        // Color variant
        $css .= $cssHelper->generateStyle('.easystore-product-variant-color .easystore-variant-option-color', $settings, [
            'color_variant_size'          => ['width', 'height'],
            'color_variant_border_radius' => 'border-radius',
        ]);

        $css .= $cssHelper->generateStyle('.easystore-variant-option-color:after', $settings, ['color_variant_border_color' => '--easystore-variant-border-color'], false);

        $css .= $cssHelper->generateStyle('.easystore-product-variant-color .easystore-variant-options', $settings, [
            'color_variant_spacing' => 'gap',
        ]);

        // list variant
        $css .= $cssHelper->generateStyle('.easystore-product-variant-list .easystore-variant-options', $settings, [
            'list_variant_spacing' => 'gap',
        ]);

        $css .= $cssHelper->typography('.easystore-product-variant-list .easystore-variant-option-value', $settings, 'list_variant_typography');

        $css .= $cssHelper->generateStyle('.easystore-product-variant-list .easystore-variant-option-value', $settings, [
            'list_variant_border_radius' => 'border-radius',
        ]);

        $css .= $cssHelper->generateStyle('.easystore-product-variant-list .easystore-variant-option-value', $settings, [
            'list_variant_padding'          => 'padding',
            'list_variant_background_color' => 'background-color',
            'list_variant_color'            => 'color',
            'list_variant_border_color'     => 'border-color',
        ], false);

        $css .= $cssHelper->border('.easystore-product-variant-list .easystore-variant-option-value', $settings, 'list_variant_border');
        $css .= $cssHelper->typography('.easystore-product-variant-list .easystore-variant-option-value', $settings, 'list_variant_typography');

        $css .= $cssHelper->generateStyle('.easystore-product-variant-list .easystore-variant-option input[type="radio"]:checked + .easystore-variant-option-value', $settings, [
            'list_variant_active_background_color' => 'background-color',
            'list_variant_active_color'            => 'color',
        ], false);

        $css .= $cssHelper->border('.easystore-product-variant-list .easystore-variant-option input[type="radio"]:checked + .easystore-variant-option-value', $settings, 'list_variant_active_border');

        return $css;
    }
}
