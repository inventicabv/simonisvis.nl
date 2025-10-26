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

use Joomla\CMS\Button\FeaturedButton;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_easystore.admin')
    ->useScript('table.columns')
    ->useScript('multiselect');

/** @var CMSApplication */
$app       = Factory::getApplication();
$user      = Factory::getApplication()->getIdentity();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = ($listOrder === 'a.ordering' && (strtolower($listDirn) === 'desc' || strtolower($listDirn) === 'asc'));

if ($saveOrder && !empty($this->items)) {
    $saveOrderingUrl = 'index.php?option=com_easystore&task=products.saveOrderAjax&' . Session::getFormToken() . '=1';
    HTMLHelper::_('draggablelist.draggable');
}

$acl = AccessControl::create();

?>
<form action="<?php echo Route::_('index.php?option=com_easystore&view=products'); ?>" method="post" name="adminForm" id="adminForm">
    <div id="j-main-container" class="j-main-container easystore-container">
        <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

        <?php if (empty($this->items)) : ?>
            <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
            </div>
        <?php else : ?>
            <table class="table" id="productList">
                <caption class="visually-hidden">
                    <?php echo Text::_('COM_TAGS_TABLE_CAPTION'); ?>,
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
                        <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JFEATURED', 'a.featured', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_EASYSTORE_BRANDS', 'a.brand_id', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_EASYSTORE_CATEGORY', 'a.catid', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" >
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_EASYSTORE_PRODUCT_PRICE', 'a.regular_price', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_EASYSTORE_PRODUCT_FIELD_INVENTORY_STATUS', 'a.inventory_status', $listDirn, $listOrder); ?>
                        </th>
                        <?php if (Multilanguage::isEnabled()) : ?>
                            <th scope="col" class="w-10 d-none d-md-table-cell">
                                <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'a.language', $this->state->get('list.direction'), $this->state->get('list.ordering')); ?>
                            </th>
                        <?php endif;?>
                        <th scope="col" class="w-1 text-center">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="w-5 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                        </th>
                    </tr>
                </thead>
                <tbody <?php if ($saveOrder) :
                    ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true" <?php
                       endif;?>>
                    <?php
                    foreach ($this->items as $i => $item) :
                        $canCheckin = $acl->setAsset('com_checkin')->canManage() || $item->checked_out === $user->id || is_null($item->checked_out);
                        $canChange  = $acl->canEditState() && $canCheckin;
                        ?>
                        <tr class="row<?php echo $i % 2; ?>" data-item-id="<?php echo $item->id; ?>" sortable-group-id="1" data-draggable-group="1">
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
                                    <input type="text" class="hidden" name="order[]" size="5" value="<?php echo $item->ordering; ?>">
                                <?php endif;?>
                            </td>

                            <th scope="row" class="text-center d-none d-md-table-cell">
                                <?php
                                $options = [
                                'task_prefix' => 'products.',
                                'disabled'    => !$canChange,
                                'id'          => 'featured-' . $item->id,
                                ];

                                echo (new FeaturedButton())->render((int) $item->featured, $i, $options);
                                ?>
                            </th>

                            <th scope="row">
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($item->thumbnail)) : ?>
                                        <?php
                                        $isVideoFile = EasyStoreHelper::validateFileType($item->thumbnail);
                                        ?>
                                        <span class="me-4 easystore-image-wrapper">
                                            <?php if ($isVideoFile) : ?>
                                                <video src="<?php echo $item->thumbnail; ?>" alt="<?php echo $this->escape($item->title); ?>" height="64"></video>
                                            <?php else : ?>
                                                <img src="<?php echo $item->thumbnail; ?>" alt="<?php echo $this->escape($item->title); ?>" height="64" >
                                            <?php endif;?>
                                        </span>
                                    <?php else : ?>
                                        <div class="me-4 easystore-image-wrapper">
                                            <img src="<?php echo $this->defaultThumbnailSrc; ?>" alt="<?php echo $this->escape($item->title); ?>" height="64" >
                                        </div>
                                    <?php endif;?>

                                    <span class="d-block">
                                        <?php if ($item->checked_out) : ?>
                                            <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'products.', $canCheckin); ?>
                                        <?php endif;?>
                                        <?php if ($acl->canEdit() || $acl->setContext('product')->canEditOwn($item->id)) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_easystore&task=product.edit&id=' . $item->id); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->title); ?>">
                                                <?php echo $this->escape($item->title); ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo $this->escape($item->title); ?>
                                        <?php endif;?>
                                        <small class="mt-1 strong d-block">
                                            <?php echo Text::_('JFIELD_ALIAS_LABEL'); ?>: <?php echo $this->escape($item->alias); ?>
                                        </small>
                                    </span>
                                </div>
                            </th>
                            <td class="small d-none d-md-table-cell">
                                <?php if ($acl->canEdit()) : ?>
                                    <div class="break-word small">
                                        <?php if (!empty($item->brand_title)) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_easystore&task=brand.edit&id=' . $item->brand_id); ?>">
                                            <?php echo $this->escape($item->brand_title); ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo Text::_('COM_EASYSTORE_NONE'); ?>
                                        <?php endif;?>
                                    </div>
                                <?php else : ?>
                                    <div class="break-word small">
                                        <?php echo $this->escape($item->brand_title) ?>
                                    </div>
                                <?php endif;?>
                            </td>

                            <td class="small d-none d-md-table-cell">
                                <?php if ($acl->canEdit()) : ?>
                                    <div class="break-word small">
                                        <?php if (!empty($item->cat_title)) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_easystore&task=category.edit&id=' . $item->catid); ?>">
                                            <?php echo $this->escape($item->cat_title); ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo Text::_('COM_EASYSTORE_NONE'); ?>
                                        <?php endif;?>
                                    </div>
                                <?php else : ?>
                                    <div class="break-word small">
                                        <?php echo $this->escape($item->cat_title) ?>
                                    </div>
                                <?php endif;?>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <?php if ($item->discounted_price) {?>
                                    <del><?php echo EasyStoreHelper::formatCurrency($item->regular_price); ?></del>
                                    <div class="break-down">
                                        <strong>
                                            <?php echo EasyStoreHelper::formatCurrency($item->discounted_price); ?>
                                        </strong>
                                    </div>

                                <?php } else {
                                    echo EasyStoreHelper::formatCurrency($item->regular_price);
                                }?>
                            </td>
                            <td>
                                <?php echo $this->escape($item->inventory_status); ?>
                            </td>
                            <?php if (Multilanguage::isEnabled()) : ?>
                                <td class="small d-none d-md-table-cell">
                                    <?php echo LayoutHelper::render('joomla.content.language', $item); ?>
                                </td>
                            <?php endif;?>

                            <td class="text-center">
                                <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'products.', $canChange); ?>
                            </td>

                            <td class="d-none d-md-table-cell">
                                <?php echo (int) $item->id; ?>
                            </td>
                        </tr>
                    <?php endforeach;?>
                </tbody>
            </table>

            <?php echo $this->pagination->getListFooter(); ?>

        <?php endif;?>

        <input type="hidden" name="task" value="">
        <input type="hidden" name="boxchecked" value="0">
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>

<form action="<?php echo Route::_('index.php?option=com_easystore&view=products'); ?>" method="post" name="exportCSV" id="exportCSV">
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="cids" value="" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>


<?php
$productImportModalData = [
    'selector' => 'easystoreProductImport',
    'params'   => [
        'title'      => Text::_('COM_EASYSTORE_PRODUCT_IMPORT_CSV'),
        'modalWidth' => 35,
    ],
    'body'     => $this->loadTemplate('import_body'),
];
?>
<?php echo LayoutHelper::render('libraries.html.bootstrap.modal.main', $productImportModalData); ?>
