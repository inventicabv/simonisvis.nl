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

class SppagebuilderAddonEasystoreSingleGallery extends SppagebuilderAddons
{
    public function render()
    {
        return EasyStoreHelper::loadLayout(
            'gallery',
            ['item' => $this->addon->easystoreItem]
        );
    }

    public function css()
    {
        $css = '';

        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        $css .= $cssHelper->generateStyle('.easystore-product-gallery', $settings, [
            'columns' => 'grid-template-columns:repeat(%s, 1fr)',
            'spacing' => 'gap',
        ], ['columns' => false]);

        $css .= $cssHelper->generateStyle('.easystore-gallery-image', $settings, [
            'padding'          => 'padding',
            'background_color' => 'background-color',
            'radius'           => 'border-radius',
            'opacity'          => 'opacity',
        ], ['background_color' => false, 'opacity' => false, 'padding' => false]);

        $css .= $cssHelper->generateStyle('.easystore-gallery-image:not(.active):hover', $settings, [
            'background_color_hover' => 'background-color',
            'opacity_hover'          => 'opacity',
        ], false);

        $css .= $cssHelper->generateStyle('.easystore-gallery-image.active', $settings, [
            'background_color_active' => 'background-color',
            'opacity_active'          => 'opacity',
        ], false);

        // border
        $css .= $cssHelper->border('.easystore-gallery-image', $settings, 'border');
        $css .= $cssHelper->border('.easystore-gallery-image:not(.active):hover', $settings, 'border_hover');
        $css .= $cssHelper->border('.easystore-gallery-image.active', $settings, 'border_active');

        return $css;
    }
}
