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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_easystore.admin')
    ->useScript('table.columns')
    ->useScript('multiselect');

$app       = Factory::getApplication();
$user      = $app->getIdentity();
$userId    = (int) $user->id;
$component = $this->state->get('filter.component');
$section   = $this->state->get('filter.section');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$ordering  = ($listOrder === 'a.ordering');
$saveOrder = ($listOrder === 'a.ordering' && strtoupper($listDirn) === 'ASC');
$acl       = AccessControl::create();

if ($saveOrder && !empty($this->items)) {
    $saveOrderingUrl = 'index.php?option=com_easystore&task=collections.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
    HTMLHelper::_('draggablelist.draggable');
}
?>

<form action="<?php echo Route::_('index.php?option=com_easystore&view=collections', false); ?>" method="post" name="adminForm" id="adminForm">
    <div id="j-main-container" class="j-main-container easystore-container">
        <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this, 'options' => ['selectorFieldName' => 'context']]); ?>

        <?php if (empty($this->items)): ?>
            <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
            </div>
        <?php else: ?>
            <table class="table" id="collectionList">
                <caption class="visually-hidden">
                    <?php echo Text::_('COM_FIELDS_FIELDS_TABLE_CAPTION'); ?>,
                    <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                    <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                </caption>
                <thead>
                    <tr>
                        <td class="w-1 text-center">
                            <?php echo HTMLHelper::_('grid.checkall'); ?>
                        </td>
                        <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
                        </th>
                        <th scope="col">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="text-center">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_EASYSTORE_TOTAL_PRODUCT_COUNT', 'product_count', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="w-10 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JAUTHOR', 'a.created_by', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="w-10 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_FIELD_CREATED_LABEL', 'a.created', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="w-10 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'a.access', $listDirn, $listOrder); ?>
                        </th>
                        <?php if (Multilanguage::isEnabled()): ?>
                            <th scope="col" class="w-10 d-none d-md-table-cell">
                                <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'a.language', $listDirn, $listOrder); ?>
                            </th>
                        <?php endif; ?>
                        <th scope="col" class="w-1 text-center">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="w-5 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                        </th>
                    </tr>
                </thead>
                <tbody <?php if ($saveOrder): ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true" <?php endif; ?>>
                    <?php foreach ($this->items as $i => $item): ?>
                        <?php
                        $canEdit    = $acl->canEdit() || $acl->setContext('collection')->canEditOwn($item->id);
                        $canCheckin = $acl->setAsset('com_checkin')->canManage() || (int) $item->checked_out === $userId || is_null($item->checked_out);
                        $canChange  = $acl->canEditState() && $canCheckin;
                        ?>


                        <tr class="row<?php echo $i % 2; ?>" data-draggable-group="1" item-id="<?php echo $item->id; ?>">
                            <td class="text-center">
                                <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
                            </td>
                            <td class="text-center d-none d-md-table-cell">
                                <?php $iconClass = ''; ?>
                                <?php if (!$canChange): ?>
                                    <?php $iconClass = ' inactive'; ?>
                                <?php elseif (!$saveOrder): ?>
                                    <?php $iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED'); ?>
                                <?php endif; ?>
                                <span class="sortable-handler<?php echo $iconClass; ?>">
                                    <span class="icon-ellipsis-v" aria-hidden="true"></span>
                                </span>
                                <?php if ($canChange && $saveOrder): ?>
                                    <input type="text" class="hidden" name="order[]" size="5" value="<?php echo $item->ordering; ?>">
                                <?php endif; ?>
                            </td>
                            <th scope="row">
                                <div class="d-flex align-items-center">
                                    <div class="me-4 easystore-image-wrapper">
                                        <img src="<?php echo $item->image; ?>" alt="<?php echo $this->escape($item->title); ?>" height="64">
                                    </div>
                                    <span class="d-block">
                                        <?php if ($item->checked_out): ?>
                                            <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'collections.', $canCheckin); ?>
                                        <?php endif; ?>
                                    </span>
                                    <?php if ($canEdit): ?>
                                        <a href="<?php echo Route::_('index.php?option=com_easystore&task=collection.edit&id=' . $item->id, false); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->title); ?>">
                                            <?php echo $this->escape($item->title); ?></a>
                                    <?php else: ?>
                                        <?php echo $this->escape($item->title); ?>
                                    <?php endif; ?>
                                </div>
                            </th>
                            <td class="small text-center">
                                <?php echo (int) $item->product_count; ?>
                            </td>
                            <td class="small d-none d-md-table-cell">
                                <?php if ((int) $item->created_by !== 0): ?>
                                    <a href="<?php echo Route::_('index.php?option=com_users&task=user.edit&id=' . (int) $item->created_by); ?>">
                                        <?php echo $this->escape($item->author_name); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo Text::_('JNONE'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="small d-none d-md-table-cell">
                                <?php echo HTMLHelper::_('date', $item->created, Text::_('COM_EASYSTORE_DATE_FORMAT_2')); ?>
                            </td>
                            <td class="small d-none d-md-table-cell">
                                <?php echo $this->escape($item->access_level); ?>
                            </td>
                            <?php if (Multilanguage::isEnabled()): ?>
                                <td class="small d-none d-md-table-cell">
                                    <?php echo LayoutHelper::render('joomla.content.language', $item); ?>
                                </td>
                            <?php endif; ?>
                            <td class="text-center">
                                <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'collections.', $canChange, 'cb'); ?>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <span><?php echo (int) $item->id; ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php echo $this->pagination->getListFooter(); ?>
        <?php endif; ?>

        <input type="hidden" name="task" value="">
        <input type="hidden" name="boxchecked" value="0">
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>