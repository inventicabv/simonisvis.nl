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

class SppagebuilderAddonEasystoreSingleCategory extends SppagebuilderAddons
{
    public function render()
    {
        return EasyStoreHelper::loadLayout(
            'category',
            ['item' => $this->addon->easystoreItem]
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
