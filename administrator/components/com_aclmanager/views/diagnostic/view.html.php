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

// No direct access.
defined('_JEXEC') or die;

/**
 * HTML View class for the ACL Manager component
 */
class AclmanagerViewDiagnostic extends HtmlView
{
	protected $items;
	protected $form;

	public function display($tpl = null)
	{
		$this->params 			= ComponentHelper::getParams('com_aclmanager');
		$this->orphanassets 	= '';
		$this->missingassets 	= '';
		$this->assetissues		= '';
		$this->orphanassets = $this->get('orphanAssets');
		if(!$this->orphanassets) {
			$this->missingassets = $this->get('missingAssets');
			if(!$this->missingassets) {
				$this->assetissues = $this->get('assetIssues');
			}
		}
		$this->adminconflicts	= $this->get('adminConflicts');

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
		ToolbarHelper::title(Text::_('COM_ACLMANAGER').': '.Text::_('COM_ACLMANAGER_SUBMENU_DIAGNOSTIC'), 'aclmanager.png');

		// Buttons
		if (Factory::getUser()->authorise('core.admin', 'com_aclmanager')) {
			ToolbarHelper::custom('diagnostic.rebuild', 'refresh', '', 'JTOOLBAR_REBUILD', false);
			ToolbarHelper::divider();
			ToolbarHelper::preferences('com_aclmanager');
		}
	}
}