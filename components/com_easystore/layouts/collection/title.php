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

extract($displayData);

$selector = isset($selector) ? $this->escape($selector) : 'h1';

?>

<?php
if (!empty($titleIcon)) {
    $icon_arr = array_filter(explode(' ', $titleIcon));
    if (count($icon_arr) === 1) {
            $titleIcon = 'fa ' . $titleIcon;
    }
}
?>

<<?php echo $selector; ?> class="easystore-collection-title">
    <?php if (!empty($titleIcon) && $titleIconPosition === 'before') : ?>
        <span class="<?php echo $titleIcon; ?> easystore-title-icon" aria-hidden="true"></span>
    <?php endif; ?>
    <span><?php echo $content; ?></span>
    <?php if (!empty($titleIcon) && $titleIconPosition === 'after') : ?>
        <span class="<?php echo $titleIcon; ?> easystore-title-icon" aria-hidden="true"></span>
    <?php endif; ?>
</<?php echo $selector; ?>>