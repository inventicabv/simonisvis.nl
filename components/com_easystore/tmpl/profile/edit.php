<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

// Load user_profile plugin language
$lang = Factory::getApplication()->getLanguage();
$lang->load('plg_user_profile', JPATH_ADMINISTRATOR);

EasyStoreHelper::wa()->useScript('keepalive')
    ->useScript('form.validate')
    ->useStyle('com_easystore.profile.site')
    ->useScript('field.passwordview')
    ->useScript('com_easystore.profile-edit.site');
?>
<form action="<?php echo Route::_('index.php?option=com_easystore'); ?>" method="post" class="form-validate" enctype="multipart/form-data">
    <div class="row justify-content-center">
        <div class="col-lg-4 col-xl-3 mb-4 mb-lg-0">
            <?php echo EasyStoreHelper::loadLayout('profile.sidebar', ['view' => 'account']); ?>
        </div>

        <div class="col-lg-8 col-xl-6">
            <div class="easystore-card easystore-card-border my-4">
                <div class="easystore-card-header">
                    <span><?php echo Text::_('COM_EASYSTORE_PROFILE_EDIT_TITLE'); ?></span>
                </div>

                <div class="easystore-card-body">
                    <?php foreach ($this->form->getFieldset('core') as $field) : ?>
                        <?php echo $field->renderField(); ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="easystore-card easystore-card-border mb-4">
                <div class="easystore-card-header">
                    <?php echo Text::_('COM_EASYSTORE_PROFILE_LABEL_SHIPPING'); ?>
                </div>

                <div class="easystore-card-body">
                    <?php foreach ($this->form->getFieldset('shipping-address') as $field) : ?>
                        <?php echo $field->renderField(); ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php foreach ($this->form->getFieldset('check-address') as $field) : ?>
                <?php echo $field->renderField(); ?>
            <?php endforeach; ?>

            <div class="easystore-card easystore-card-border easystore-same-address">
                <div class="easystore-card-header">
                    <?php echo Text::_('COM_EASYSTORE_PROFILE_LABEL_BILLING'); ?>
                </div>
                <div class="easystore-card-body">
                    <?php foreach ($this->form->getFieldset('billing-address') as $field) : ?>
                        <?php echo $field->renderField(); ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="easystore-profile-actions mt-4">
                <button type="submit" class="btn btn-primary validate" name="task" value="profile.save" easystore-profile-save-button>
                    <?php echo EasyStoreHelper::getIcon('tick') ?>
                    <?php echo Text::_('JSAVE'); ?>
                </button>
                <button type="submit" class="btn btn-danger" name="task" value="profile.cancel" formnovalidate>
                    <?php echo EasyStoreHelper::getIcon('cross') ?>
                    <?php echo Text::_('JCANCEL'); ?>
                </button>
                <input type="hidden" name="option" value="com_easystore">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>