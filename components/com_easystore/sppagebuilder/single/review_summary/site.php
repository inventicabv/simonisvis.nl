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

class SppagebuilderAddonEasystoreSingleReviewSummary extends SppagebuilderAddons
{
    public function render()
    {
        return EasyStoreHelper::loadLayout(
            'review.summary',
            ['item' => $this->addon->easystoreItem]
        );
    }

    public function css()
    {
        $css = '';

        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        // Rating
        $css .= $cssHelper->generateStyle('.easystore-summary-value', $settings, [
            'count_color' => 'color',
        ], false);

        $css .= $cssHelper->typography('.easystore-summary-value', $settings, 'count_typography');

        $css .= $cssHelper->generateStyle('.easystore-summary-total', $settings, [
            'total_color' => 'color',
        ], false);

        $css .= $cssHelper->typography('.easystore-summary-total', $settings, 'total_typography');

        $css .= $cssHelper->generateStyle('.easystore-summary-count', $settings, [
            'gap'         => 'gap',
            'spacing'     => 'margin-bottom',
            'align_items' => 'align-items',
        ], ['align_items' => false]);

        // Stars
        $css .= $cssHelper->generateStyle('.easystore-summary-stars', $settings, [
            'stars_color'   => 'color',
            'stars_size'    => 'font-size',
            'stars_gap'     => 'gap',
            'stars_spacing' => 'margin-bottom',
        ], ['stars_color' => false]);

        // Content
        $css .= $cssHelper->generateStyle('.easystore-summary-content', $settings, [
            'content_color' => 'color',
        ], ['content_color' => false]);

        $css .= $cssHelper->typography('.easystore-summary-content', $settings, 'content_typography');

        return $css;
    }
}
