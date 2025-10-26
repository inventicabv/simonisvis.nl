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

class SppagebuilderAddonEasystoreSingleTitle extends SppagebuilderAddons
{
    public function render()
    {
        $settings          = $this->addon->settings;
        $titleIcon         = isset($settings->title_icon) ? $settings->title_icon : '';
        $titleIconPosition = isset($settings->title_icon_position) ? $settings->title_icon_position : '';

        return EasyStoreHelper::loadLayout(
            'title',
            [
                'item'              => $this->addon->easystoreItem,
                'selector'          => $settings->selector ?? 'h1',
                'titleIcon'         => $titleIcon,
                'titleIconPosition' => $titleIconPosition,
            ]
        );
    }

    public function css()
    {
        $css = '';

        $addon_id                    = '#sppb-addon-' . $this->addon->id;
        $settings                    = $this->addon->settings;
        $cssHelper                   = new CSSHelper($addon_id);
        $settings->title_text_shadow = CSSHelper::parseBoxShadow($settings, 'title_text_shadow', true);

        $css .= $cssHelper->generateStyle('.easystore-product-title', $settings, [
            'color'             => 'color',
            'alignment'         => 'text-align',
            'title_margin'      => 'margin',
            'title_padding'     => 'padding',
            'title_text_shadow' => 'text-shadow',
        ], false);

        $css .= $cssHelper->generateStyle('.easystore-product-title a .easystore-title-icon, .easystore-product-title .easystore-title-icon', $settings, [
            'title_icon_color' => 'color',
        ], false);

        $css .= $cssHelper->typography('.easystore-product-title', $settings, 'typography');

        return $css;
    }
}
