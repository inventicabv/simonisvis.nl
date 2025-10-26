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

class SppagebuilderAddonEasystoreSingleSku extends SppagebuilderAddons
{
    public function render()
    {
        $settings = $this->addon->settings;

        return EasyStoreHelper::loadLayout(
            'sku',
            [
                'item'      => $this->addon->easystoreItem,
                'separator' => $settings->title_value_separator ?? ':',
            ]
        );
    }

    public function css()
    {
        $css = '';

        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        // SKU Title
        $css .= $cssHelper->generateStyle('.easystore-product-sku-title', $settings, [
            'title_color' => 'color',
        ], false);

        $css .= $cssHelper->typography('.easystore-product-sku-title', $settings, 'title_typography');

        // SKU Value
        $css .= $cssHelper->generateStyle('.easystore-product-sku-value', $settings, [
            'value_color' => 'color',
        ], false);

        $css .= $cssHelper->typography('.easystore-product-sku-value', $settings, 'value_typography');

        // SKU Separator
        $css .= $cssHelper->generateStyle('.easystore-product-sku-title-value-separator', $settings, [
            'title_value_separator_color' => 'color',
            'title_value_separator'       => 'text',
        ], false);

        $css .= $cssHelper->typography('.easystore-product-sku-title-value-separator', $settings, 'title_value_typography');

        return $css;
    }
}
