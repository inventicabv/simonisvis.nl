<?php

/**
 * @package     COM_POSTNL
 * @subpackage  Template
 *
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;

// Helper function for status colors
function getStatusColor($status) {
    switch ($status) {
        case 'completed':
            return 'success';
        case 'processing':
            return 'primary';
        case 'pending':
            return 'warning';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}

?>
<form action="<?php echo Route::_('index.php?option=com_postnl&view=orders'); ?>" method="post" name="adminForm" id="adminForm">

    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table table-striped" id="ordersList">
                        <thead>
                            <tr>
                                <th style="width:1%" class="text-center">
                                    <?php echo Text::_('COM_POSTNL_HEADING_ID'); ?>
                                </th>
                                <th>
                                    <?php echo Text::_('COM_POSTNL_HEADING_ORDER_NUMBER'); ?>
                                </th>
                                <th>
                                    <?php echo Text::_('COM_POSTNL_HEADING_CUSTOMER'); ?>
                                </th>
                                <th class="text-center">
                                    <?php echo Text::_('COM_POSTNL_HEADING_STATUS'); ?>
                                </th>
                                <th class="text-center">
                                    <?php echo Text::_('COM_POSTNL_HEADING_TRACKING'); ?>
                                </th>
                                <th class="text-center">
                                    <?php echo Text::_('COM_POSTNL_HEADING_DATE'); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($this->items as $i => $item) : ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td class="text-center">
                                    <?php echo $item->id; ?>
                                </td>
                                <td>
                                    <a href="<?php echo Route::_('index.php?option=com_postnl&view=order&id=' . (int) $item->id); ?>">
                                        <strong><?php echo $this->escape($item->order_number ?? '#' . $item->id); ?></strong>
                                    </a>
                                </td>
                                <td>
                                    <?php echo $this->escape($item->customer_email); ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo getStatusColor($item->order_status); ?>">
                                        <?php echo $this->escape($item->order_status); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($item->tracking_number)) : ?>
                                        <span class="badge bg-success">
                                            <span class="icon-check" aria-hidden="true"></span>
                                            <?php echo $this->escape($item->tracking_number); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="badge bg-secondary">
                                            <?php echo Text::_('COM_POSTNL_NO_TRACKING'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('date', $item->creation_date, Text::_('DATE_FORMAT_LC4')); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php // Load the pagination. ?>
                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
