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

class SppagebuilderAddonEasystoreSingleBadge extends SppagebuilderAddons
{
    public function render()
    {
        $settings = $this->addon->settings;

        return EasyStoreHelper::loadLayout(
            'badge',
            [
                'item'          => $this->addon->easystoreItem,
                'sale'          => $settings->sale ?? 1,
                'badge_type'    => $settings->text ?? 'sale',
                'badge_text'    => $settings->text ?? 'sale',
                'custom_text'   => $settings->custom_text ?? 'Custom',
                'featured'      => $settings->featured ?? 0,
                'featured_text' => $settings->featured_text ?? 'Featured',
            ]
        );
    }

    public function css()
    {
        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        $saleAttributes = [
            'color'            => 'color',
            'background_color' => 'background-color',
        ];

        $featuredAttributes = [
            'featured_color'            => 'color',
            'featured_background_color' => 'background-color',
        ];

        $basicAttributes = [
            'padding'       => 'padding',
            'border_radius' => 'border-radius',
        ];

        $css = $cssHelper->generateStyle('.easystore-badge.is-sale', $settings, array_merge($saleAttributes, $basicAttributes), ['color' => false, 'background_color' => false, 'padding' => false]);
        $css .= $cssHelper->generateStyle('.easystore-badge.is-featured', $settings, array_merge($featuredAttributes, $basicAttributes), ['featured_color' => false, 'featured_background_color' => false, 'padding' => false]);
        $css .= $cssHelper->typography('.easystore-badge.is-sale', $settings, 'typography');
        $css .= $cssHelper->typography('.easystore-badge.is-featured', $settings, 'featured_typography');
        $css .= $cssHelper->border('.easystore-badge', $settings, 'border');

        return $css;
    }
}
