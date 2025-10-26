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
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_easystore.admin');

$user = Factory::getApplication()->getIdentity();
$acl = AccessControl::create();
?>
<form action="<?php echo Route::_('index.php?option=com_easystore&task=tag.add'); ?>" method="post" name="adminForm" id="adminForm">
<div class="easystore-container">
    <div class="easystore-card">
        <div class="easystore-empty-state">
            <div class="easystore-empty-state-icon mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="144" height="156" fill="none"><path fill="#F0F2FD" d="M14.228 42.574a1.51 1.51 0 1 0 0-3.02 1.51 1.51 0 0 0 0 3.02ZM140.574 77.1a3.814 3.814 0 1 0-5.12-5.654 3.814 3.814 0 0 0 5.12 5.655ZM141.73 85.957a2.418 2.418 0 1 0-3.246-3.586 2.418 2.418 0 1 0 3.246 3.586ZM21.678 37.47a2.735 2.735 0 1 0 0-5.47 2.735 2.735 0 0 0 0 5.47ZM4.326 83.001a3.674 3.674 0 1 0 0-7.349 3.674 3.674 0 0 0 0 7.349ZM11.717 126.462a3.45 3.45 0 1 0 0-6.902 3.45 3.45 0 0 0 0 6.902ZM127.72 42.49a5.02 5.02 0 1 0 .001-10.039 5.02 5.02 0 0 0-.001 10.039Z"/><path fill="#C4CAF1" d="m28.486 138.87 52.354-3.169a4.206 4.206 0 0 0 3.947-4.455l-4.238-70.003a4.208 4.208 0 0 0-1.878-3.255L48.947 38.321a4.21 4.21 0 0 0-5.425.666L20.995 63.55a4.207 4.207 0 0 0-1.1 3.098l4.136 68.274a4.207 4.207 0 0 0 4.455 3.947Z"/><path fill="#F0F2FD" d="m70.237 154.869 68.169-24.693a4.944 4.944 0 0 0 2.965-6.332L108.924 34.26a4.944 4.944 0 0 0-3.198-3.044L58.632 16.777a4.942 4.942 0 0 0-5.89 2.553L32.484 60.72a4.93 4.93 0 0 0-.206 3.857l31.628 87.326a4.947 4.947 0 0 0 6.331 2.966Z"/><path fill="#C4CAF1" d="M65.374 47.205a6.893 6.893 0 1 0 0-13.787 6.893 6.893 0 0 0 0 13.787Z"/><path stroke="#C4CAF1" stroke-width="4.52" d="M59.58 40.665c10.609 0 19.209-8.432 19.209-18.833 0-10.4-8.6-18.832-19.21-18.832C48.971 3 40.37 11.431 40.37 21.832c0 4.585 1.884 9.417 5.274 12.053"/><path fill="#AFBAFF" d="M80.315 104.373c8.975 3.317 18.94-1.269 22.258-10.245 3.317-8.975-1.27-18.94-10.245-22.257-8.975-3.318-18.94 1.269-22.258 10.244-3.317 8.975 1.27 18.941 10.245 22.258Z"/><path stroke="#fff" stroke-linecap="round" stroke-width="3.013" d="m84.048 81.735 4.547 12.774M92.709 85.85l-12.774 4.547"/></svg>
            </div>

            <div class="easystore-empty-state-title mb-2">
                <?php echo Text::_('COM_EASYSTORE_EMPTYSTATE_TAG_TITLE'); ?>
            </div>
            
            <div class="easystore-empty-state-description mb-4">
                <?php echo Text::_($acl->canCreate() ? 'COM_EASYSTORE_EMPTYSTATE_TAG_DESCRIPTION' : 'COM_EASYSTORE_EMPTY_STATE_TAG_NO_PERMISSION'); ?>
            </div>

            <?php if ($acl->canCreate() || !empty($user->getAuthorisedCategories('com_easystore', 'core.create'))) : ?>
                <div class="easystore-empty-state-actions">
                    <a href="<?php echo Route::_('index.php?option=com_easystore&task=tag.add'); ?>" class="btn btn-primary"><?php echo Text::_('COM_EASYSTORE_EMPTYSTATE_TAG_BUTTON_TEXT'); ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<input type="hidden" name="task" value="">
<input type="hidden" name="boxchecked" value="0">
<?php echo HTMLHelper::_('form.token'); ?>
</form>
