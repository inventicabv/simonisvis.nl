<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2024, JoomShaper
 * @license     MIT
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();

$wa->useScript('keepalive')
    ->useScript('form.validate')
    ->useStyle('com_easystore.admin');

$app   = Factory::getApplication();
$input = $app->getInput();

$this->useCoreUI = true;

// In case of modal
$isModal = $input->get('layout') === 'modal';
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $isModal || $input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';
?>

<form action="<?php echo Route::_('index.php?option=com_easystore&layout=' . $layout . $tmpl . '&id=' . (int) $this->item->id, false); ?>" method="post" name="adminForm" id="collection-form" aria-label="<?php echo Text::_('COM_EASYSTORE_COLLECTION_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>" class="form-validate">
    <div class="easystore-container">
        <div class="row gx-lg-5">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>
                        <legend><?php echo $this->form->getLabel('description'); ?></legend>
                        <?php echo $this->form->getInput('description'); ?>
                    </div>
                </div>

                <?php echo $this->form->getInput('products'); ?>

                <div class="easystore-card mb-4">
                    <div class="easystore-card-body">
                        <?php echo $this->form->renderFieldset('metadata'); ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="easystore-card mb-4">
                    <div class="easystore-card-body">
                        <legend><?php echo $this->form->getLabel('published'); ?></legend>
                        <?php echo $this->form->getInput('published'); ?>
                    </div>
                </div>
                <div class="easystore-card mb-4">
                    <div class="easystore-card-body">
                        <legend><?php echo $this->form->getLabel('image'); ?></legend>
                        <?php echo $this->form->getInput('image'); ?>
                    </div>
                </div>

                <div class="easystore-card mb-4">
                    <div class="easystore-card-body">
                        <?php echo $this->form->renderFieldset('others'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="task" value="">
    <input type="hidden" name="forcedLanguage" value="<?php echo $input->get('forcedLanguage', '', 'cmd'); ?>">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
