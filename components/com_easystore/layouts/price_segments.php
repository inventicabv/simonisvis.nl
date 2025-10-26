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

$is_negative = $is_negative ?? false;
?>
<?php if (isset($is_negative) && $is_negative) : ?>
    <span class="easystore-price-negative-sing">-</span>
<?php endif; ?>
<?php if ($segments->currency_position === 'before') : ?>
    <span class="easystore-price-symbol"><?php echo $segments->currency; ?></span>
<?php endif; ?>
<span class="easystore-price-whole"><?php echo $segments->segments->amount; ?></span>
<span class="easystore-price-decimal"><?php echo $segments->decimal_separator . $segments->segments->decimal; ?></span>
<?php if ($segments->currency_position === 'after') : ?>
    <span class="easystore-price-symbol"><?php echo $segments->currency; ?></span>
<?php endif; ?>