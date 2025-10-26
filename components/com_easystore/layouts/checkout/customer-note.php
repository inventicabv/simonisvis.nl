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

use Joomla\CMS\Language\Text;
?>

<div class="easystore-checkout-customer-note">
    <textarea class="form-control" name="customer_note" rows="3" placeholder="<?php echo Text::_('COM_EASYSTORE_CHECKOUT_CUSTOMER_NOTE_LABEL') ?>"></textarea>
</div>
<div class="easystore-checkout-item-error-wrapper">
<div x-show="showError" class="easystore-checkout-item-error-section">
         <template x-for="error in Object.values(errors.shippingAddressError)">
            <div x-show='!!error'class="easystore-checkout-item-error-text">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"><path fill="#DC3545" fill-rule="evenodd" d="M4.916 12a7.091 7.091 0 0 1 7.083-7.083A7.091 7.091 0 0 1 19.083 12a7.091 7.091 0 0 1-7.084 7.084A7.091 7.091 0 0 1 4.916 12Zm1.287 0c0 3.196 2.6 5.796 5.796 5.796s5.795-2.6 5.795-5.795c0-3.196-2.6-5.796-5.795-5.796a5.802 5.802 0 0 0-5.796 5.796ZM12 7.923a.86.86 0 0 0 0 1.717.86.86 0 0 0 0-1.717Zm-.644 3.649a.644.644 0 0 1 1.288 0v3.864a.644.644 0 0 1-1.288 0V11.57Z" clip-rule="evenodd"/></svg>
                <span x-text="error"></span>
            </div>
        </template>
    </div>
</div>