<?php
/**
 * @package		ACL Manager for Joomla
 * @copyright 	Copyright (c) 2011-2014 Sander Potjer
 * @license 	GNU General Public License version 3 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\HTML\HTMLHelper;

// No direct access.
defined('_JEXEC') or die;

/**
 * HTML View class for the ACL Manager component
 */
class AclmanagerViewHome extends HtmlView
{
	protected $items;
	protected $form;

	public function display($tpl = null)
	{
		$app = Factory::getApplication();
		$component = $app->bootComponent('com_aclmanager');
		$diagnostic = $component ? $component->getMVCFactory()->createModel('Diagnostic', 'Administrator') : false;
		$this->params 			= ComponentHelper::getParams('com_aclmanager');
		$this->orphanassets 	= '';
		$this->missingassets 	= '';
		$this->assetissues		= '';
		$this->adminconflicts	= array();
		
		if ($diagnostic) {
			$this->orphanassets = $diagnostic->getOrphanAssets();
			if(!$this->orphanassets) {
				$this->missingassets = $diagnostic->getMissingAssets();
				if(!$this->missingassets) {
					$this->assetissues = $diagnostic->getAssetIssues();
				}
			}
			$this->adminconflicts = $diagnostic->getAdminConflicts();
		}

		// Load javascript
		HTMLHelper::_('script', 'administrator/components/com_aclmanager/assets/js/datatables.min.js', ['relative' => true]);

		$this->updateInfo = LiveUpdate::getUpdateInformation(0);

		// Load the submenu
		AclmanagerHelper::addSubmenu('home');

		// Load the toolbar
		$this->addToolbar();

		// Display the view
		parent::display('bootstrap');
	}

	/**
	 * Add the page title and toolbar.
	 */
	protected function addToolbar()
	{
		// Title
		ToolbarHelper::title(Text::_('COM_ACLMANAGER'), 'aclmanager.png');

		// Buttons
		if (Factory::getUser()->authorise('core.admin', 'com_aclmanager')) {
			ToolbarHelper::preferences('com_aclmanager');
		}
	}
}