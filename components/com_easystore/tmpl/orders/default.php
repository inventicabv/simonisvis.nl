<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Site\Helper\OrderHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

EasyStoreHelper::wa()
    ->useStyle('com_easystore.site')
    ->useStyle('com_easystore.profile.site');
?>

<div class="row justify-content-center">
    <div class="col-lg-4 col-xl-3 mb-4 mb-lg-0">
        <?php echo EasyStoreHelper::loadLayout('profile.sidebar', ['view' => 'orders']); ?>
    </div>

    <div class="col-lg-8 col-xl-9">
        <div class="com-easystore customer-orders mt-4">
            <h4><?php echo Text::_('COM_EASYSTORE_ORDERS_ORDER_HISTORY') ?></h4>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr class="text-center">
                            <th scope="col"><?php echo Text::_('COM_EASYSTORE_ORDERS_ORDER_ID') ?></th>
                            <th scope="col"><?php echo Text::_('COM_EASYSTORE_ORDERS_DATE') ?></th>
                            <th scope="col"><?php echo Text::_('COM_EASYSTORE_ORDERS_STATUS') ?></th>
                            <th scope="col"><?php echo Text::_('COM_EASYSTORE_ORDERS_PAYMENT_STATUS') ?></th>
                            <th scope="col"><?php echo Text::_('COM_EASYSTORE_ORDERS_TOTAL_AMOUNT') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->items as $item) : ?>
                            <tr>
                                <th class="text-center" scope="row">
                                    <a href="<?php echo Route::_('index.php?option=com_easystore&view=order&id=' . (int) $item->id, false); ?>">
                                        <?php echo OrderHelper::formatOrderNumber($item->id); ?>
                                    </a>
                                </th>
                                <td><?php echo HTMLHelper::_('date', $item->created, 'DATE_FORMAT_LC2'); ?></td>
                                <td class="text-center"><?php echo $this->escape($item->published); ?></td>
                                <td class="text-center"><span class="badge rounded-pill text-bg-<?php echo EasyStoreHelper::getPaymentBadgeColor($item->payment_status); ?>"><?php echo EasyStoreHelper::getPaymentStatusString($item->payment_status); ?></span></td>
                                <td class="text-end"><?php echo $item->total_with_currency; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
