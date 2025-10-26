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
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\HTML\Helpers\StringHelper;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;

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
$acl = AccessControl::create();
?>
<form action="<?php echo Route::_('index.php?option=com_easystore&view=reviews'); ?>" method="post" name="adminForm" id="adminForm">
    <div id="j-main-container" class="j-main-container">
        <div class="easystore-container">
            <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
            <?php if (empty($this->items)) : ?>
                <div class="alert alert-info">
                    <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                    <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                </div>
            <?php else : ?>
                <table class="table" id="reviewList">
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
                            <th scope="col" class="w-50">
                                <?php echo Text::_('COM_EASYSTORE_MANAGER_REVIEW'); ?>
                            </th>
                            <th scope="col">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_EASYSTORE_REVIEWS_RATINGS', 'a.rating', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_EASYSTORE_FIELDSET_CONFIG_PRODUCT_OPTIONS_LABEL', 'product_name', $listDirn, $listOrder); ?>
                            </th>
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
                            $canEdit    = $acl->canEdit() || $acl->setContext('review')->canEditOwn($item->id);
                            $canChange  = $acl->canEditState();
                            ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $this->escape($item->user_name)); ?>
                                </td>
                                <td>
                                    <!-- Avatar will be added later -->
                                    <div class="d-flex">
                                        <!-- <div>Avatar will go here</div> -->
                                        <div class="d-inline-flex align-items-center">
                                            <strong><?php echo $this->escape($item->user_name); ?></strong>
                                            <small class="text-muted ms-2"><?php echo HTMLHelper::_('date', $item->created, 'DATE_FORMAT_LC3'); ?></small>
                                        </div>
                                    </div>

                                    <div class="mt-2">
                                        <?php if ($canEdit) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_easystore&task=review.edit&id=' . $item->id); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->subject); ?>">
                                                <?php echo $this->escape($item->subject); ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo $this->escape($item->subject); ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mt-2">
                                        <?php echo $this->escape(StringHelper::truncate(strip_tags($item->review), 170)); ?>
                                    </div>
                                </td>

                                <td class="d-none d-md-table-cell">
                                    <div class="d-flex align-items-center">
                                        <?php
                                            echo LayoutHelper::render(
                                                'ratings',
                                                [
                                                'count' => $item->rating,
                                                'showCount' => false,
                                                'showStar' => true
                                                ],
                                                JPATH_ROOT . '/components/com_easystore/layouts'
                                            );
                                        ?>
                                    </div>
                                </td>

                                <td class="small d-none d-md-table-cell">
                                    <?php echo $this->escape($item->product_name); ?>
                                </td>
                                
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'reviews.', $canChange); ?>
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
        </div>

        <input type="hidden" name="task" value="">
        <input type="hidden" name="boxchecked" value="0">
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>