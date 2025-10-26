<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;


?>

<button class="btn btn-primary mt-4 import-button" x-show="showImportButton" :disabled="!isValidZip || showSpinner">
    <span x-show="showSpinner"><i class="fa fa-redo fa-spin"></i></span>
    <span x-show="!showSpinner"><?php echo Text::_('COM_EASYSTORE_PRODUCT_IMPORT_IMPORT_PRODUCTS'); ?></span>
</button>     
