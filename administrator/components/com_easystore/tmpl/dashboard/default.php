<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_easystore.admin')
    ->useScript('keepalive')
    ->useScript('com_easystore.app.admin.dashboard');
?>

<div id="easystore-admin-dashboard"></div>
<div class="easystore-container">
    <p class="easystore-version"><?php echo Text::_('COM_EASYSTORE'); ?> <?php echo EasyStoreHelper::getCurrentVersion(); ?></p>
</div>