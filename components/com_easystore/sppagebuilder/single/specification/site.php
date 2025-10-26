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

class SppagebuilderAddonEasystoreSingleSpecification extends SppagebuilderAddons
{
    public function render()
    {
        $settings = $this->addon->settings;

        return EasyStoreHelper::loadLayout(
            'specification',
            [
                'item'      => $this->addon->easystoreItem,
                'separator' => $settings->specification_item_key_value_separator ?? ':',
            ]
        );
    }

    public function css()
    {
        $css = '';

        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        // Specification Title
        $css .= $cssHelper->generateStyle('.easystore-specification-title', $settings, [
            'specification_title_color' => 'color',
        ], false);
        $css .= $cssHelper->typography('.easystore-specification-title', $settings, 'specification_title_typography');

        // Specification Item key
        $css .= $cssHelper->generateStyle('.easystore-specification-key', $settings, [
            'specification_item_key_color' => 'color',
        ], false);
        $css .= $cssHelper->typography('.easystore-specification-key', $settings, 'specification_item_key_typography');

        // Specification Item Value
        $css .= $cssHelper->generateStyle('.easystore-specification-value', $settings, [
            'specification_item_value_color' => 'color',
        ], false);
        $css .= $cssHelper->typography('.easystore-specification-value', $settings, 'specification_item_value_typography');

        // Specification Item Key Value Separator
        $css .= $cssHelper->generateStyle('.easystore-specification-item-key-value-separator', $settings, [
            'specification_item_key_value_separator_color' => 'color',
        ], false);
        $css .= $cssHelper->typography('.easystore-specification-item-key-value-separator', $settings, 'specification_item_key_value_separator_typography');

        return $css;
    }
}
