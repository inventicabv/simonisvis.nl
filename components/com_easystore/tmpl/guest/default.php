<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

?>

<form action="<?php echo Route::_('index.php?option=com_easystore'); ?>" method="post" class="form-validate">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <div class="easystore-card easystore-card-border my-4">
                <div class="easystore-card-header">
                    <span><?php echo Text::_('COM_EASYSTORE_GUEST_ORDER_TOKEN_VIEW_TITLE')?></span>
                </div>

                <div class="easystore-card-body">
                    <input type="email" name="customer_email" required placeholder="<?php echo Text::_('COM_EASYSTORE_GUEST_ORDER_TOKEN_FROM_PLACEHOLDER')?>">
                </div>
                <div class="easystore-card-footer">
                    <button type="submit" class="btn btn-primary validate" name="task" value="guest.requestToken" easystore-profile-save-button>
                        <?php echo Text::_('COM_EASYSTORE_GUEST_ORDER_TOKEN_FROM_SUBMIT')?>
                    </button>
                    <input type="hidden" name="option" value="com_easystore">
                    <?php echo HTMLHelper::_('form.token'); ?>
                </div>
                
            </div>
        </div>
    </div>
</form>