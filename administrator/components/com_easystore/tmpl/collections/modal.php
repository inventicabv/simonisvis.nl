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
$function  = $app->getInput()->getCmd('function', 'jSelectCollection');

?>
<form action="<?php echo Route::_('index.php?option=com_easystore&view=collections&layout=modal&tmpl=component&function=' . $function . '&' . Session::getFormToken() . '=1', false); ?>" method="post" name="adminForm" id="adminForm">
    <div id="j-main-container" class="j-main-container easystore-container">
        <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
        <?php if (empty($this->items)) : ?>
            <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
            </div>
        <?php else : ?>
            <table class="table" id="collectionList">
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
                        <th scope="col" class="text-center">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_EASYSTORE_TOTAL_PRODUCT_COUNT', 'a.product_count', $listDirn, $listOrder); ?>
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
                        <?php if (Multilanguage::isEnabled()) : ?>
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
                <tbody>
                    <?php
                    foreach ($this->items as $i => $item) :
                        $canCreate  = $user->authorise('core.create', 'com_easystore');
                        $canEdit    = $user->authorise('core.edit', 'com_easystore');
                        $canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->get('id') || is_null($item->checked_out);
                        $canChange  = $user->authorise('core.edit.state', 'com_easystore') && $canCheckin;
                        ?>
                        <tr class="row<?php echo $i % 2; ?>" data-draggable-group="1" item-id="<?php echo $item->id; ?>" onclick="window.parent && window.parent.<?php echo $this->escape($function); ?>('<?php echo $item->id; ?>', '<?php echo $this->escape(addslashes($item->title)); ?>');" style="cursor: pointer;">
                            <th scope="row">
                                <div class="d-flex align-items-center">
                                    <div class="me-4 easystore-image-wrapper">
                                        <img src="<?php echo $item->image; ?>" alt="<?php echo $this->escape($item->title); ?>" height="64">
                                    </div>
                                    <?php echo $this->escape($item->title); ?>
                                </div>
                            </th>
                            <td class="small text-center">
                                <?php echo (int) $item->product_count; ?>
                            </td>
                            <td class="small d-none d-md-table-cell">
                                <?php if ((int) $item->created_by !== 0) : ?>
                                    <?php echo $this->escape($item->author_name); ?>
                                <?php else : ?>
                                    <?php echo Text::_('JNONE'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="small d-none d-md-table-cell">
                                <?php echo HTMLHelper::_('date', $item->created, Text::_('COM_EASYSTORE_DATE_FORMAT_2')); ?>
                            </td>
                            <td class="small d-none d-md-table-cell">
                                <?php echo $this->escape($item->access_level); ?>
                            </td>
                            <?php if (Multilanguage::isEnabled()) : ?>
                                <td class="small d-none d-md-table-cell">
                                    <?php echo LayoutHelper::render('joomla.content.language', $item); ?>
                                </td>
                            <?php endif; ?>
                            <td class="text-center">
                                <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'collections.', false); ?>
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