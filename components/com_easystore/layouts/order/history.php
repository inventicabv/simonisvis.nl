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

use Joomla\CMS\Language\Text;

extract($displayData);
?>

<div class="easystore-card easystore-card-border my-4">
    <div class="easystore-card-header">
        <h4 class="easystore-h4">
            <?php echo Text::_('COM_EASYSTORE_ORDER_HISTORY') ?>
        </h4>
    </div>
    <div class="easystore-cart-body py-3">
        <?php if (!empty($displayData['history'])) :?>
            <ul>
                <?php foreach ($displayData['history'] as $data) :?>
                    <li>
                        <div class="text-muted"><?php echo $data->created; ?></div>
                        <p><b><?php echo $data->activity_type; ?></b> <?php if (!empty($data->activity_value)) : echo ' - ' . $data->activity_value; endif; ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>