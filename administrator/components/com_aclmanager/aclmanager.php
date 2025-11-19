<?php
/**
 * @package		ACL Manager for Joomla
 * @copyright	Copyright (c) 2011-2014 Sander Potjer
 * @license		GNU General Public License version 3 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

// No direct access.
defined('_JEXEC') or die;

// Access check.
$app = Factory::getApplication();
$view	= $app->getInput()->get('view', 'home');
if($view != 'notauthorised') {
	if (!Factory::getUser()->authorise('core.manage', 'com_aclmanager')) {
		$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
		$app->redirect('index.php');
		return;
	}
}

if(($view == 'diagnostic') && (!Factory::getUser()->authorise('aclmanager.diagnostic', 'com_aclmanager'))) {
	$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
	$app->redirect('index.php');
	return;
}

// Akeeba Live Update
require_once JPATH_COMPONENT_ADMINISTRATOR.'/liveupdate/liveupdate.php';
if($view == 'liveupdate') {
	if(Factory::getUser()->authorise('core.admin', 'com_aclmanager')) {
		LiveUpdate::handleRequest();
		return;
	} else {
		$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
		$app->redirect('index.php');
		return;
	}
}

// Load language and fall back language
$jlang = Factory::getLanguage();
$jlang->load('com_aclmanager.sys', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('com_aclmanager.sys', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
$jlang->load('com_aclmanager.sys', JPATH_ADMINISTRATOR, null, true);
$jlang->load('com_aclmanager', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('com_aclmanager', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
$jlang->load('com_aclmanager', JPATH_ADMINISTRATOR, null, true);

// Additional language files datatables
$jlang->load('com_users', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('com_users', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
$jlang->load('com_users', JPATH_ADMINISTRATOR, null, true);
$jlang->load('mod_menu', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('mod_menu', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
$jlang->load('mod_menu', JPATH_ADMINISTRATOR, null, true);

// Get controller instance
$controller = BaseController::getInstance('Aclmanager');
$controller->execute($app->getInput()->get('task'));
$controller->redirect();