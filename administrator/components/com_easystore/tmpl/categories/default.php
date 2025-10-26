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
use Joomla\CMS\Uri\Uri;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_easystore.admin')
    ->useScript('table.columns')
    ->useScript('multiselect');

$user      = Factory::getApplication()->getIdentity();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = ($listOrder == 'a.lft' && strtolower($listDirn) == 'asc');
$acl = AccessControl::create();

if ($saveOrder && !empty($this->items)) {
    $saveOrderingUrl = 'index.php?option=com_easystore&task=categories.saveOrderAjax&' . Session::getFormToken() . '=1';
    HTMLHelper::_('draggablelist.draggable');
}
?>
<form action="<?php echo Route::_('index.php?option=com_easystore&view=categories'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <div class="easystore-container">
                    <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
                    <?php if (empty($this->items)) : ?>
                        <div class="alert alert-info">
                            <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                            <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                        </div>
                    <?php else : ?>
                        <table class="table" id="categoryList">
                            <caption class="visually-hidden">
                                <?php echo Text::_('COM_EASYSTORE_CATEGORIES_TABLE_CAPTION'); ?>,
                                <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                                <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                            </caption>
                            <thead>
                                <tr>
                                    <td class="w-1 text-center">
                                        <?php echo HTMLHelper::_('grid.checkall'); ?>
                                    </td>
                                    <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                                        <?php echo HTMLHelper::_('searchtools.sort', '', 'a.lft', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
                                    </th>
                                    <th scope="col" class="w-1 text-center">
                                        <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
                                    </th>
                                    <th scope="col">
                                        <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
                                    </th>
                                    <th scope="col" class="w-10 d-none d-md-table-cell">
                                        <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'access_level', $listDirn, $listOrder); ?>
                                    </th>
                                    <?php if (Multilanguage::isEnabled()) : ?>
                                        <th scope="col" class="w-10 d-none d-md-table-cell">
                                            <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'language_title', $listDirn, $listOrder); ?>
                                        </th>
                                    <?php endif; ?>
                                    <th scope="col" class="w-5 d-none d-md-table-cell">
                                        <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody 
                                <?php if ($saveOrder) :
                                    ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="false" <?php
                                endif; ?>>
                                <?php foreach ($this->items as $i => $item) : ?>
                                    <?php
                                    $asset = 'com_easystore.category.' . $item->id;
                                    $canEdit = $acl->setAsset($asset)->canEdit() || $acl->setAsset($asset)->setContext('category')->canEditOwn($item->id);
                                    $canCheckin = $acl->setAsset('com_checkin')->isAdmin() || (int) $item->checked_out === (int) $userId || is_null($item->checked_out);
                                    $canChange = $acl->setAsset($asset)->canEditState() && $canCheckin;

                                    // Get the parents of item for sorting
                                    if ($item->level > 1) {
                                        $parentsStr = '';
                                        $_currentParentId = $item->parent_id;
                                        $parentsStr = ' ' . $_currentParentId;
                                        for ($i2 = 0; $i2 < $item->level; $i2++) {
                                            foreach ($this->ordering as $k => $v) {
                                                    $v = implode('-', $v);
                                                    $v = '-' . $v . '-';
                                                if (strpos($v, '-' . $_currentParentId . '-') !== false) {
                                                    $parentsStr .= ' ' . $k;
                                                    $_currentParentId = $k;
                                                    break;
                                                }
                                            }
                                        }
                                    } else {
                                        $parentsStr = '';
                                    }
                                    ?>
                                    <tr class="row<?php echo $i % 2; ?>" data-draggable-group="<?php echo $item->parent_id; ?>" data-item-id="<?php echo $item->id ?>" data-parents="<?php echo $parentsStr ?>" data-level="<?php echo $item->level ?>">
                                        <td class="text-center">
                                            <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
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
                                                <input type="text" class="hidden" name="order[]" size="5" value="<?php echo $item->lft; ?>">
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'categories.', $canChange); ?>
                                        </td>
                                        <th scope="row" style="display: flex;">
                                            <?php if (!empty($item->image)) :?>
                                                <div style="margin-right: 10px;">
                                                    <a href="<?php echo Route::_('index.php?option=com_easystore&task=category.edit&id=' . $item->id); ?>">
                                                        <img class="easystore-category-list-img" src="<?php echo Uri::root() . $item->image; ?>" alt="<?php echo $this->escape($item->title); ?>" style="height: 40px; width: auto;">
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <?php $prefix = LayoutHelper::render('joomla.html.treeprefix', ['level' => $item->level]); ?>
                                                <?php echo $prefix; ?>
                                                <?php if ($item->checked_out) : ?>
                                                    <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'categories.', $canCheckin); ?>
                                                <?php endif; ?>
                                                <?php if ($canEdit) : ?>
                                                    <a href="<?php echo Route::_('index.php?option=com_easystore&task=category.edit&id=' . $item->id); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->title); ?>">
                                                        <?php echo $this->escape($item->title); ?></a>
                                                <?php else : ?>
                                                    <?php echo $this->escape($item->title); ?>
                                                <?php endif; ?>
                                                <div>
                                                    <?php echo $prefix; ?>
                                                    <span class="small">
                                                        <?php if (empty($item->note)) : ?>
                                                                <?php echo Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
                                                        <?php else : ?>
                                                                <?php echo Text::sprintf('JGLOBAL_LIST_ALIAS_NOTE', $this->escape($item->alias), $this->escape($item->note)); ?>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </th>
                                        <td class="small d-none d-md-table-cell">
                                            <?php echo $this->escape($item->access_level); ?>
                                        </td>
                                        <?php if (Multilanguage::isEnabled()) : ?>
                                            <td class="small d-none d-md-table-cell">
                                                <?php echo LayoutHelper::render('joomla.content.language', $item); ?>
                                            </td>
                                        <?php endif; ?>
                                        <td class="d-none d-md-table-cell">
                                            <?php echo (int) $item->id; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php // load the pagination. ?>
                        <?php echo $this->pagination->getListFooter(); ?>

                        <?php // Load the batch processing form. ?>
                        <?php
                        if ($acl->canCreate() && $acl->canEdit() && $acl->canEditState()) : ?>
                            <?php
                                echo HTMLHelper::_(
                                    'bootstrap.renderModal',
                                    'collapseModal',
                                    [
                                        'title'  => Text::_('COM_EASYSTORE_CATEGORIES_BATCH_OPTIONS'),
                                        'footer' => $this->loadTemplate('batch_footer'),
                                    ],
                                    $this->loadTemplate('batch_body')
                                ); ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>