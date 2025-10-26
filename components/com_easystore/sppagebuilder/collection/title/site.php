<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2024 JoomShaper <https://www.joomshaper.com> . All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Factory;
use JoomShaper\Component\EasyStore\Site\Helper\CollectionHelper;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class SppagebuilderAddonEasystoreCollectionTitle extends SppagebuilderAddons
{
    public function render()
    {
        $settings          = $this->addon->settings;
        $titleIcon         = isset($settings->title_icon) ? $settings->title_icon : '';
        $titleIconPosition = isset($settings->title_icon_position) ? $settings->title_icon_position : '';
        $collection = CollectionHelper::getCollectionData();

        if (empty($collection)) {
            return '';
        }

        return EasyStoreHelper::loadLayout(
            'collection.title',
            [
                'content'              => $collection->title,
                'selector'          => isset($settings->selector) ? $settings->selector : 'h4',
                'titleIcon'         => $titleIcon,
                'titleIconPosition' => $titleIconPosition,
            ]
        );
    }

    

    public function css()
    {
        $css = '';

        $addon_id                    = '#sppb-addon-' . $this->addon->id;
        $settings                    = $this->addon->settings;
        $cssHelper                   = new CSSHelper($addon_id);
        $settings->title_text_shadow = CSSHelper::parseBoxShadow($settings, 'title_text_shadow', true);

        $css .= $cssHelper->generateStyle('.easystore-collection-title, .easystore-collection-title a', $settings, [
            'color'             => 'color',
            'alignment'         => 'text-align',
            'title_margin'      => 'margin',
            'title_padding'     => 'padding',
            'title_text_shadow' => 'text-shadow',
        ], false);

        $css .= $cssHelper->generateStyle('.easystore-collection-title a .easystore-title-icon, .easystore-collection-title .easystore-title-icon', $settings, [
            'title_icon_color' => 'color',
        ], false);

        $css .= $cssHelper->typography('.easystore-collection-title', $settings, 'typography');

        return $css;
    }
}
