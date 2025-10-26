<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Factory;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

$app = Factory::getApplication();
$input = $app->input;
$option = $input->get('option', '', 'STRING');
$optionView = $input->get('view', '', 'STRING');
$context = $option . '.' . $optionView;
$isEditor = ($context == 'com_sppagebuilder.form' || $context == 'com_sppagebuilder.ajax');

?>
<div class="easystore-product-gallery">
    <?php foreach ($item->media->gallery as $index => $image) : ?>
        <?php $isVideo = $image->type === 'video'; ?>
        <?php if ($isEditor) : ?>
            <div class="easystore-gallery-image <?php echo (int) $image->id === (int) $item->thumbnail->id ? ' active' : ''; ?>">
                <?php if ($isVideo) : ?>
                    <video src="<?php echo $image->src; ?>" preload="auto" playsinline="playsinline" disablepictureinpicture="true" />
                <?php else : ?>
                    <img src="<?php echo $image->src; ?>" alt="<?php echo $image->alt_text; ?>">
                <?php endif; ?>   
            </div>
        <?php else : ?>
            <button
                type="button"
                easystore-gallery-item
                data-src="<?php echo $image->src; ?>"
                @click='onThumbnailChange($event, "<?php echo str_replace("\\", "/", $image->src); ?>", "<?php echo $image->type; ?>")'
                class="easystore-gallery-image easystore-button-reset<?php echo (int) $image->id === (int) $item->thumbnail->id ? ' active' : ''; ?>"
            >
                <?php if ($isVideo) : ?>
                    <div class="easystore-ratio easystore-ratio-4x3">
                        <div class="easystore-gallery-video">
                            <video src="<?php echo $image->src; ?>" preload="auto" playsinline="playsinline" disablepictureinpicture="true"></video>
                            <svg viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity=".8" d="M28 4.75C15.16 4.75 4.75 15.16 4.75 28S15.16 51.25 28 51.25 51.25 40.84 51.25 28 40.84 4.75 28 4.75Z" fill="#fff" stroke="#212529"/><path d="M35.477 27.26c.53.322.53 1.157 0 1.48L23.9 35.767c-.516.314-1.151-.094-1.151-.74V20.973c0-.646.635-1.054 1.151-.74l11.576 7.027Z" fill="#212529"/></svg>
                        </div>
                    </div>
                <?php else : ?>
                    <img src="<?php echo $image->src; ?>" alt="<?php echo $image->alt_text; ?>" loading="lazy">
                <?php endif; ?>
            </button>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
