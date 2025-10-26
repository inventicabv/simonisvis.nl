<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use JoomShaper\Component\EasyStore\Administrator\Supports\Arr;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);
$vars = [
    '--collection-image-fit' => $image_fit,
    '--collection-aspect-ratio' => $aspect_ratio,
];
$cssVariables = '';

foreach ($vars as $key => $value) {
    if (empty($value)) {
        continue;
    }

    $cssVariables .= $key . ': ' . $value . '; ';
}
?>

<div class="easystore-collection-thumbnail" style="<?php echo $cssVariables; ?>">
    <img src="<?php echo $src; ?>" alt="<?php echo $alt; ?>" title="<?php echo $alt; ?>" class="easystore-collection-thumbnail-image" style="object-fit: <?php echo $image_fit; ?>">
</div>

