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

$specifications = json_decode($item->additional_data ?? '');

?>

<?php if (count((array) $specifications)) : ?>
<div class="easystore-product-specification">
    <?php foreach ($specifications as $specification) : ?>
    <h3 class="easystore-specification-title"><?php echo $this->escape($specification->title);?></h3>
        <?php foreach ($specification->values as $value) : ?>
    <div class="easystore-specification-item">
        <span class="easystore-specification-key"><?php echo $this->escape($value->key); ?></span>
        <span class="easystore-specification-item-key-value-separator"><?php echo $separator; ?></span>
        <span class="easystore-specification-value"><?php echo nl2br($value->value); ?></span>
    </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>