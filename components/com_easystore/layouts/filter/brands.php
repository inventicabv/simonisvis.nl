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

extract($displayData);

echo EasyStoreHelper::loadLayout('system.list', [
    'title'   => $settings->title ?? Text::_('COM_EASYSTORE_BRANDS'),
    'key'     => 'brand',
    'filter'  => 'brands',
    'options' => $options,
    'settings'  => $settings
]);
