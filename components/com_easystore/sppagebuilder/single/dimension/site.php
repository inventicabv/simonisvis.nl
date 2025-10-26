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

class SppagebuilderAddonEasystoreSingleDimension extends SppagebuilderAddons
{
    public function render()
    {
        $settings = $this->addon->settings;

        return EasyStoreHelper::loadLayout(
            'dimension',
            [
                'item'            => $this->addon->easystoreItem,
                'separator'       => $settings->title_value_separator ?? ':',
                'dimension_title' => $settings->title_text ?? '',
            ]
        );
    }

    public function css()
    {
        $css = '';

        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        // Dimension Title
        $css .= $cssHelper->generateStyle('.easystore-product-dimension-title', $settings, [
            'title_color' => 'color',
        ], false);

        $css .= $cssHelper->typography('.easystore-product-dimension-title', $settings, 'title_typography');

        // Dimension Value
        $css .= $cssHelper->generateStyle('.easystore-product-dimension-value', $settings, [
            'value_color' => 'color',
        ], false);

        $css .= $cssHelper->typography('.easystore-product-dimension-value', $settings, 'value_typography');

        // Dimension Separator
        $css .= $cssHelper->generateStyle('.easystore-product-dimension-title-value-separator', $settings, [
            'title_value_separator_color' => 'color',
            'title_value_separator'       => 'text',
        ], false);

        $css .= $cssHelper->typography('.easystore-product-dimension-title-value-separator', $settings, 'title_value_typography');

        return $css;
    }
}
