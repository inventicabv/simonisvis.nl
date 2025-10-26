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

class SppagebuilderAddonEasystoreListThumbnail extends SppagebuilderAddons
{
    public function render()
    {
        $settings = $this->addon->settings;
        $products = $this->addon->easystoreList;
        $index    = $this->addon->listIndex;
        $showLink = $settings->show_link ?? false;
        $altText  = $settings->alt_text ?? '';

        return EasyStoreHelper::loadLayout(
            'thumbnail',
            [
                'item'        => $products[$index],
                'link'        => $showLink,
                'toggleImage' => $settings->toggle_image ?? 0,
                'origin'      => 'list',
                'altText'     => $altText,
            ]
        );
    }

    public function css()
    {
        $css = '';

        $addon_id         = '#sppb-addon-' . $this->addon->id;
        $settings         = $this->addon->settings;
        $cssHelper        = new CSSHelper($addon_id);
        $isEffectsEnabled = (isset($settings->is_effects_enabled) && $settings->is_effects_enabled) ? $settings->is_effects_enabled : 0;

        $css .= $cssHelper->generateStyle('.easystore-product-image', $settings, [
            'padding'          => 'padding',
            'margin'           => 'margin',
            'background_color' => 'background-color',
            'radius'           => 'border-radius',
        ], ['background_color' => false, 'padding' => false, 'margin' => false]);

        if ($isEffectsEnabled) {
            $settings->image_effects = $cssHelper::parseCssEffects($settings, 'image_effects');

            $css .= $cssHelper->generateStyle(
                '.easystore-product-image img',
                $settings,
                [
                    'image_effects' => 'filter',
                ],
                false
            );
        }

        $css .= $cssHelper->border('.easystore-product-image', $settings, 'border');

        return $css;
    }
}
