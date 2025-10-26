<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2024 JoomShaper <https://www.joomshaper.com> . All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use JoomShaper\Component\EasyStore\Site\Helper\CollectionHelper;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class SppagebuilderAddonEasystoreCollectionDescription extends SppagebuilderAddons
{
    public function render()
    {
        $settings          = $this->addon->settings;
        $class = (isset($settings->class) && $settings->class) ? $settings->class : '';
        $hasDropCap = (isset($settings->has_drop_cap) && $settings->has_drop_cap) ? $settings->has_drop_cap : 0;

        $dropCapClass = '';

		if ($hasDropCap) {
			$dropCapClass = ' easystore-drop-cap';
		}
        
        $collection = CollectionHelper::getCollectionData();

        if (empty($collection)) {
            return '';
        }

        $displayData = [
            'content' => $collection->description,
            'class' => $class,
            'has_drop_cap' => $hasDropCap,
            'drop_cap_class' => $dropCapClass,
        ];

        return EasyStoreHelper::loadLayout('collection.description', $displayData);
    }

    public function css()
    {
        $css = '';

        $addon_id                    = '#sppb-addon-' . $this->addon->id;
        $settings                    = $this->addon->settings;
        $cssHelper                   = new CSSHelper($addon_id);
        $hasDropCap = (isset($settings->has_drop_cap) && $settings->has_drop_cap) ? $settings->has_drop_cap : 0;
        $settings->title_text_shadow = CSSHelper::parseBoxShadow($settings, 'title_text_shadow', true);

        $settings->content_alignment = CSSHelper::parseAlignment($settings, 'content_alignment');
        
        $css = '';
        $dropCapStyle = $cssHelper->generateStyle(
            '.easystore-drop-cap .easystore-collection-description-content:first-letter, .easystore-drop-cap .easystore-collection-description-content p:first-letter',
            $settings,
            [
                'drop_cap_color' => 'color',
                'drop_cap_font_size' => ['font-size', 'line-height']
            ],
            ['drop_cap_color' => false]
        );

        $textFontStyle = $cssHelper->typography(
            '.easystore-collection-description .easystore-collection-description-content',
            $settings,
            'content_typography',
            [
                'font'        => 'text_font_family',
                'size'        => 'text_fontsize',
                'line_height' => 'text_lineheight',
                'weight'      => 'text_fontweight'
            ]
        );

        if ($hasDropCap) {
            $css .= $dropCapStyle;
        }

        $css .= $cssHelper->generateStyle('.easystore-collection-description', $settings, ['content_alignment'  => 'text-align'], false);
        $css .= $cssHelper->generateStyle('.easystore-collection-description .easystore-collection-description-content', $settings, ['content_text_color'  => 'color'], false);
		$transformCss = $cssHelper->generateTransformStyle('.easystore-collection-description', $settings, 'transform');

        $css .= $textFontStyle;
        $css .= $transformCss;

        return $css;
    }
}
