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
<form action="<?php echo Route::_('index.php?option=com_easystore&task=order.add'); ?>" method="post" name="adminForm" id="adminForm">
<div class="easystore-container">
    <div class="easystore-card">
        <div class="easystore-empty-state">
            <div class="easystore-empty-state-icon mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="160" height="160" fill="none"><path fill="#A6ADDF" d="M65.749 28.136c-1.85-5.002-3.707-10-5.558-15-1.01-2.724-5.333-1.354-4.316 1.394 1.85 5.001 3.707 10 5.557 14.999 1.01 2.725 5.334 1.355 4.317-1.393ZM82.188 28.405c-.044-7.576-.085-15.151-.13-22.724-.006-1.064-.012-2.128-.015-3.192-.019-2.921-4.544-2.719-4.531.196.044 7.575.085 15.15.13 22.723.005 1.064.012 2.128.015 3.193.016 2.92 4.544 2.718 4.531-.196ZM100.128 29.41c1.146-4.34 2.295-8.682 3.442-13.02.795-3.007-3.91-4.077-4.702-1.08-1.147 4.338-2.296 8.68-3.442 13.02-.796 3.005 3.909 4.076 4.702 1.08Z"/><path fill="#DDE0F9" d="m129.88 151.182-1.437-92.535-95.803.281-2.144 92.204a6.01 6.01 0 0 0 6.009 6.091h87.366a6.011 6.011 0 0 0 6.009-6.038v-.003Z"/><path fill="#C1C6EC" d="m41.67 45.16-5.765-5.772 90.546.246-5.207 5.53a9.599 9.599 0 0 0-2.659 6.612l-.019 7.74-75.06-.279.224-8.826a7.283 7.283 0 0 0-2.053-5.254l-.006.003Z"/><path fill="#7881B2" d="M43.802 47.412 43.5 59.24H32.952a.487.487 0 0 1-.344-.83L43.613 47.51"/><path fill="#DDE0F9" d="m42.953 46.48-6.783-6.988a.345.345 0 0 0-.59.252l.615 15.05 6.733-6.584c.48-.47.492-1.244.022-1.727l.003-.003Z"/><path fill="#7881B2" d="m128.238 58.091-9.647-10.152-.028 11.576 9.151-.173a.741.741 0 0 0 .524-1.25Z"/><path fill="#DDE0F9" d="m126.735 39.968-.442 15.985-6.827-7.136a1.35 1.35 0 0 1-.013-1.85l6.717-7.229a.325.325 0 0 1 .562.23h.003Z"/><path fill="#C4C9ED" d="M66.662 74.4a4.048 4.048 0 1 0 0-8.096 4.048 4.048 0 0 0 0 8.097ZM93.872 74.578a4.048 4.048 0 1 0 0-8.097 4.048 4.048 0 0 0 0 8.097Z"/><path fill="#DDE0F9" d="M70.912 51.76a2.892 2.892 0 1 0 0-5.784 2.892 2.892 0 0 0 0 5.784ZM92.514 50.017a2.89 2.89 0 1 0-5.394-2.084 2.89 2.89 0 1 0 5.394 2.084Z"/><path fill="#EFF1FB" d="M150.851 147.247a3.922 3.922 0 0 0 0-7.841 3.92 3.92 0 1 0 0 7.841ZM144.418 159.727a2.636 2.636 0 1 0-.001-5.271 2.636 2.636 0 0 0 .001 5.271ZM145.757 113.063a3.154 3.154 0 1 0 0-6.308 3.154 3.154 0 0 0 0 6.308ZM17.843 94.919a4.81 4.81 0 1 0-9.62-.208 4.81 4.81 0 0 0 9.62.208ZM8.262 110.891a3.114 3.114 0 1 0 0-6.227 3.114 3.114 0 0 0 0 6.227Z"/></svg>    
            </div>

            <div class="easystore-empty-state-title mb-2">
                <?php echo Text::_('COM_EASYSTORE_EMPTYSTATE_ORDER_TITLE'); ?>
            </div>
            
            <div class="easystore-empty-state-description mb-4">
                <?php echo Text::_('COM_EASYSTORE_EMPTYSTATE_ORDER_DESCRIPTION'); ?>
            </div>

            <?php if ($acl->canCreate() || count($user->getAuthorisedCategories('com_easystore', 'core.create')) > 0) : ?>
                <div class="easystore-empty-state-actions">
                    <a href="<?php echo Route::_('index.php?option=com_easystore&task=order.add'); ?>" class="btn btn-primary"><?php echo Text::_('COM_EASYSTORE_EMPTYSTATE_ORDER_BUTTON_TEXT'); ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<input type="hidden" name="task" value="">
<input type="hidden" name="boxchecked" value="0">
<?php echo HTMLHelper::_('form.token'); ?>
</form>