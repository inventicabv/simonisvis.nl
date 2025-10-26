<?php
/*
 *  package: Custom-Quickicons
 *  copyright: Copyright (c) 2024. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 2 or later
 *  link: https://www.joomill-extensions.com
 */

use Joomla\CMS\Helper\ModuleHelper;

// No direct access.
defined('_JEXEC') or die;

// Get Joomla Layout
require ModuleHelper::getLayoutPath('mod_custom_quickicon', $params->get('layout', 'default'));