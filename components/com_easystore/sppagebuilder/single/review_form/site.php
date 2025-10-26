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

class SppagebuilderAddonEasystoreSingleReviewForm extends SppagebuilderAddons
{
    public function render()
    {
        $settings = $this->addon->settings;

        return EasyStoreHelper::loadLayout(
            'review.form',
            [
                'item'        => $this->addon->easystoreItem,
                'showIcon'    => isset($settings->show_icon) ? $settings->show_icon : false,
                'showDivider' => isset($settings->show_divider) ? $settings->show_divider : false,
            ]
        );
    }

    public function css()
    {
        $css = '';

        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        // Normal
        $settings->background_color = $cssHelper->parseColor($settings, 'background');

        $css .= $cssHelper->generateStyle('.easystore-btn-review-form', $settings, [
            'background_color' => 'background',
            'color'            => 'color',
            'padding'          => 'padding',
            'border_width'     => 'border-width',
            'border_color'     => 'border-color',
            'radius'           => 'border-radius',
        ], false);

        $css .= $cssHelper->generateStyle('.easystore-btn-review-form', $settings, [
            'border_width' => 'border-width',
            'gap'          => 'gap',
            'width'        => 'width',
        ]);

        $css .= $cssHelper->generateStyle('.easystore-btn-icon', $settings, [
            'icon_color' => 'color',
        ], false);

        $css .= $cssHelper->generateStyle('.easystore-btn-separator', $settings, [
            'divider_color' => 'color',
        ], false);

        // Hover
        $settings->background_color_hover = $cssHelper->parseColor($settings, 'background_hover');
        $css .= $cssHelper->generateStyle('.easystore-btn-review-form:hover', $settings, [
            'background_color_hover' => 'background',
            'color_hover'            => 'color',
            'border_color_hover'     => 'border-color',
        ], false);

        $css .= $cssHelper->generateStyle('.easystore-btn-review-form:hover .easystore-btn-icon', $settings, [
            'icon_color_hover' => 'color',
        ], false);

        $css .= $cssHelper->generateStyle('.easystore-btn-review-form:hover .easystore-btn-separator', $settings, [
            'divider_color_hover' => 'color',
        ], false);
        return $css;
    }
}
