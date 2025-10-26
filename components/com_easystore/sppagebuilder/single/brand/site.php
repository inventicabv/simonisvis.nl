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

class SppagebuilderAddonEasystoreSingleBrand extends SppagebuilderAddons
{
    public function render()
    {
        $settings = $this->addon->settings;
        $show_brand_image = !empty($settings->show_brand_image) ? 1 : 0;
        $show_brand_name = !empty($settings->show_brand_name) ? 1 : 0;
        $show_brand_link = !empty($settings->show_brand_link) ? 1 : 0;
        $show_brand_label = !empty($settings->show_brand_label) ? 1 : 0;
        $brand_image_height = !empty($settings->brand_image_height) ? $settings->brand_image_height : 50;
        $brand_image_width = !empty($settings->brand_image_width) ? $settings->brand_image_width : 50;
        $brand_image_radius = !empty($settings->brand_image_radius) ? $settings->brand_image_radius : 0;

        return EasyStoreHelper::loadLayout(
            'brand',
            [
                'item' => $this->addon->easystoreItem,
                'show_brand_image' => $show_brand_image,
                'show_brand_name' => $show_brand_name,
                'show_brand_link' => $show_brand_link,
                'show_brand_label' => $show_brand_label,
                'brand_image_height' => $brand_image_height,
                'brand_image_width' => $brand_image_width,
                'brand_image_radius' => $brand_image_radius,
            ]
        );
    }

    public function css() 
    {
        $css = '';
        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        $css .= $cssHelper->generateStyle('.easystore-product-brand-image', $settings, [
            'brand_image_height' => ['height'],
            'brand_image_width' => ['width'],
            'brand_image_radius' => ['border-radius'],
        ]);
        
        return $css;
    }
}
