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

class SppagebuilderAddonEasystoreSingleReviews extends SppagebuilderAddons
{
    public function render()
    {
        return EasyStoreHelper::loadLayout(
            'review.items',
            ['item' => $this->addon->easystoreItem]
        );
    }

    public function css()
    {
        $css = '';

        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        // Item
        $css .= $cssHelper->generateStyle('.easystore-review-item', $settings, [
            'background' => 'background',
            'padding'    => 'padding',
            'radius'     => 'border-radius',
            'alignment'  => 'text-align',
        ], false);

        $css .= $cssHelper->generateStyle('.easystore-reviews', $settings, [
            'spacing' => 'gap',
        ]);

        // Subject
        $css .= $cssHelper->generateStyle('.easystore-review-title', $settings, [
            'subject_color'  => 'color',
            'subject_margin' => 'margin-bottom',
        ], ['subject_color' => false]);

        $css .= $cssHelper->typography('.easystore-review-title', $settings, 'subject_typography');

        // Ratings
        $css .= $cssHelper->generateStyle('.easystore-review-ratings', $settings, [
            'stars_margin' => 'margin-bottom',
        ]);

        $css .= $cssHelper->generateStyle('.easystore-rating-stars', $settings, [
            'stars_color'   => 'color',
            'stars_size'    => 'font-size',
            'stars_spacing' => 'gap',
        ], ['stars_color' => false]);

        // Message
        $css .= $cssHelper->generateStyle('.easystore-review-message', $settings, [
            'message_color'  => 'color',
            'message_margin' => 'margin-bottom',
        ], ['message_color' => false]);
        $css .= $cssHelper->typography('.easystore-review-message', $settings, 'message_typography');

        // Author
        $css .= $cssHelper->generateStyle('.easystore-review-user', $settings, [
            'author_color' => 'color',
        ], false);
        $css .= $cssHelper->typography('.easystore-review-user', $settings, 'author_typography');

        return $css;
    }
}
