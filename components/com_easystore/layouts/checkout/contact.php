<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);
$return              = Uri::getInstance()->toString();
$loginUrl            = Route::_('index.php?option=com_users&view=login&return=' . base64_encode($return), false);
$user                = Factory::getApplication()->getIdentity();
$settings            = SettingsHelper::getSettings();
$allowGuestCheckout  = $settings->get('checkout.allow_guest_checkout', false);
$isGuestCheckout     = $user->guest && $allowGuestCheckout;
$logoutUrl           = Route::_('index.php?option=com_users&view=login&layout=logout&return=' . base64_encode($return), false);
$isLoggedInUser      = !$user->guest;
$allowCompanyName    = $settings->get('checkout.allow_company_name', 'do-not-include');
$allowCompanyId      = $settings->get('checkout.allow_company_id', 'do-not-include');
$allowVatInformation = $settings->get('checkout.allow_vat_information', 'do-not-include');
?>

<div class="easystore-widget easystore-checkout-contact">
    <div class="easystore-contact-header">
        <h3 class="easystore-widget-title mb-0"><?php echo Text::_('COM_EASYSTORE_CHECKOUT_CONTACT_LABEL'); ?></h3>

        <?php if ($isGuestCheckout) : ?>
            <p><?php echo Text::_('COM_EASYSTORE_HAVE_AN_ACCOUNT_TEXT'); ?> <a href="<?php echo $loginUrl; ?>"><?php echo Text::_('JLOGIN'); ?></a></p>
        <?php endif;?>

        <?php if ($isLoggedInUser) : ?>
            <a href="<?php echo $logoutUrl; ?>"><?php echo Text::_('JLOGOUT'); ?></a>
        <?php endif;?>
    </div>

    <?php if ($isLoggedInUser) : ?>
        <div>
            <span><?php echo $user->name; ?></span>
            <span>(<?php echo $user->email; ?>)</span>
        </div>
    <?php endif;?>

    <?php if ($isGuestCheckout) : ?>
        <div class="easystore-contact-with-spinner">
            <input
                name="email"
                type="email"
                class="form-control"
                placeholder="<?php echo Text::_('COM_EASYSTORE_CHECKOUT_EMAIL_PLACEHOLDER'); ?>"
                @change="searchGuestUser"
                :class="errors.contactError.email? 'easystore-checkout-item-error-input' : '' "
                
            />

            <span x-show="!!searchLoading" class="easystore-spinner dark small"></span>
            <div class="easystore-checkout-item-error-wrapper">
                <div x-show="showError" class="easystore-checkout-item-error-section">
                    <template x-for="error in Object.values(errors.contactError)">
                        <div x-show="!!error" class="easystore-checkout-item-error-text">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"><path fill="#DC3545" fill-rule="evenodd" d="M4.916 12a7.091 7.091 0 0 1 7.083-7.083A7.091 7.091 0 0 1 19.083 12a7.091 7.091 0 0 1-7.084 7.084A7.091 7.091 0 0 1 4.916 12Zm1.287 0c0 3.196 2.6 5.796 5.796 5.796s5.795-2.6 5.795-5.795c0-3.196-2.6-5.796-5.795-5.796a5.802 5.802 0 0 0-5.796 5.796ZM12 7.923a.86.86 0 0 0 0 1.717.86.86 0 0 0 0-1.717Zm-.644 3.649a.644.644 0 0 1 1.288 0v3.864a.644.644 0 0 1-1.288 0V11.57Z" clip-rule="evenodd"/></svg>
                            <span x-text="error"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    <?php endif;?>
    <?php if ($allowCompanyName !== 'do-not-include' || $allowCompanyId !== 'do-not-include' || $allowVatInformation !== 'do-not-include') : ?>
        <div class="easystore-compact-form mt-3">
            <?php if ($allowCompanyName !== 'do-not-include') : ?>
                <div>
                    <input type="text" class="form-control" name="company_name" placeholder="<?php echo Text::_('COM_EASYSTORE_PROFILE_LABEL_COMPANY_NAME'); ?>" x-model="information.company_name" :class="errors.companyInfoError.company_name ? 'easystore-checkout-item-error-input' : ''" />

                </div>
            <?php endif;?>

            <?php if ($allowCompanyId !== 'do-not-include') : ?>
                <div>
                    <input type="text" class="form-control" name="company_id" placeholder="<?php echo Text::_('COM_EASYSTORE_PROFILE_LABEL_COMPANY_ID'); ?>" x-model="information.company_id" :class="errors.companyInfoError.company_id ? 'easystore-checkout-item-error-input' : ''" />

                </div>
            <?php endif;?>

            <?php if ($allowVatInformation !== 'do-not-include') : ?>
                <div>
                    <input type="text" class="form-control" name="vat_information" placeholder="<?php echo Text::_('COM_EASYSTORE_USERS_VATIN'); ?>"  x-model="information.vat_information" :class="errors.companyInfoError.vat_information ? 'easystore-checkout-item-error-input' : ''" />

                </div>
            <?php endif;?>
            <div class="easystore-checkout-item-error-wrapper">
                <div x-show="showError" class="easystore-checkout-item-error-section">
                    <template x-for="error in Object.values(errors.companyInfoError)">
                        <div x-show="!!error" class="easystore-checkout-item-error-text">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"><path fill="#DC3545" fill-rule="evenodd" d="M4.916 12a7.091 7.091 0 0 1 7.083-7.083A7.091 7.091 0 0 1 19.083 12a7.091 7.091 0 0 1-7.084 7.084A7.091 7.091 0 0 1 4.916 12Zm1.287 0c0 3.196 2.6 5.796 5.796 5.796s5.795-2.6 5.795-5.795c0-3.196-2.6-5.796-5.795-5.796a5.802 5.802 0 0 0-5.796 5.796ZM12 7.923a.86.86 0 0 0 0 1.717.86.86 0 0 0 0-1.717Zm-.644 3.649a.644.644 0 0 1 1.288 0v3.864a.644.644 0 0 1-1.288 0V11.57Z" clip-rule="evenodd"/></svg>
                            <span x-text="error"></span>
                        </div>
                    </template>
                </div>
            </div>

        </div>
    <?php endif;?>
</div>
