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

class SppagebuilderAddonEasystoreListTitle extends SppagebuilderAddons
{
    public function render()
    {
        $settings          = $this->addon->settings;
        $products          = $this->addon->easystoreList;
        $index             = $this->addon->listIndex;
        $showLink          = $settings->show_link ?? true;
        $titleIcon         = isset($settings->title_icon) ? $settings->title_icon : '';
        $titleIconPosition = isset($settings->title_icon_position) ? $settings->title_icon_position : '';

        return EasyStoreHelper::loadLayout(
            'title',
            [
                'item'              => $products[$index],
                'selector'          => isset($settings->selector) ? $settings->selector : 'h4',
                'link'              => $showLink,
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

        $css .= $cssHelper->generateStyle('.easystore-product-title, .easystore-product-title a', $settings, [
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
