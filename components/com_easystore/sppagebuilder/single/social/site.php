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

class SppagebuilderAddonEasystoreSingleSocial extends SppagebuilderAddons
{
    public function render()
    {
        return EasyStoreHelper::loadLayout(
            'social',
            ['item' => $this->addon->easystoreItem]
        );
    }

    public function css()
    {
        $css = '';

        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        // General
        $css .= $cssHelper->generateStyle('.easystore-social-share', $settings, [
            'spacing'         => 'gap',
            'justify_content' => 'justify-content',
        ], ['justify_content' => false]);

        // Normal
        $css .= $cssHelper->generateStyle('.easystore-social-share > li a', $settings, [
            'size'   => 'font-size',
            'width'  => 'width',
            'height' => 'height',
            'radius' => 'border-radius',
        ]);

        $css .= $cssHelper->generateStyle('.easystore-social-share > li a', $settings, [
            'color'      => 'color',
            'background' => 'background-color',
        ], false);

        // Hover
        $css .= $cssHelper->generateStyle('.easystore-social-share > li a:hover', $settings, [
            'color_hover'      => 'color',
            'background_hover' => 'background-color',
        ], false);

        return $css;
    }
}
