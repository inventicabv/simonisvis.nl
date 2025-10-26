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

class SppagebuilderAddonEasystoreSingleThumbnail extends SppagebuilderAddons
{
    public function render()
    {
        $settings = $this->addon->settings;
        $altText  = isset($settings->alt_text) ? $settings->alt_text : '';

        return EasyStoreHelper::loadLayout(
            'thumbnail',
            [
                'item'             => $this->addon->easystoreItem,
                'open_in_lightbox' => $settings->open_in_lightbox ?? 1,
                'show_controls'    => $settings->show_controls ?? 1,
                'show_thumbnails'  => $settings->show_thumbnails ?? 1,
                'show_zoom'        => $settings->show_zoom ?? 0,
                'origin'           => 'single',
                'altText'          => $altText,
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
        $isShowZoomEnabled      = (isset($settings->show_zoom) && $settings->show_zoom) ? $settings->show_zoom : 0;

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
        if ($isShowZoomEnabled) {
            $css .= $cssHelper->generateStyle(
                '.easystore-product-image-zoom-lens',
                $settings,
                [
                    'lens_background_opacity' => 'opacity',
                    
                ],
                false
            );
        }

        $css .= $cssHelper->border('.easystore-product-image', $settings, 'border');

        return $css;
    }
}
