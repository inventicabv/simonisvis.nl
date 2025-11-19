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
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;

// No direct access.
defined('_JEXEC') or die;

/**
 * HTML View class for the ACL Manager component
 */
class AclmanagerViewGroup extends HtmlView
{
	protected $items;
	protected $pagination;
	protected $state;
	protected $form;

	public function display($tpl = null)
	{
		$app = Factory::getApplication();
		$this->params 			= ComponentHelper::getParams('com_aclmanager');
		$this->actions			= AclmanagerHelper::getActions();
		$this->permissions		= $this->get('Permissions');
		$this->items			= $this->get('Items');
		$this->state			= $this->get('State');
		$this->assets			= AclmanagerHelper::asset($this->items,$this->state);
		$this->pagination		= $this->get('Pagination');
		$this->form				= $this->get('Form');

		// Load javascript
		HTMLHelper::_('script', 'administrator/components/com_aclmanager/assets/js/datatables.min.js', ['relative' => true]);
		HTMLHelper::_('script', 'administrator/components/com_aclmanager/assets/js/permissions.js', ['relative' => true]);

		// Show reset message
		$layout		= $app->getInput()->get('layout', null);
		$session 	= Factory::getSession();
		$task 		= $session->get('aclmanager_reset');

		if(!$layout && $task == 'revert') {
			$app->enqueueMessage(Text::_('COM_ACLMANAGER_PERMISSIONS_REVERTED'));
			$session->set('aclmanager_reset', null);
		} elseif(!$layout && $task == 'clear') {
			$app->enqueueMessage(Text::_('COM_ACLMANAGER_PERMISSIONS_CLEARED'));
			$session->set('aclmanager_reset', null);
		}

		// Load the toolbar
		$this->addToolbar();

		// Warning on diagnostic issues
		$diagnostic = $app->bootComponent('com_aclmanager')->getMVCFactory()->createModel('Diagnostic', 'Administrator', ['ignore_request' => true]);
		$orphanassets 	= '';
		$missingassets 	= '';
		$assetissues		= '';
		$orphanassets = $diagnostic->getOrphanAssets();
		if(!$orphanassets) {
			$missingassets = $diagnostic->getMissingAssets();
			if(!$missingassets) {
				$assetissues = $diagnostic->getAssetIssues();
			}
		}
		$adminconflicts	= $diagnostic->getAdminConflicts();

		if($orphanassets || $missingassets || $assetissues || $adminconflicts) {
			$app->enqueueMessage(Text::_('COM_ACLMANAGER_NOTICE_FIX_DIAGNOSTIC_ISSUES'), 'warning');
		}

		// Display the view
		parent::display('bootstrap');
	}

	/**
	 * Add the page title and toolbar.
	 */
	protected function addToolbar()
	{
		$app = Factory::getApplication();
		// Disable mainmenu
		$app->getInput()->set('hidemainmenu', 1);

		// Title
		$groupId = $this->state->get('filter.group_id');
		$groupname = AclmanagerHelper::groupName($groupId);
		ToolbarHelper::title(Text::_('COM_ACLMANAGER').': '.Text::_('COM_ACLMANAGER_SUBMENU_GROUP').' - '.$groupname, 'aclmanager.png');

		// Buttons
		$bar = Toolbar::getInstance('toolbar');
		if (Factory::getUser()->authorise('core.edit', 'com_aclmanager')) {
			ToolbarHelper::apply('group.apply');
			ToolbarHelper::save('group.save');
			ToolbarHelper::divider();
		}
		ToolbarHelper::cancel('cancel');
		if (Factory::getUser()->authorise('core.edit', 'com_aclmanager')) {
			ToolbarHelper::divider();

			HTMLHelper::_('bootstrap.modal', 'collapseModal');
			$title = Text::_('COM_ACLMANAGER_RESET');
			$dhtml = "<button data-bs-toggle=\"modal\" data-bs-target=\"#collapseModal\" class=\"btn btn-small\">
						<span class=\"icon-refresh\" title=\"$title\"></span>
						$title</button>";
			$bar->appendButton('Custom', $dhtml, 'reset');
		}
		ToolbarHelper::divider();
		$printUrl = 'index.php?option=com_aclmanager&view=group&id='.$groupId.'&layout=print&tmpl=component';
		$bar->appendButton('Popup', 'print', 'JGLOBAL_PRINT', $printUrl, 875, 550);
	}
}