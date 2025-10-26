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
use Joomla\CMS\Language\Multilanguage;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;
use JoomShaper\Component\EasyStore\Site\Helper\RouteHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect')
    ->useStyle('com_easystore.admin');

$app       = Factory::getApplication();
$user      = Factory::getApplication()->getIdentity();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$function  = $app->getInput()->getCmd('function', 'jSelectProduct');
$acl = AccessControl::create();

?>
<form action="<?php echo Route::_('index.php?option=com_easystore&view=products&layout=modal&tmpl=component&function=' . $function . '&' . Session::getFormToken() . '=1'); ?>" method="post" name="adminForm" id="adminForm">
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
                        <th scope="col">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_EASYSTORE_CATEGORY', 'a.category', $listDirn, $listOrder); ?>
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
                        <?php endif; ?>
                        <th scope="col" class="w-1 text-center">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="w-5 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($this->items as $i => $item) :
                        $canCreate  = $acl->canCreate();
                        $canEdit    = $acl->canEdit() || $acl->setContext('product')->canEditOwn($item->id);
                        $canCheckin = $acl->setAsset('com_checkin')->canManage() || (int) $item->checked_out === (int) $user->id || is_null($item->checked_out);
                        $canChange  = $acl->canEditState() && $canCheckin;
                        ?>
                        <tr class="row<?php echo $i % 2; ?>" data-item-id="<?php echo $item->id; ?>" sortable-group-id="1" data-draggable-group="1" onclick="window.parent && window.parent.<?php echo $this->escape($function); ?>('<?php echo $item->id; ?>', '<?php echo $this->escape(addslashes($item->title)); ?>','<?php echo $item->catid; ?>','<?php echo $this->escape(RouteHelper::getProductRoute($item->id, $item->language)); ?>', null);" style="cursor: pointer;">
                            <th scope="row">
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($item->thumbnail)) : ?>
                                        <div class="me-4 easystore-image-wrapper">
                                            <img src="<?php echo $item->thumbnail; ?>" alt="<?php echo $this->escape($item->title); ?>" height="64" >
                                        </div>
                                    <?php else : ?>
                                        <div class="me-4 easystore-image-wrapper">
                                            <img src="<?php echo $this->defaultThumbnailSrc; ?>" alt="<?php echo $this->escape($item->title); ?>" height="64" >
                                        </div>
                                    <?php endif; ?>
                                    <span class="d-block">
                                        <?php echo $this->escape($item->title); ?>
                                        <small class="strong d-block mt-1">
                                            <?php echo Text::_('JFIELD_ALIAS_LABEL'); ?>: <?php echo $this->escape($item->alias); ?>
                                        </small>
                                    </span>
                                </div>
                            </th>

                            <td class="small d-none d-md-table-cell">                    
                                <div class="break-word small">
                                    <?php echo $this->escape($item->cat_title) ?>                             
                                </div>                                                         
                            </td>
                            <td class="d-none d-md-table-cell">                             
                                <?php if ($item->discounted_price) { ?>
                                    <del><?php echo $this->escape(EasyStoreHelper::formatCurrency($item->regular_price)); ?></del>
                                    <div class="break-down">
                                        <strong>
                                            <?php echo $this->escape(EasyStoreHelper::formatCurrency($item->discounted_price)); ?>
                                        </strong>
                                    </div>
                                    
                                <?php } else {
                                    echo $this->escape(EasyStoreHelper::formatCurrency($item->regular_price));
                                } ?>
                            </td>
                            <td>
                                <?php echo $this->escape($item->inventory_status); ?>
                            </td>
                            <?php if (Multilanguage::isEnabled()) : ?>
                                <td class="small d-none d-md-table-cell">
                                    <?php echo LayoutHelper::render('joomla.content.language', $item); ?>
                                </td>
                            <?php endif; ?>

                            <td class="text-center">
                                <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'products.', $canChange); ?>
                            </td>

                            <td class="d-none d-md-table-cell">
                                <?php echo (int) $item->id; ?>
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