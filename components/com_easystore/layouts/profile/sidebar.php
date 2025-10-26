<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

$view = $view ?? 'account';

$links = [
    'account' => [
        'link' => Route::_('index.php?option=com_easystore&view=profile'),
        'title' => Text::_('COM_EASYSTORE_PROFILE_ACCOUNT'),
    ],
    'orders' => [
        'link' => Route::_('index.php?option=com_easystore&view=orders'),
        'title' => Text::_('COM_EASYSTORE_PROFILE_ORDERS'),
    ]
];

$profile = EasyStoreHelper::getCustomerByUserId((int) Factory::getApplication()->getIdentity()->id);
?>

<div class="easystore-profile-sidebar">
    <div class="easystore-profile-info">
        <div class="easystore-profile-avatar">
            <?php echo $profile->avatar; ?>
        </div>

        <div class="easystore-profile-content">
            <div class="easystore-profile-name">
                <?php echo $this->escape($profile->name); ?>
            </div>
            <?php if (isset($profile->created)) : ?>
            <div class="easystore-profile-created">
                <?php echo $this->escape(HTMLHelper::_('date', $profile->created, 'DATE_FORMAT_LC3')); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <ul class="easystore-profile-links">
        <?php foreach ($links as $key => $link) : ?>
            <li>
                <a href="<?php echo $link['link']; ?>" class="<?php echo $view === $key ? 'active' : ''; ?>">
                    <?php echo $link['title']; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

