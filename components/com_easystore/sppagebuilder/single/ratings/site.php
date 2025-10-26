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

class SppagebuilderAddonEasystoreSingleRatings extends SppagebuilderAddons
{
    public function render()
    {
        $settings = $this->addon->settings;
        $item     = $this->addon->easystoreItem;

        return EasyStoreHelper::loadLayout(
            'ratings',
            [
                'count'        => $item->reviewData->rating,
                'review_count' => $item->reviewData->count,
                'show_count'   => $settings->show_count ?? 1,
                'show_label'   => $settings->show_label ?? 0,
            ]
        );
    }

    public function css()
    {
        $css = '';

        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        // general
        $css .= $cssHelper->generateStyle('.easystore-rating-stars', $settings, [
            'color'   => 'color',
            'size'    => 'font-size',
            'spacing' => 'gap',
        ], ['color' => false]);

        $css .= $cssHelper->border('.easystore-ratings-container', $settings, 'border');

        $css .= $cssHelper->generateStyle('.easystore-ratings-container', $settings, [
            'padding' => 'padding',
            'margin'  => 'margin',
            'radius'  => 'border-radius',
        ], ['padding' => false, 'margin' => false]);

        // count
        $css .= $cssHelper->generateStyle('.easystore-rating-count', $settings, [
            'count_color' => 'color',
        ], false);

        $css .= $cssHelper->typography('.easystore-rating-count', $settings, 'count_typography');

        $css .= $cssHelper->generateStyle('.easystore-ratings-container', $settings, [
            'count_spacing' => 'gap',
        ]);

        return $css;
    }
}
