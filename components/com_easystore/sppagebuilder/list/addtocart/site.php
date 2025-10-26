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

class SppagebuilderAddonEasystoreListAddtocart extends SppagebuilderAddons
{
    public function render()
    {
        $settings = $this->addon->settings;
        $products = $this->addon->easystoreList;
        $index    = $this->addon->listIndex;

        return EasyStoreHelper::loadLayout(
            'addtocart',
            [
                'item'    => $products[$index],
                'layout'  => $settings->layout ?? 'text',
                'context' => 'product-list',
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

        if ($settings->layout === 'icon-text') {
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
