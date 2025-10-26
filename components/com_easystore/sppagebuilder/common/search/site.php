<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2024 JoomShaper <https://www.joomshaper.com> . All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

class SppagebuilderAddonEasystoreCommonSearch extends SppagebuilderAddons
{
    public function render()
    {
        $settings = $this->addon->settings;

        return EasyStoreHelper::loadLayout(
            'filter.search',
            [
                'placeholder' => $settings->placeholder ?? Text::_('COM_EASYSTORE_SEARCH'),
            ]
        );
    }

    public function css()
    {
        $css = '';

        $addon_id  = '#sppb-addon-' . $this->addon->id;
        $settings  = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);

        $css .= $cssHelper->generateStyle('.form-control', $settings, [
            'color'            => 'color',
            'background_color' => 'background-color',
            'border_radius'    => 'border-radius',
            'padding'          => 'padding',
        ], ['color' => false, 'background_color' => false, 'padding' => false]);

        $css .= $cssHelper->border('.form-control', $settings, 'border');
        $css .= $cssHelper->typography('.form-control', $settings, 'typography');

        $css .= $cssHelper->generateStyle('.easystore-svg', $settings, [
            'icon_size'    => 'font-size',
            'icon_spacing' => 'left',
            'icon_color'   => 'color',
        ], ['icon_color' => false]);

        $css .= $cssHelper->generateStyle('.form-control::placeholder', $settings, [
            'placeholder_color' => 'color',
        ], false);

        return $css;
    }

    //
    //
    //
    //
}
