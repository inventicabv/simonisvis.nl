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

class SppagebuilderAddonEasystoreListCategory extends SppagebuilderAddons
{
    public function render()
    {
        $products = $this->addon->easystoreList;
        $index    = $this->addon->listIndex;

        if (!isset($products[$index])) {
            return '';
        }

        return EasyStoreHelper::loadLayout(
            'category',
            ['item' => $products[$index]]
        );
    }

    public function css()
    {
        $css = '';

        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        $css .= $cssHelper->generateStyle('.easystore-product-category', $settings, [
            'alignment' => 'alignment',
        ], false);

        $css .= $cssHelper->generateStyle('.easystore-product-category, .easystore-product-category a', $settings, [
            'color' => 'color',
        ], false);

        $css .= $cssHelper->typography('.easystore-product-category', $settings, 'typography');

        return $css;
    }
}
