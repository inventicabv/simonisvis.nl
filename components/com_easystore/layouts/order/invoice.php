<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects
extract($displayData);

$isPaymentIncomplete         = $item->payment_status === 'unpaid' || $item->payment_status === 'failed' || $item->payment_status === 'canceled';
$is_paid           = $item->payment_status === 'paid';
$is_manual_payment = in_array($item->payment_method, EasyStoreHelper::getManualPaymentLists()) && $item->payment_status === 'unpaid';

$token = $item->is_guest_order ? '&guest_token=' . $item->order_token : '';

$btn_link = $isPaymentIncomplete ? 'javascript:void(0);' : Route::_('index.php?option=com_easystore&view=order&layout=invoice&id=' . (int) $item->id . $token, false);
$btn_text = $isPaymentIncomplete ? Text::_('COM_EASYSTORE_ORDER_PAY_NOW') : Text::_('COM_EASYSTORE_ORDER_INVOICE');

$click_event = $isPaymentIncomplete ? '@click="handlePaynow(' . htmlspecialchars(json_encode($item)) . ')"' : '';
?>

<a href="<?php echo $btn_link; ?>" class="btn btn-outline-primary <?php echo $is_manual_payment || (!$is_paid && !$isPaymentIncomplete) ? 'd-none' : '';?>"  <?php echo $click_event; ?>><?php echo $btn_text; ?></a>