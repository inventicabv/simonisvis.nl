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

$acl = AccessControl::create();

$user = Factory::getApplication()->getIdentity();
?>
<form action="<?php echo Route::_('index.php?option=com_easystore&task=category.add'); ?>" method="post" name="adminForm" id="adminForm">
<div class="easystore-container">
    <div class="easystore-card">
        <div class="easystore-empty-state">
            <div class="easystore-empty-state-icon mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="155" height="124" fill="none"><path fill="#C4CAF1" d="M114.338 7.776 20.884 22.18a3.93 3.93 0 0 0-3.285 4.483L32 120.115a3.93 3.93 0 0 0 4.483 3.286l93.454-14.402a3.93 3.93 0 0 0 3.285-4.483l-14.402-93.454a3.93 3.93 0 0 0-4.483-3.286Z"/><path fill="#DDE1FC" d="m126.11 6.122-94.327 6.596a3.93 3.93 0 0 0-3.646 4.195l6.596 94.326a3.93 3.93 0 0 0 4.195 3.647l94.326-6.596a3.931 3.931 0 0 0 3.647-4.195l-6.596-94.326a3.93 3.93 0 0 0-4.195-3.647Z"/><path fill="#F0F2FD" d="M137.308 4H42.751a3.93 3.93 0 0 0-3.93 3.93v94.557a3.93 3.93 0 0 0 3.93 3.93h94.557a3.93 3.93 0 0 0 3.93-3.93V7.93a3.93 3.93 0 0 0-3.93-3.93Z"/><path fill="#D7DCFF" d="M97.947 49.526H78.522c-1.3 0-2.245-.598-2.592-1.62-.493-1.442.516-2.962 2.09-3.135.224-.023.447-.014.67-.014h38.55c.347 0 .703.01 1.036.091 1.213.293 1.971 1.346 1.857 2.528-.11 1.128-1.054 2.04-2.241 2.14-.273.023-.547.014-.821.014H97.947v-.004ZM63.473 51.907c-2.706 0-4.914-2.154-4.91-4.782 0-2.624 2.227-4.768 4.933-4.76 2.701.005 4.928 2.168 4.919 4.783-.01 2.62-2.236 4.764-4.942 4.76ZM98.047 28.01c6.498 0 13.001-.004 19.499 0 1.848 0 3.061 1.598 2.441 3.19-.37.95-1.114 1.433-2.099 1.566-.196.027-.397.018-.598.018H78.668c-1.383 0-2.327-.552-2.71-1.574-.548-1.456.488-3.058 2.071-3.19.274-.023.548-.01.822-.01h19.197ZM63.469 25.624c2.715 0 4.937 2.126 4.946 4.75.01 2.61-2.218 4.782-4.914 4.791-2.697.01-4.933-2.149-4.938-4.763 0-2.633 2.19-4.773 4.91-4.778h-.004ZM97.947 66.263c-6.448 0-12.9.009-19.347-.014-.552 0-1.16-.119-1.638-.374-.858-.456-1.255-1.269-1.137-2.254.123-1.013.73-1.675 1.684-1.985.347-.114.735-.133 1.104-.133 12.9-.004 25.795-.004 38.695 0 .347 0 .703.028 1.032.11a2.356 2.356 0 0 1 1.788 2.51c-.105 1.1-1.036 2.021-2.167 2.126-.297.027-.598.018-.895.018H97.943l.004-.004ZM63.45 68.65c-2.715-.01-4.9-2.15-4.891-4.791.009-2.615 2.258-4.769 4.95-4.746 2.707.023 4.92 2.19 4.901 4.805-.018 2.624-2.24 4.74-4.96 4.727v.005ZM97.947 83.005c-6.448 0-12.9.01-19.347-.013-.552 0-1.16-.119-1.638-.374-.858-.457-1.255-1.269-1.137-2.255.123-1.013.73-1.674 1.684-1.985.347-.114.735-.132 1.104-.132 12.9-.004 25.795-.004 38.695 0 .347 0 .703.028 1.032.11a2.356 2.356 0 0 1 1.788 2.51c-.105 1.099-1.036 2.02-2.167 2.126-.297.027-.598.018-.895.018H97.943l.004-.005ZM63.45 85.391c-2.715-.01-4.9-2.15-4.891-4.791.009-2.615 2.258-4.769 4.95-4.746 2.707.023 4.92 2.19 4.901 4.805-.018 2.624-2.24 4.741-4.96 4.727v.005Z"/><path fill="#AFBAFF" d="M131.596 117.476c9.954 0 18.023-8.07 18.023-18.024s-8.069-18.024-18.023-18.024c-9.955 0-18.024 8.07-18.024 18.024s8.069 18.024 18.024 18.024Z"/><path stroke="#fff" stroke-linecap="round" stroke-width="2.848" d="M131.493 93.16v12.815M137.901 99.567h-12.816"/><circle cx="7.908" cy="11.085" r="1.818" fill="#F0F2FD"/><circle cx="161.718" cy="63.683" r="5.839" fill="#F0F2FD" transform="rotate(-24.925 161.718 63.683)"/><circle cx="142.175" cy="75.453" r="2.845" fill="#F0F2FD" transform="rotate(-24.925 142.175 75.453)"/><circle cx="17.868" cy="4.077" r="3.294" fill="#F0F2FD"/><circle cx="2.5" cy="64.597" r="2.5" fill="#F0F2FD"/><circle cx="13.907" cy="112.472" r="3.333" fill="#F0F2FD"/><circle cx="145.745" cy="23.085" r="4.848" fill="#F0F2FD"/></svg>    
            </div>
            
            <div class="easystore-empty-state-title mb-2">
                <?php echo Text::_('COM_EASYSTORE_EMPTYSTATE_CATEGORY_TITLE'); ?>
            </div>

            <div class="easystore-empty-state-description mb-4">
                <?php echo Text::_($acl->canCreate() ? 'COM_EASYSTORE_EMPTYSTATE_CATEGORY_DESCRIPTION' : 'COM_EASYSTORE_EMPTYSTATE_CATEGORY_NO_PERMISSION'); ?>
            </div>

            <?php if ($acl->canCreate() || count($user->getAuthorisedCategories('com_easystore', 'core.create')) > 0) : ?>
                <div class="easystore-empty-state-actions">
                    <a href="<?php echo Route::_('index.php?option=com_easystore&task=category.add'); ?>" class="btn btn-primary"><?php echo Text::_('COM_EASYSTORE_EMPTYSTATE_CATEGORY_BUTTON_TEXT'); ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<input type="hidden" name="task" value="">
<input type="hidden" name="boxchecked" value="0">
<?php echo HTMLHelper::_('form.token'); ?>
</form>
