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

// No direct access.
defined('_JEXEC') or die;

/**
 * HTML View class for the ACL Manager component
 */
class AclmanagerViewUser extends HtmlView
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

		// Load the toolbar
		$this->addToolbar();

		// Show edit notice
		$app->enqueueMessage(Text::_('COM_ACLMANAGER_NOTICE_VIEW_USER_PERMISSIONS'), 'notice');

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
		$user = Factory::getUser($this->state->get('filter.user_id'));
		ToolbarHelper::title(Text::_('COM_ACLMANAGER').': '.Text::_('COM_ACLMANAGER_SUBMENU_USER').' - '.$user->name, 'aclmanager.png');

		// Buttons
		$bar = Toolbar::getInstance('toolbar');
		ToolbarHelper::cancel('cancel');
		ToolbarHelper::divider();
		$printUrl = 'index.php?option=com_aclmanager&view=user&id='.$user->id.'&layout=print&tmpl=component';
		$bar->appendButton('Popup', 'print', 'JGLOBAL_PRINT', $printUrl, 875, 550);
	}
}