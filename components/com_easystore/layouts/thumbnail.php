<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

extract($displayData);

$app = Factory::getApplication();
$input = $app->input;
$option = $input->get('option', '', 'STRING');
$optionView = $input->get('view', '', 'STRING');
$context = $option . '.' . $optionView;
$isEditor = ($context === 'com_sppagebuilder.form' || $context === 'com_sppagebuilder.ajax');

$showLink = $link ?? false;
$openInLightbox = $open_in_lightbox ?? true;
$showControls = $show_controls ?? true;
$showThumbnails = $show_thumbnails ?? true;
$showZoomProductThumbnail = $show_zoom ?? false;
$isToggleThumbnail = $toggleImage ?? false;
$secondaryThumbnail = $isToggleThumbnail && !empty($item->media->gallery[1]) ? $item->media->gallery[1] : false;
$isVideo = !empty($secondaryThumbnail) && $secondaryThumbnail->type === 'video';
$origin = $origin ?? 'list';
$thumbnailPlaceholder = Uri::root(true) . '/media/com_easystore/images/thumbnail-placeholder.svg';
$defaultThumbnailSrc = EasyStoreHelper::getPlaceholderImage();
$isZoomEnabled = $origin === 'single' && $openInLightbox && !$isEditor;
$altText = $altText ?? '';



?>
<?php if (!empty($item->thumbnail) || !empty($defaultThumbnailSrc)) : ?>
    <?php
    $thumbnailSrc = !empty($item->thumbnail->src) ? $item->thumbnail->src : $defaultThumbnailSrc;
    ?>
<div class="<?php echo (!empty($item->thumbnail->type) && $item->thumbnail->type === 'video') ? 'easystore-product-video' : 'easystore-product-image'; ?> "
    <?php echo !$isEditor ? 'easystore-thumbnail-wrapper' : ''; ?>>
    <?php if ($origin === 'list') : ?>
        <?php if (!empty($item->thumbnail->type) && $item->thumbnail->type === 'video') : ?>
    <div class="easystore-ratio easystore-ratio-4x3">
        <div class="easystore-video-thumbnail">
            <video preload="auto" crossOrigin="anonymous" playsinline="playsinline" disablepictureinpicture="true"
                src="<?php echo $thumbnailSrc; ?>"></video>
            <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle opacity=".6" cx="19.998" cy="20.002" r="16.643" fill="#fff" stroke="#C9CBCF" />
                <path
                    d="M22.677 17.083c0-.552-.235-1.082-.654-1.473a2.315 2.315 0 0 0-1.578-.61h-5.357c-.592 0-1.16.22-1.579.61-.418.39-.654.92-.654 1.473v5.834c0 .552.236 1.082.654 1.473.419.39.987.61 1.579.61h5.357c.592 0 1.16-.22 1.578-.61.419-.39.654-.92.654-1.473v-5.834Zm.893 1.192v3.46l2.468 1.958a.713.713 0 0 0 .715.091.658.658 0 0 0 .283-.23.595.595 0 0 0 .105-.337v-6.39a.595.595 0 0 0-.105-.334.657.657 0 0 0-.28-.23.712.712 0 0 0-.713.085l-2.473 1.927Z"
                    fill="#5C5E62" />
            </svg>
        </div>
    </div>
        <?php else : ?>
            <?php if (!$isEditor) : ?>
    <img src="<?php echo $thumbnailPlaceholder; ?>"
        alt="<?php echo $altText ? $altText : (!empty($item->thumbnail->alt_text) ? htmlspecialchars($item->thumbnail->alt_text) : 'Default Thumbnail'); ?>"
        data-src="<?php echo $thumbnailSrc; ?>" data-unveil loading="lazy">
            <?php else : ?>
    <img src="<?php echo $thumbnailSrc; ?>" alt=<?php echo $altText ?? '' ?>>
            <?php endif; ?>
            <?php if ($isToggleThumbnail && !empty($secondaryThumbnail->src)) : ?>
                <?php if (!$isVideo) : ?>
        <img class="easystore-product-image-o" src="<?php echo $thumbnailPlaceholder; ?>"
        data-src="<?php echo $secondaryThumbnail->src; ?>" alt="<?php echo $altText ?? $secondaryThumbnail->alt_text; ?>"
        loading="lazy">
                <?php else : ?>
                    <video class="easystore-product-video-o" preload="auto" crossOrigin="anonymous" playsinline="playsinline" disablepictureinpicture="true"
                        src="<?php echo $secondaryThumbnail->src; ?>" width="100%" height="100%"></video>       
                <?php endif; ?>
            <?php endif; ?>
            <?php if (!$isEditor) : ?>
    <div class="easystore-thumb-skeleton"></div>
            <?php endif; ?>
        <?php endif; ?>
    <?php else : ?>
        <?php if (!empty($item->thumbnail)) : ?>
            <?php if ($showZoomProductThumbnail) : ?>
            <div class="easystore-product-image-zoom-container" x-data="easystoreProductDetailsThumbnail">
                <div class="easystore-product-image-wrapper">
                    <img class="<?php echo $isZoomEnabled ? 'easystore-zoom-cursor' : ''; ?>" src="<?php echo $thumbnailSrc; ?>"
                    alt="<?php echo $altText ? $altText : (!empty($item->thumbnail->alt_text) ? htmlspecialchars($item->thumbnail->alt_text) : 'Default Thumbnail'); ?>"
                    x-ref="productThumbnail"
                    x-on:pointermove="lensVisible = true; moveLens($event)"
                    x-on:mouseleave="lensVisible = false"
                    style="<?php echo (!empty($item->thumbnail->type) && $item->thumbnail->type === 'video') ? 'display: none;' : ''; ?>"
                        <?php echo $isZoomEnabled ? '@click="openGalleryPreview(\'image\')"' : ''; ?> />
                        <div x-ref="lens" class="easystore-product-image-zoom-lens"> </div>
                </div>
                <template x-teleport="body">
                    <div x-ref="result" class="easystore-product-image-zoom-result" x-show="lensVisible" ></div>
                </template>
            </div>
            <?php else : ?>
            <img class="<?php echo $isZoomEnabled ? 'easystore-zoom-cursor' : ''; ?>" src="<?php echo $thumbnailSrc; ?>"
            alt="<?php echo $altText ? $altText : (!empty($item->thumbnail->alt_text) ? htmlspecialchars($item->thumbnail->alt_text) : 'Default Thumbnail'); ?>"
            x-ref="productThumbnail"
            style="<?php echo (!empty($item->thumbnail->type) && $item->thumbnail->type === 'video') ? 'display: none;' : ''; ?>"
                <?php echo $isZoomEnabled ? '@click="openGalleryPreview(\'image\')"' : ''; ?> />
            <?php endif; ?>
           


            <div class="<?php echo $isZoomEnabled ? ' easystore-zoom-cursor' : ''; ?>"
                x-ref="productVideo"
                style="<?php echo (!empty($item->thumbnail->type) && $item->thumbnail->type === 'image') ? 'display: none;' : ''; ?>"
                    <?php echo $isZoomEnabled ? '@click="openGalleryPreview(\'video\')"' : ''; ?>>
                <div class="easystore-video-thumbnail">
                    <video preload="auto" playsinline="playsinline" disablepictureinpicture="true"
                        src="<?php echo $thumbnailSrc; ?>"></video>
                    <svg viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path opacity=".8"
                            d="M28 4.75C15.16 4.75 4.75 15.16 4.75 28S15.16 51.25 28 51.25 51.25 40.84 51.25 28 40.84 4.75 28 4.75Z"
                            fill="#fff" stroke="#212529" />
                        <path
                            d="M35.477 27.26c.53.322.53 1.157 0 1.48L23.9 35.767c-.516.314-1.151-.094-1.151-.74V20.973c0-.646.635-1.054 1.151-.74l11.576 7.027Z"
                            fill="#212529" />
                    </svg>
                </div>
            </div>
        <?php else : ?>
        <img class="<?php echo $isZoomEnabled ? 'easystore-zoom-cursor' : ''; ?>" src="<?php echo $thumbnailSrc; ?>"
        alt="<?php echo $altText ? $altText : (!empty($item->thumbnail->alt_text) ? htmlspecialchars($item->thumbnail->alt_text) : 'Default Thumbnail'); ?>"
        x-ref="productThumbnail"
        style="<?php echo (!empty($item->thumbnail->type) && $item->thumbnail->type === 'video') ? 'display: none;' : ''; ?>"
            <?php echo $isZoomEnabled ? '@click="openGalleryPreview(\'image\')"' : ''; ?> />
        <?php endif; ?>
    <?php endif; ?>
    <?php if ($showLink) : ?>
    <a class="stretched-link" href="<?php echo $item->link; ?>"><span
            class="visually-hidden"><?php echo Text::_('COM_EASYSTORE_DETAILS'); ?></span></a>
    <?php endif; ?>
</div>
<?php endif; ?>


<?php if ($origin === 'single' && $openInLightbox && !empty($item->thumbnail)) : ?>
<template x-teleport="body">
    <div class="easystore-zoom-gallery">
        <div class="easystore-zoom-gallery-wrapper" x-ref="zoomGallery">
            <div class="easystore-zoom-gallery-preview" x-ref="zoomPreview">
                <img src="<?php echo $item->thumbnail->src; ?>" @click="toggleZoom"
                    alt="<?php echo $altText ?? $item->thumbnail->alt_text; ?>" draggable="false" x-ref="previewImage"
                    style="<?php echo $item->thumbnail->type === 'video' ? 'display: none;' : ''?>" />
                <div class="easystore-ratio easystore-ratio-16x9" easystore-preview-video
                    style="<?php echo $item->thumbnail->type === 'image' ? 'display: none;' : ''?>">
                    <div class="easystore-video-thumbnail">
                        <video preload="auto" playsinline="playsinline" disablepictureinpicture="true"
                            crossOrigin="anonymous" @click="handlePlayPauseOnClick" loop x-ref="previewVideo"
                            src="<?php echo $item->thumbnail->src; ?>" draggable="false"></video>
                    </div>
                </div>
            </div>

            <div class="easystore-zoom-gallery-thumbs<?php echo !$showThumbnails ? ' thumbs-hidden' : ''; ?>"
                x-ref="galleryThumbs">
                <?php foreach ($item->media->gallery as $index => $image) : ?>
                <button type="button" class="easystore-zoom-gallery-thumb" easystore-zoom-thumb
                    x-ref="thumbItem<?php echo $index; ?>" :class="isSelected(<?php echo $index; ?>) ? 'is-active': ''"
                    data-index="<?php echo $index; ?>" data-type="<?php echo $image->type; ?>"
                    data-src="<?php echo $image->src; ?>" @click="selectPreviewImage">
                    <div class="thumb-image-wrapper">
                        <?php if ($image->type === 'image') : ?>
                        <img src="<?php echo $image->src; ?>" alt="<?php echo $altText ? $altText : ($image->name ?? $alt_text); ?>" />
                        <?php else : ?>
                        <div class="easystore-ratio easystore-ratio-16x9">
                            <div class="easystore-video-thumbnail">
                                <video preload="auto" playsinline="playsinline" disablepictureinpicture="true"
                                    src="<?php echo $image->src; ?>"></video>
                                <svg viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path opacity=".8"
                                        d="M28 4.75C15.16 4.75 4.75 15.16 4.75 28S15.16 51.25 28 51.25 51.25 40.84 51.25 28 40.84 4.75 28 4.75Z"
                                        fill="#fff" stroke="#212529" />
                                    <path
                                        d="M35.477 27.26c.53.322.53 1.157 0 1.48L23.9 35.767c-.516.314-1.151-.094-1.151-.74V20.973c0-.646.635-1.054 1.151-.74l11.576 7.027Z"
                                        fill="#212529" />
                                </svg>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>

            <div class="easystore-zoom-gallery-controls">
                <button type="button" class="easystore-zoom-gallery-button easystore-zoom-gallery-close"
                    @click="openPreview = false">
                    <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M30 10 10 30M10 10l20 20" stroke="#000" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </button>

                <?php if ($showControls) : ?>
                <button type="button" class="easystore-zoom-gallery-button easystore-zoom-gallery-prev"
                    @click="selectPreviousImage">
                    <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M30 36 18 24l12-12" stroke="#000" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </button>
                <button type="button" class="easystore-zoom-gallery-button easystore-zoom-gallery-next"
                    @click="selectNextImage">
                    <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="m18 36 12-12-12-12" stroke="#000" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="easystore-zoom-gallery-backdrop" x-ref="zoomBackdrop" @click="openPreview = false"></div>
    </div>
</template>
<?php endif; ?>
