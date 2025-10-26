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

class SppagebuilderAddonEasystoreSingleDescription extends SppagebuilderAddons
{
    public function render()
    {
        return EasyStoreHelper::loadLayout(
            'description',
            ['item' => $this->addon->easystoreItem]
        );
    }

    public function css()
    {
        $css = '';

        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        $css .= $cssHelper->generateStyle('.easystore-product-description', $settings, [
            'color'      => 'color',
            'typography' => 'typography',
            'alignment'  => 'text-align',
        ], false);

        $css .= $cssHelper->typography('.easystore-product-description', $settings, 'typography');

        return $css;
    }
}
