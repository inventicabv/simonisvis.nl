<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Layout\LayoutHelper;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseMapHelper;
use JoomShaper\Component\EasyStore\Site\Helper\OrderHelper;

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_easystore.admin')
    ->useScript('table.columns')
    ->useScript('multiselect');

$app       = Factory::getApplication();
$user      = Factory::getApplication()->getIdentity();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = ($listOrder === 'a.ordering' && strtolower($listDirn) === 'asc');
$acl = AccessControl::create();

if ($saveOrder && !empty($this->items)) {
    $saveOrderingUrl = 'index.php?option=com_easystore&task=orders.saveOrderAjax&' . Session::getFormToken() . '=1';
    HTMLHelper::_('draggablelist.draggable');
}


?>
<form action="<?php echo Route::_('index.php?option=com_easystore&view=orders'); ?>" method="post" name="adminForm" id="adminForm">
    <div id="j-main-container" class="j-main-container">
        <div class="easystore-container">
            <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
            <?php if (empty($this->items)) : ?>
                <div class="alert alert-info">
                    <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                    <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                </div>
            <?php else : ?>
                <table class="table" id="orderList">
                    <caption class="visually-hidden">
                        <?php echo Text::_('COM_ORDERS_TABLE_CAPTION'); ?>,
                        <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                        <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                    </caption>
                    
                    <thead>
                        <tr>
                            <td class="w-1 text-center">
                                <?php echo HTMLHelper::_('grid.checkall'); ?>
                            </td>
                            <th scope="col" class="w-1 d-none d-md-table-cell text-center">
                                <?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
                            </th>
                            <th scope="col">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_EASYSTORE_ORDER_FIELD_ORDER_ID', 'a.id', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_EASYSTORE_ORDER_FIELD_CUSTOM_INVOICE_ID', 'a.custom_invoice_id', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_EASYSTORE_ORDER_FIELD_ORDER_DATE', 'a.created', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_EASYSTORE_ORDER_FIELD_CUSTOMER', 'customer_name', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_EASYSTORE_ORDER_FIELD_PAYMENT_STATUS', 'a.payment_status', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_EASYSTORE_ORDER_FIELD_FULFILMENT', 'a.fulfilment', $listDirn, $listOrder); ?>
                            </th>

                            <th scope="col">
                                <?php echo Text::_('COM_EASYSTORE_ORDER_FIELD_TOTAL_PRICE'); ?>
                            </th>

                        </tr>
                    </thead>
                    <tbody <?php if ($saveOrder) :
                        ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true" <?php
                           endif;?>>
                        <?php
                        foreach ($this->items as $i => $item) :
                            $canCreate = $acl->canCreate();
                            $canEdit = $acl->canEdit() || $acl->setContext('order')->canEditOwn($item->id);
                            $canCheckin = $acl->setAsset('com_checkin')->canManage() || (int) $item->checked_out === (int) $user->get('id') || is_null($item->checked_out);
                            $canChange = $acl->canEditState() && $canCheckin;

                            $routePath  = $item->order_status === 'active' ? 'order-details' : 'manage-order';
                            ?>
                            <tr class="row<?php echo $i % 2; ?>" data-item-id="<?php echo $item->id; ?>" sortable-group-id="1" data-draggable-group="1">
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->id); ?>
                                </td>
                                <td class="text-center d-none d-md-table-cell">
                                    <?php
                                    $iconClass = '';
                                    if (!$canChange) {
                                            $iconClass = ' inactive';
                                    } elseif (!$saveOrder) {
                                        $iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
                                    }
                                    ?>
                                    <span class="sortable-handler<?php echo $iconClass ?>">
                                        <span class="icon-ellipsis-v"></span>
                                    </span>
                                    <?php if ($canChange && $saveOrder) : ?>
                                        <input type="text" class="hidden" name="order[]" size="5" value="<?php echo $item->ordering; ?>">
                                    <?php endif;?>
                                </td>
                                <th scope="row">
                                    <?php if ($item->checked_out) : ?>
                                        <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'orders.', $canCheckin); ?>
                                    <?php endif;?>
                                    <?php if ($canEdit) : ?>
                                        <a href="<?php echo Route::_('index.php?option=com_easystore&task=order.edit&id=' . $item->id . '#/'. $routePath); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo OrderHelper::formatOrderNumber($this->escape($item->id)); ?>">
                                            <?php echo OrderHelper::formatOrderNumber($this->escape($item->id)); ?></a>
                                    <?php else : ?>
                                        <?php echo OrderHelper::formatOrderNumber($this->escape($item->id)); ?>
                                    <?php endif;?>
                                </th>
                                <td class="small d-none d-md-table-cell">
                                    <?php echo $this->escape($item->custom_invoice_id); ?>
                                </td>
                                <td class="small d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('date', $item->created, Text::_("DATE_FORMAT_LC2")); ?>
                                </td>
                                <td class="small d-none d-md-table-cell">
                                    <?php echo $this->escape($item->customer_name); ?>
                                </td>
                                <td class="small d-none d-md-table-cell">
                                    <?php echo EasyStoreDatabaseMapHelper::getPaymentStatus($this->escape($item->payment_status)); ?>
                                </td>
                                <td class="small d-none d-md-table-cell">
                                    <?php echo EasyStoreDatabaseMapHelper::getFulfilment($this->escape($item->fulfilment)); ?>
                                </td>
                                <td class="small d-none d-md-table-cell">
                                    <?php echo $item->total_with_currency; ?>
                                </td>
                            </tr>
                        <?php endforeach;?>
                    </tbody>
                </table>

                <?php echo $this->pagination->getListFooter(); ?>
            <?php endif;?>
        </div>

        <input type="hidden" name="task" value="">
        <input type="hidden" name="boxchecked" value="0">
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>
