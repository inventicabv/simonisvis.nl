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
use Joomla\CMS\Router\Route;

$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_easystore.admin')
    ->useScript('keepalive')
    ->useScript('com_easystore.alpine.admin')
    ->useStyle('com_easystore.product-import.admin')
    ->useScript('com_easystore.product-import.admin');

$downloadUrl = Route::_('index.php?option=com_easystore&task=products.downloadSampleFile');
$buttonHtml  = '<a href="' . $downloadUrl . '">sample Zip file</a>';

Text::script('COM_EASYSTORE_PRODUCT_IMPORT_SELECT_VALID_FILE');

?>

<div id="easystore-admin-product-import" x-data="easystore_product_import">
    <form id="easystore-product-import-form" action="<?php echo Route::_('index.php?option=com_easystore&task=products.import'); ?>" method="post" @submit.prevent="importZip" enctype="multipart/form-data">
        <div class="easystore-product-import-body">
            <p><?php echo Text::sprintf('COM_EASYSTORE_PRODUCT_IMPORT_DOWNLOAD_SAMPLE', $buttonHtml); ?></p>
            <input class="form-control" type="file" accept=".zip" name="import_file" x-ref="zipInput" @change="checkZipValidity">
        </div>
        <div class="easystore-product-import-footer">
            <a href="https://www.joomshaper.com/documentation/easystore/import-export-products" target="_blank"><?php echo Text::_('COM_EASYSTORE_PRODUCT_IMPORT_DOCUMENTATION'); ?></a>
            <div class="easystore-product-import-footer-right">
                <button type="button" class="easystore-btn easystore-btn-secondary" @click="closeModal"><?php echo Text::_('COM_EASYSTORE_PRODUCT_IMPORT_CANCEL'); ?></button>
                <button type="submit" class="easystore-btn easystore-btn-primary" :class="`${isLoading ? 'easystore-spinner': ''}`" :disabled="!isValidZip || isLoading"><?php echo Text::_('COM_EASYSTORE_PRODUCT_IMPORT_ADD_PRODUCT'); ?></button>
            </div>
        </div>
    </form>
</div>
