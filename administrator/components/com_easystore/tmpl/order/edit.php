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


$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
    ->useStyle('com_easystore.order.admin')
    ->useScript('com_easystore.app.admin.order');

?>

<div id="easystore-order-root"></div>