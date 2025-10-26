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

class SppagebuilderAddonEasystoreListAddtowishlist extends SppagebuilderAddons
{
    public function render()
    {
        $settings   = $this->addon->settings;
        $products   = $this->addon->easystoreList;
        $index      = $this->addon->listIndex;
        $isWishlist = false;

        if (!empty($products) && $products[0]->source === 'wishlist') {
            $isWishlist = true;
        }

        return EasyStoreHelper::loadLayout(
            'addtowishlist',
            [
                'item'       => $products[$index],
                'layout'     => $settings->layout ?? 'text',
                'isWishlist' => $isWishlist,
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

        $css = $cssHelper->generateStyle('.easystore-btn-add-to-wishlist', $settings, $commonStyles, $commonStylesExceptions)
            . $cssHelper->border('.easystore-btn-add-to-wishlist', $settings, 'border')
            . $cssHelper->typography('.easystore-btn-add-to-wishlist', $settings, 'typography')
            . $cssHelper->generateStyle('.easystore-btn-icon', $settings, [
                'icon_size'  => 'font-size',
                'icon_color' => 'color',
            ], ['icon_color' => false]);

        if ($settings->layout === 'icon-text') {
            $css .= $cssHelper->generateStyle('.easystore-btn-add-to-wishlist .easystore-btn-icon', $settings, [
                'gap' => 'margin-right',
            ]);
        }

        $settings->background_color_hover = $cssHelper->parseColor($settings, 'background_hover');
        $css .= $cssHelper->generateStyle('.easystore-btn-add-to-wishlist:hover', $settings, [
                'background_color_hover' => 'background',
                'color_hover'            => 'color',
                'border_color_hover'     => 'border-color',
            ], false)
            . $cssHelper->border('.easystore-btn-add-to-wishlist:hover', $settings, 'border_hover')
            . $cssHelper->generateStyle('.easystore-btn-add-to-wishlist:hover .easystore-btn-icon', $settings, [
                'icon_color_hover' => 'color',
            ], false);

        return $css;
    }
}
