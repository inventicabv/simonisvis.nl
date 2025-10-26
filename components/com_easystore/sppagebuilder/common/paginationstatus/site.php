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

class SppagebuilderAddonEasystoreCommonPaginationstatus extends SppagebuilderAddons
{
    public function render()
    {
        $settings = $this->addon->settings;

        return EasyStoreHelper::loadLayout(
            'pagination_status',
            [
                'pattern'        => $settings->pattern,
                'custom_pattern' => $settings->custom_pattern,
            ]
        );
    }

    public function css()
    {
        $css = '';

        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        $css .= $cssHelper->generateStyle('.easystore-pagination-status', $settings, [
            'color' => 'color',
        ], false);

        $css .= $cssHelper->typography('.easystore-pagination-status', $settings, 'typography');
        $css .= $cssHelper->typography('.easystore-pagination-status > span', $settings, 'number_typography');

        $css .= $cssHelper->generateStyle('.easystore-pagination-status > span', $settings, [
            'number_color' => 'color',
        ], false);

        return $css;
    }
}
