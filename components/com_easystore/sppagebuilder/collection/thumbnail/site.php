<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2024 JoomShaper <https://www.joomshaper.com> . All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Uri\Uri;
use JoomShaper\Component\EasyStore\Site\Helper\CollectionHelper;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class SppagebuilderAddonEasystoreCollectionThumbnail extends SppagebuilderAddons
{
    public function render()
    {
        $settings          = $this->addon->settings;
        $collection = CollectionHelper::getCollectionData();

        if (empty($collection)) {
            return '';
        }

        $aspectRatio = $settings->aspect_ratio ?? '';

        if ($aspectRatio === 'custom') {
            $aspectRatio = $settings->custom_aspect_ratio ?? '';
        }

        if ($aspectRatio === 'none') {
            $aspectRatio = '';
        }

        $displayData = [
            'title' => $collection->title ?? '',
            'src' => $collection->image ?: Uri::root(true) . '/media/com_easystore/images/thumbnail.jpg',
            'alt' => $collection->title ?? '',
            'image_fit' => $settings->image_fit ?? 'cover',
            'aspect_ratio' => $aspectRatio,
        ];

        return EasyStoreHelper::loadLayout(
            'collection.thumbnail',
            $displayData
        );
    }

    public function css()
    {
        $css = '';

        $addon_id                    = '#sppb-addon-' . $this->addon->id;
        $settings                    = $this->addon->settings;
        $cssHelper                   = new CSSHelper($addon_id);

        $isEffectsEnabled = (isset($settings->is_effects_enabled) && $settings->is_effects_enabled) ? $settings->is_effects_enabled : 0;

        if ($isEffectsEnabled) {
            $settings->image_effects = $cssHelper::parseCssEffects($settings, 'image_effects');
        }

        $imageEffectStyle = $cssHelper->generateStyle(
            '.easystore-collection-thumbnail .easystore-collection-thumbnail-image',
            $settings,
            ['image_effects' => 'filter'],
            false
        );

        $transformCss = $cssHelper->generateTransformStyle(
            '.easystore-collection-thumbnail .easystore-collection-thumbnail-image',
            $settings,
            'transform'
        );

        $imageBorderRadius = $cssHelper->generateStyle(
            '.easystore-collection-thumbnail .easystore-collection-thumbnail-image',
            $settings,
            ['radius' => 'border-radius'],
            ['border_radius' => false],
            ['border_radius' => 'spacing']
        );

        $imageBorderStyle = $cssHelper->border('.easystore-collection-thumbnail', $settings, 'border');

        $imageWrapperStyle = $cssHelper->generateStyle(
            '.easystore-collection-thumbnail',
            $settings,
            ['width' => 'width', 'height' => 'height'],
            'px'
        );

        $staticStyles = $cssHelper->generateStyle(
            '.easystore-collection-thumbnail', $settings, [], '', [], [], false,
            'width: 100%; overflow: hidden; aspect-ratio: var(--collection-aspect-ratio);'
        );
        $staticImageStyles = $cssHelper->generateStyle(
            '.easystore-collection-thumbnail .easystore-collection-thumbnail-image', $settings, [], '', [], [], false,
            'width: 100%; height: 100%; object-fit: var(--collection-image-fit);'
        );

        $css .= $staticStyles;
        $css .= $staticImageStyles;
        $css .= $imageWrapperStyle;
        $css .= $imageBorderStyle;
        $css .= $imageBorderRadius;
        $css .= $imageEffectStyle;
        $css .= $transformCss;

        return $css;
    }
}
