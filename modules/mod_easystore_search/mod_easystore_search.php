<?php
/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use JoomShaper\Module\EasyStore\Site\Helper\SearchHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


$categories = SearchHelper::getCategories();

array_unshift($categories, ['title' => Text::_('MOD_EASYSTORE_SEARCH_ALL_CATEGORIES'), 'alias' => 'all']);

require ModuleHelper::getLayoutPath('mod_easystore_search', 'default');