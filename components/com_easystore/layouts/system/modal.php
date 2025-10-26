<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

EasyStoreHelper::wa()
    ->useScript('com_easystore.alpine.site');
?>

<div id="easystore-modal" tabindex="-1" aria-modal="true" role="dialog" aria-live="true" aria-busy="false">
    <div class="modal-container center-center">
        <button type="button" class="btn-close" close-easystore-modal aria-label="<?php echo Text::_('COM_EASYSTORE_CLOSE'); ?>"></button>
        <div class="modal-content"></div>
    </div>
    <div class="modal-backdrop"></div>
</div>