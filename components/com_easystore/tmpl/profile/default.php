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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

EasyStoreHelper::wa()->useStyle('com_easystore.profile.site');
?>

<div class="row justify-content-center">
    <div class="col-lg-4 col-xl-3 mb-4 mb-lg-0">
        <?php echo EasyStoreHelper::loadLayout('profile.sidebar', ['view' => 'account']); ?>
    </div>

    <div class="col-lg-8 col-xl-6">
        <div class="easystore-card easystore-card-border my-4">
            <div class="easystore-card-header">
                <span><?php echo Text::_('COM_EASYSTORE_PROFILE_TITLE'); ?></span>
                <?php if (Factory::getApplication()->getIdentity()->id == $this->item->id || $this->item->havePermission) : ?>
                    <a class="easystore-profile-edit" href="<?php echo Route::_('index.php?option=com_easystore&task=profile.edit&user_id=' . (int) $this->item->id); ?>">
                        <?php echo EasyStoreHelper::getIcon('edit'); ?> <?php echo Text::_('COM_EASYSTORE_PROFILE_EDIT'); ?>
                    </a>
                <?php endif;?>
            </div>

            <div class="easystore-card-body">
                <div class="easystore-metadata">
                    <div class="easystore-metadata-item-v">
                        <span class="easystore-metadata-key"><?php echo Text::_('COM_EASYSTORE_PROFILE_LABEL_FULL_NAME'); ?></span>
                        <span class="easystore-metadata-value"><?php echo $this->escape($this->item->name); ?></span>
                    </div>
                    <div class="easystore-metadata-item-v">
                        <span class="easystore-metadata-key"><?php echo Text::_('COM_EASYSTORE_PROFILE_LABEL_USERNAME'); ?></span>
                        <span class="easystore-metadata-value"><?php echo $this->escape($this->item->username); ?></span>
                    </div>
                    <div class="easystore-metadata-item-v">
                        <span class="easystore-metadata-key"><?php echo Text::_('COM_EASYSTORE_PROFILE_LABEL_EMAIL'); ?></span>
                        <span class="easystore-metadata-value"><?php echo $this->escape($this->item->email); ?></span>
                    </div>
                    <div class="easystore-metadata-item-v">
                        <span class="easystore-metadata-key"><?php echo Text::_('COM_EASYSTORE_USERS_PHONE'); ?></span>
                        <span class="easystore-metadata-value"><?php echo $this->escape($this->item->phone); ?></span>
                    </div>

                    <?php if (!empty($this->item->company_name)) : ?>
                        <div class="easystore-metadata-item-v">
                            <span class="easystore-metadata-key"><?php echo Text::_('COM_EASYSTORE_PROFILE_LABEL_COMPANY_NAME'); ?></span>
                            <span class="easystore-metadata-value"><?php echo $this->escape($this->item->company_name); ?></span>
                        </div>
                    <?php endif;?>

                    <?php if (!empty($this->item->company_id)) : ?>
                        <div class="easystore-metadata-item-v">
                            <span class="easystore-metadata-key"><?php echo Text::_('COM_EASYSTORE_PROFILE_LABEL_COMPANY_ID'); ?></span>
                            <span class="easystore-metadata-value"><?php echo $this->escape($this->item->company_id); ?></span>
                        </div>
                    <?php endif;?>

                    <?php if (!empty($this->item->vat_information)) : ?>
                        <div class="easystore-metadata-item-v">
                            <span class="easystore-metadata-key"><?php echo Text::_('COM_EASYSTORE_PROFILE_LABEL_VATIN'); ?></span>
                            <span class="easystore-metadata-value"><?php echo $this->escape($this->item->vat_information); ?></span>
                        </div>
                    <?php endif;?>

                    <div class="easystore-metadata-item-v">
                        <span class="easystore-metadata-key"><?php echo Text::_('COM_EASYSTORE_PROFILE_LABEL_REGISTERED_DATE'); ?></span>
                        <span class="easystore-metadata-value"><?php echo HTMLHelper::_('date', $this->item->registerDate, Text::_('COM_EASYSTORE_DATE_FORMAT_2')); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($this->item->shipping_country_state->country)) : ?>
        <div class="easystore-card easystore-card-border mb-4">
            <div class="easystore-card-header">
                <?php echo Text::_('COM_EASYSTORE_PROFILE_LABEL_SHIPPING'); ?>
            </div>

            <div class="easystore-card-body">
                <address>
                    <?php echo $this->escape($this->item->shipping_address_1); ?><br>
                    <?php echo $this->escape($this->item->shipping_address_2); ?><br>
                    <?php echo $this->escape($this->item->shipping_country_state->state); ?><br>
                    <?php echo $this->escape($this->item->shipping_city); ?><br>
                    <?php echo $this->escape($this->item->shipping_country_state->country); ?><br>
                    <?php echo $this->escape($this->item->shipping_zip_code); ?><br>
                </address>
            </div>
        </div>
        <?php endif;?>


        <!-- Billing Address -->
         <?php if (isset($this->item->billing_country_state->country)) : ?>
        <div class="easystore-card easystore-card-border">
            <div class="easystore-card-header">
                <?php echo Text::_('COM_EASYSTORE_PROFILE_LABEL_BILLING'); ?>
            </div>

            <div class="easystore-card-body">
                <?php if ($this->item->is_billing_same) : ?>
                    <?php echo Text::_('COM_EASYSTORE_PROFILE_LABEL_SAME_AS_SHIPPING'); ?>
                <?php else : ?>
                <address>
                    <?php echo $this->escape($this->item->billing_address_1); ?><br>
                    <?php echo $this->escape($this->item->billing_address_2); ?><br>
                    <?php echo $this->escape($this->item->billing_country_state->state); ?><br>
                    <?php echo $this->escape($this->item->billing_city); ?><br>
                    <?php echo $this->escape($this->item->billing_country_state->country); ?><br>
                    <?php echo $this->escape($this->item->billing_zip_code); ?><br>
                </address>
                <?php endif;?>
            </div>
        </div>
         <?php endif;?>
    </div>
</div>