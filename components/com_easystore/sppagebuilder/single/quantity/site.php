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

class SppagebuilderAddonEasystoreSingleQuantity extends SppagebuilderAddons
{
    public function render()
    {
        return EasyStoreHelper::loadLayout(
            'quantity',
            ['item' => $this->addon->easystoreItem]
        );
    }

    public function css()
    {
        $css = '';

        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        // Input
        $css .= $cssHelper->generateStyle('.easystore-product-quantity', $settings, [
            'radius'     => 'border-radius',
            'background' => 'background',
        ], ['background' => false]);

        $css .= $cssHelper->generateStyle('.easystore-product-quantity', $settings, [
            'width'  => 'width',
            'height' => 'height',
            'color'  => 'color',
        ], ['color' => false]);

        $css .= $cssHelper->typography('.easystore-product-quantity', $settings, 'typography');
        $css .= $cssHelper->border('.easystore-product-quantity', $settings, 'border');

        // button
        $css .= $cssHelper->generateStyle('.easystore-quantity-selector-btn', $settings, [
            'button_width'     => 'width',
            'button_icon_size' => 'font-size',
            'button_color'     => 'color',
        ], ['button_color' => false]);

        $css .= $cssHelper->generateStyle('.easystore-quantity-selector-btn:hover', $settings, [
            'button_color_hover' => 'color',
        ], false);

        return $css;
    }
}
