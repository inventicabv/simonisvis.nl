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

class SppagebuilderAddonEasystoreSingleAvailability extends SppagebuilderAddons
{
    public function render()
    {
        $settings = $this->addon->settings;

        return EasyStoreHelper::loadLayout(
            'availability',
            [
                'item'      => $this->addon->easystoreItem,
                'show_icon' => $settings->show_icon ?? true,
            ]
        );
    }

    public function css()
    {
        $css = '';

        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        $css .= $cssHelper->typography('.easystore-product-inventory-value', $settings, 'typography');
        $css .= $cssHelper->generateStyle('.easystore-product-inventory-value', $settings, [
            'color' => 'color',
        ], false);

        // icon
        $css .= $cssHelper->generateStyle('.easystore-product-inventory-icon, .easystore-product-inventory-icon::before', $settings, [
            'icon_size' => ['width', 'height'],
        ]);

        $css .= $cssHelper->generateStyle('.easystore-product-inventory', $settings, [
            'icon_spacing' => 'gap',
        ]);

        $css .= $cssHelper->generateStyle('.easystore-available-stock, .easystore-available-stock::before', $settings, [
            'available_stock_icon_color' => 'background-color',
        ], false);

        $css .= $cssHelper->generateStyle('.easystore-low-stock, .easystore-low-stock::before', $settings, [
            'low_inventory_icon_color' => 'background-color',
        ], false);

        $css .= $cssHelper->generateStyle('.easystore-no-stock, .easystore-no-stock::before', $settings, [
            'out_of_stock_icon_color' => 'background-color',
        ], false);

        $css .= $cssHelper->typography('.easystore-product-title', $settings, 'typography');

        return $css;
    }
}
