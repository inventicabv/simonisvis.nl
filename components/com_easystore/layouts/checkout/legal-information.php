<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

$settings = SettingsHelper::getSettings();
$showTermsAndConditions = $settings->get('checkout.show_terms_and_conditions', false);
$showPrivacyPolicy = $settings->get('checkout.show_privacy_policy', false);
$termsAndConditionText = $settings->get('checkout.terms_and_conditions', false);
$privacyPolicyText = $settings->get('checkout.privacy_policy', false);

?>

<div class="d-flex flex-column easystore-checkout-legal-info">
    <?php if ($showTermsAndConditions) : ?>
        <div class="form-check form-check-inline">
            <input type="checkbox" name="terms_and_conditions" class="form-check-input" id="terms-and-conditions" x-model="legal.terms_and_conditions" required />
            <label for="terms-and-conditions" class="form-check-label">
                <?php echo $termsAndConditionText; ?>
            </label>
        </div>
    <?php endif; ?>
    
    <?php if ($showPrivacyPolicy) : ?>
        <div class="form-check form-check-inline">
            <input type="checkbox" name="privacy_policy" class="form-check-input" id="privacy-policy" x-model="legal.privacy_policy" required />
            <label for="privacy-policy" class="form-check-label">
                <?php echo $privacyPolicyText; ?>
            </label>
        </div>
    <?php endif; ?>
</div>