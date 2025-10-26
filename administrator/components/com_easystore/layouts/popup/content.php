<?php

/**
 * @package     EasyStore.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

?>
<div class="easystore-migration-popup">
    <div class="easystore-migration-title-wrapper">
        <h3 class="easystore-migration-popup-title">
            <?php echo Text::_('COM_EASYSTORE_MIGRATION_POPUP_TITLE'); ?>
        </h3>
        <svg width="16" height="10" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.67 1.669 8.002 8.336 1.336 1.669" stroke="#5C5E62" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <div class="easystore-migration-content-wrapper">
        <p class="easystore-migration-popup-content">
            <?php echo Text::_('COM_EASYSTORE_MIGRATION_POPUP_CONTENT'); ?>
        </p>
        <a class="easystore-migration-popup-btn" href="<?php echo Route::_('index.php?option=com_easystore&view=migration'); ?>" title="<?php echo Text::_('COM_EASYSTORE_MIGRATION_POPUP_BUTTON_TEXT'); ?>">
            <?php echo Text::_('COM_EASYSTORE_MIGRATION_POPUP_BUTTON_TEXT'); ?>
        </a>
    </div>
</div>
