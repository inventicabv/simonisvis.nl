<?php

/**
 * @package     EasyStore.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

if (!isset($displayData)) {
    return;
}

extract($displayData);

$isAllCompleted = array_reduce($displayData, function ($acc, $currentItem) {
    $acc = $acc && $currentItem['isCompleted'];
    return $acc;
}, true);

?>
<div class="easystore-settingsteps-popup"<?php echo $isAllCompleted ? 'easystore-settingsteps-isAllCompleted' : '' ?> >
    <div class="easystore-settingsteps-title-wrapper" >
        <?php if ($isAllCompleted) : ?>
            <svg width="24" height="24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2Zm5.25 7.574a.938.938 0 0 0-1.382-1.267l-5.063 5.363-1.98-1.988a.938.938 0 0 0-1.383 1.268l2.672 2.741a.938.938 0 0 0 1.382 0l5.754-6.117Z" fill="#079874"/></svg>
        <?php else : ?>
            <svg width="24" height="24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.4 4.125c1.156-2 4.044-2 5.198 0l7.355 12.748c1.154 2-.29 4.5-2.6 4.5H4.646c-2.31 0-3.752-2.5-2.598-4.5L9.4 4.125ZM12 9.372a.75.75 0 0 1 .75.75v3.75a.75.75 0 1 1-1.5 0v-3.75a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" fill="#F2B300"/></svg>
        <?php endif;?>

        <h3 class="easystore-settingsteps-popup-title">
            <?php echo Text::_('COM_EASYSTORE_SETTINGSTEPS_POPUP_TITLE'); ?>
        </h3>
        <?php if ($isAllCompleted) : ?>
            <svg easystore-settingsteps-popup-close width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="#FAFBFB"/><path d="m17.216 16.003 5.192-5.192a.863.863 0 1 0-1.221-1.222l-5.192 5.192-5.192-5.192a.863.863 0 1 0-1.221 1.222l5.191 5.192-5.191 5.192a.863.863 0 1 0 1.221 1.221l5.192-5.192 5.192 5.192a.862.862 0 0 0 1.221 0 .863.863 0 0 0 0-1.221l-5.192-5.192Z" fill="#5C5E62"/></svg>
        <?php else : ?>
            <svg width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="m17.997 7.672-6.666 6.667-6.667-6.667" stroke="#5C5E62" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <?php endif;?>

    </div>
    <div class="easystore-settingsteps-content-wrapper">
        <?php foreach ($displayData as $settingName => $settingsValue) : ?>
            <div class="easystore-settingsteps-popup-content" <?php echo $settingsValue['isCompleted'] ? 'easystore-settingsteps-isCompleted' : '' ?>>
                <?php echo $settingsValue['icon']; ?>
                <p class="easystore-settingsteps-popup-content-title"><?php echo $settingsValue['title']; ?></p>
                <a class="easystore-settingsteps-popup-content-link" href="<?php echo $settingsValue['link']; ?>"><?php echo $settingsValue['linkText']; ?></a>
            </div>
        <?php endforeach;?>
    </div>
</div>
