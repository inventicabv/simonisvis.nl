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

extract($displayData);

if (!empty($customer_note)) :?>
    <div class="row mb-4">
        <div class="col-lg-12 mb-lg-0">
            <div class="easystore-card easystore-card-border h-100">
                <div class="easystore-card-body">
                    <h4 class="easystore-h4"><?php echo Text::_('COM_EASYSTORE_CUSTOMER_NOTE') ?></h4>
                    <div><?php echo $customer_note; ?></div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>