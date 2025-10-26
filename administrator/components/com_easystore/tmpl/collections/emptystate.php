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

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_easystore.admin');
$user = Factory::getApplication()->getIdentity();

?>
<form action="<?php echo Route::_('index.php?option=com_easystore&task=collection.add'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="easystore-container">
        <div class="easystore-card">
            <div class="easystore-empty-state">
                <div class="easystore-empty-state-icon mb-4">
                    <svg width="155" height="140" fill="none" xmlns="http://www.w3.org/2000/svg"><ellipse cx="70.04" cy="116.733" rx="70.04" ry="22.374" fill="#C1C1C1"/><path d="m78 122.023-31-8.819.816-55.18 36.683 1.5-6.5 62.499Z" fill="#F4F4F4"/><path d="M69.066 133.757 28.21 114.704l1.477-50.5L70.5 80.9l-1.434 52.857Z" fill="#D4D4D4"/><path d="M111.868 113.961V64L69.5 80.5l-.5 53.25 42.868-19.789Z" fill="#F8F8F8"/><path d="M87.55 54.534 69.74 48.64 29.5 64.05l20.205 8.908 37.844-18.424Z" fill="#D6D5D5"/><path d="M76.248 69.067 44.26 55.046 70 48.5l27.5 9-21.252 11.567Z" fill="#CCC"/><path d="m50.282 95.332-40.069-15.64 19.293-15.64L69.5 80.5 56.96 64.051l42.295-16.385 12.614 16.385L69.5 80.5 50.282 95.332Z" fill="#E7E7E7"/><path fill-rule="evenodd" clip-rule="evenodd" d="M116.176 44.259c6.691-4.108 9.723-12.523 6.81-20.12-3.288-8.576-12.906-12.864-21.483-9.575-8.577 3.288-12.864 12.906-9.576 21.483 2.702 7.048 9.68 11.2 16.841 10.63l.272.708 8.706 22.708a3.89 3.89 0 1 0 7.266-2.786l-8.706-22.708-.13-.34Zm4.993-19.423c2.904 7.573-.882 16.066-8.455 18.97-7.574 2.903-16.067-.883-18.97-8.456-2.904-7.573.882-16.066 8.455-18.97 7.574-2.903 16.067.882 18.97 8.456Zm-10.313 21.852 1.817-.696 1.817-.697.696 1.817 3.831 9.992-3.634 1.393-3.83-9.992-.697-1.817Zm6.443 16.804-1.219-3.179 3.633-1.393 1.219 3.18-3.633 1.392Zm.696 1.817 1.567 4.087a1.946 1.946 0 0 0 3.633-1.393l-1.567-4.087-3.633 1.393Z" fill="#B5B5B5"/><circle cx="100.565" cy="30.035" r="1.49" transform="rotate(-15.086 100.565 30.035)" fill="#B5B5B5"/><circle cx="113.185" cy="26.633" r="1.49" transform="rotate(-15.086 113.185 26.633)" fill="#B5B5B5"/><path d="M104.352 35.766c.902-1.176 2.336-2.14 4.082-2.611 1.745-.47 3.47-.357 4.841.206M58.853 30.122l10.083 7.771m-9.194.939 8.305-9.649" stroke="#B5B5B5" stroke-width="1.946" stroke-linecap="round"/><path d="m43 41.68 7.065 5.446m-6.441.657 5.818-6.76" stroke="#B5B5B5" stroke-width="1.363" stroke-linecap="round"/></svg>
                </div>
                <h2 class="easystore-empty-state-title"><?php echo Text::_('COM_EASYSTORE_EMPTYSTATE_COLLECTION_TITLE'); ?></h2>
                <p class="easystore-empty-state-description"><?php echo Text::_('COM_EASYSTORE_EMPTYSTATE_COLLECTION_DESCRIPTION'); ?></p>

                <?php if ($user->authorise('core.create', 'com_easystore') || count($user->getAuthorisedCategories('com_easystore', 'core.create')) > 0) : ?>
                    <div class="easystore-empty-state-actions">
                        <a href="<?php echo Route::_('index.php?option=com_easystore&task=collection.add', false); ?>" class="btn btn-primary"><?php echo Text::_('COM_EASYSTORE_EMPTYSTATE_COLLECTION_BUTTON_TEXT'); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
