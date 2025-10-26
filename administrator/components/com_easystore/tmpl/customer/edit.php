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

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_easystore.admin')
    ->useScript('keepalive')
    ->useScript('form.validate')
    ->useScript('com_easystore.customer.admin');

// Fieldsets to not automatically render by /layouts/joomla/edit/params.php
$this->ignore_fieldsets = ['jmetadata'];
$this->useCoreUI = true;
?>

<form action="<?php echo Route::_('index.php?option=com_easystore&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="item-form" aria-label="<?php echo Text::_('COM_EASYSTORE_MANAGER_CUSTOMER_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>" class="form-vertical form-validate">
    <div class="easystore-container">
        <div class="row gx-lg-5">
            <div class="col-lg-8">
                <div class="easystore-card mb-4">
                    <div class="easystore-card-body">
                        <fieldset id="fieldset-user" class="options-form">
                            <legend><?php echo Text::_('COM_EASYSTORE_USER_BASIC'); ?></legend>
                            <div>
                               <?php echo $this->form->renderFieldset('basic'); ?>
                            </div>        
                        </fieldset>
                    </div>
                </div>
                <div class="easystore-card mb-4">
                    <div class="easystore-card-body">
                        <fieldset id="fieldset-shipping_address" class="options-form">
                            <legend><?php echo Text::_('COM_EASYSTORE_USERS_SHIPPING_ADDRESS'); ?></legend>
                            <div>
                               <?php echo $this->form->renderFieldset('shipping_address'); ?>
                            </div>        
                        </fieldset>
                    </div>
                </div>
                <div class="easystore-card mb-4">
                    <div class="easystore-card-body">
                        <fieldset id="fieldset-billing_address" class="options-form">
                            <legend><?php echo Text::_('COM_EASYSTORE_USERS_billing_ADDRESS'); ?></legend>
                            <div>
                               <?php echo $this->form->renderFieldset('billing_address'); ?>
                            </div>        
                        </fieldset>
                    </div>
                </div>
            </div>
    
            <div class="col-lg-4">      
                <div class="easystore-card mb-4">
                    <div class="easystore-card-body">
                        <?php echo $this->form->renderFieldset('publishing'); ?>
                    </div>
                </div>
            </div>
        </div>
    
        <input type="hidden" name="task" value="">
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>