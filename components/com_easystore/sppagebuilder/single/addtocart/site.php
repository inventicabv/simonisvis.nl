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

class SppagebuilderAddonEasystoreSingleAddtocart extends SppagebuilderAddons
{
    public function render()
    {
        $settings = $this->addon->settings;

        return EasyStoreHelper::loadLayout(
            'addtocart',
            [
                'item'    => $this->addon->easystoreItem,
                'layout'  => $settings->layout ?? 'text',
                'context' => 'product-details',
            ]
        );
    }

    public function css()
    {
        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        $commonStyles = [
            'background_color' => 'background',
            'color'            => 'color',
            'padding'          => 'padding',
            'radius'           => 'border-radius',
            'width'            => 'width',
        ];

        $commonStylesExceptions = [
            'background_color' => false,
            'color'            => false,
            'padding'          => false,
        ];

        $settings->background_color = $cssHelper->parseColor($settings, 'background');

        $css = $cssHelper->generateStyle('.easystore-btn-add-to-cart', $settings, $commonStyles, $commonStylesExceptions)
            . $cssHelper->border('.easystore-btn-add-to-cart', $settings, 'border')
            . $cssHelper->typography('.easystore-btn-add-to-cart', $settings, 'typography')
            . $cssHelper->generateStyle('.easystore-btn-icon', $settings, [
                'icon_size'  => 'font-size',
                'icon_color' => 'color',
            ], ['icon_color' => false]);

        if (isset($settings->layout) && $settings->layout === 'icon-text') {
            $css .= $cssHelper->generateStyle('.easystore-btn-add-to-cart .easystore-btn-icon', $settings, [
                'gap' => 'margin-right',
            ]);
        }

        $settings->background_color_hover = $cssHelper->parseColor($settings, 'background_hover');
        $css .= $cssHelper->generateStyle('.easystore-btn-add-to-cart:hover', $settings, [
                'background_color_hover' => 'background',
                'color_hover'            => 'color',
                'border_color_hover'     => 'border-color',
            ], false)
            . $cssHelper->border('.easystore-btn-add-to-cart:hover', $settings, 'border_hover')
            . $cssHelper->generateStyle('.easystore-btn-add-to-cart:hover .easystore-btn-icon', $settings, [
                'icon_color_hover' => 'color',
            ], false);

        return $css;
    }
}
