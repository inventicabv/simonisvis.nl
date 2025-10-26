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
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_easystore.admin');

$user = Factory::getApplication()->getIdentity();
$acl = AccessControl::create();

?>
<form action="<?php echo Route::_('index.php?option=com_easystore&task=product.add'); ?>" method="post" name="adminForm" id="adminForm">
<div class="easystore-container">
    <div class="easystore-card">
        <div class="easystore-empty-state">
            <div class="easystore-empty-state-icon mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="146" height="129" fill="none">
                    <path
                        fill="#C4CAF1"
                        d="M7.806 0H126.78c.794 0 1.442.644 1.442 1.442V121.67c0 .794-.645 1.442-1.442 1.442H11.298a4.937 4.937 0 0 1-4.934-4.934V1.442C6.364.648 7.008 0 7.806 0Z"/><path fill="#7682B7" d="m6.367 40.605 121.859 8.27V39.63l-121.859.976Z" opacity=".36"/><path fill="#EEF1FF" d="M129.835 0H3.635C1.696 0 .125 1.525.125 3.407v33.556c0 1.882 1.571 3.407 3.51 3.407h126.2c1.939 0 3.511-1.525 3.511-3.407V3.407c0-1.882-1.572-3.407-3.511-3.407Z"/><path fill="#7682B7" d="M42.454 93.475H22.087a3.538 3.538 0 0 0-3.538 3.538v9.682a3.538 3.538 0 0 0 3.538 3.538h20.367a3.538 3.538 0 0 0 3.538-3.538v-9.682a3.538 3.538 0 0 0-3.538-3.538Z" opacity=".36"/><path fill="#DDE1FC" d="M82.33 64.028h60.929v60.083a4.268 4.268 0 0 1-4.267 4.267h-53.17a3.495 3.495 0 0 1-3.493-3.492V64.028Z"/><path fill="#7682B7" d="M88.784 94.302h21.083c.765 0 1.5-.674 1.464-1.465-.036-.79-.644-1.464-1.464-1.464H88.783c-.764 0-1.5.673-1.464 1.464.036.791.644 1.465 1.465 1.465ZM88.784 99.33h16.5c.765 0 1.501-.673 1.465-1.464-.036-.79-.645-1.465-1.465-1.465h-16.5c-.765 0-1.5.674-1.465 1.465.036.79.644 1.465 1.465 1.465ZM88.784 104.359h19.615c.765 0 1.5-.674 1.464-1.465-.035-.791-.644-1.464-1.464-1.464H88.784c-.765 0-1.5.673-1.465 1.464.036.791.644 1.465 1.465 1.465ZM143.23 110.361h-13.982c-.182 0-.299.215-.215.394l.635 1.357a.29.29 0 0 1-.007.26l-.735 1.4a.292.292 0 0 0 .01.289l.888 1.449a.294.294 0 0 1-.003.309l-.846 1.308a.291.291 0 0 0-.01.3l.758 1.389a.296.296 0 0 1 .007.267l-.664 1.377c-.095.198.039.436.237.429l13.959-.071-.029-10.457h-.003ZM82.33 77.61l60.929 5.877v-7.46l-60.93 1.583Z" opacity=".36"/><path fill="#F0F2FD" d="M144.04 61.743H81.204a1.836 1.836 0 0 0-1.836 1.836v12.436c0 1.013.822 1.835 1.836 1.835h62.836a1.836 1.836 0 0 0 1.836-1.835V63.579a1.836 1.836 0 0 0-1.836-1.836Z"
                    />
                </svg>
            </div>

            <div class="easystore-empty-state-title mb-2">
                <?php echo Text::_('COM_EASYSTORE_EMPTYSTATE_PRODUCT_TITLE'); ?>
            </div>
            
            <div class="easystore-empty-state-description mb-4">
                <?php echo Text::_($acl->canCreate() ? 'COM_EASYSTORE_EMPTYSTATE_PRODUCT_DESCRIPTION' : 'COM_EASYSTORE_EMPTYSTATE_PRODUCT_NO_PERMISSION'); ?>
            </div>

            <?php if ($acl->canCreate() || count($user->getAuthorisedCategories('com_easystore', 'core.create')) > 0) : ?>
                <div class="easystore-empty-state-actions">
                    <a href="<?php echo Route::_('index.php?option=com_easystore&task=product.add'); ?>" class="btn btn-primary"><?php echo Text::_('COM_EASYSTORE_EMPTYSTATE_PRODUCT_BUTTON_TEXT'); ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<input type="hidden" name="task" value="">
<input type="hidden" name="boxchecked" value="0">
<?php echo HTMLHelper::_('form.token'); ?>
</form>

<?php
$productImportModalData = [
    'selector' => 'easystoreProductImport',
    'params'   => [
        'title'      => Text::_('COM_EASYSTORE_PRODUCT_IMPORT_CSV'),
        'modalWidth' => 35,
    ],
    'body'     => $this->loadTemplate('import_body'),
];
?>
<?php echo LayoutHelper::render('libraries.html.bootstrap.modal.main', $productImportModalData); ?>