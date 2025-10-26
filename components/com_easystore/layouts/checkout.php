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

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

?>

<a class="btn btn-primary btn-lg d-block" href="<?php echo Route::_('index.php?option=com_easystore&view=checkout&cart_token=' . $token); ?>"><?php echo Text::_('COM_EASYSTORE_CART_ORDER_SUMMARY_BUTTON_CHECKOUT'); ?></a>
