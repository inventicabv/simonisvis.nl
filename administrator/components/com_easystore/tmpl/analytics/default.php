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
$wa->useStyle('com_easystore.admin')
    ->useScript('keepalive')
    ->useScript('com_easystore.app.admin.analytics');
?>

<div class="easystore-container">
    <div id="easystore-admin-analytics"></div>
</div>