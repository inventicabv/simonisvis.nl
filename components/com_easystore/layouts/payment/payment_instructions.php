<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\HTML\HTMLHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

?>

<?php if ($manual_payment->payment_instruction) : ?>
<div class="easystore-manual-payment-instructions">
    <?php echo HTMLHelper::_('content.prepare', $manual_payment->payment_instruction); ?>
</div>
<?php endif; ?>

<?php if ($manual_payment->additional_information) : ?>
<div class="easystore-manual-payment-additional-info mt-3">
    <?php echo HTMLHelper::_('content.prepare', $manual_payment->additional_information); ?>
</div>
<?php endif; ?>