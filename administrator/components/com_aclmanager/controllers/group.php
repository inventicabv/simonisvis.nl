<?php
/**
 * @package		ACL Manager for Joomla
 * @copyright 	Copyright (c) 2011-2014 Sander Potjer
 * @license 	GNU General Public License version 3 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\MVC\Controller\BaseController;

// No direct access.
defined('_JEXEC') or die;

/**
 * ACL Manager Diagnostic Controller
 */
class AclmanagerControllerGroup extends BaseController
{
	/**
	 * Class Constructor
	 */
	function __construct($config = array())
	{
		parent::__construct($config);

		// Map the apply task to the save method.
		$this->registerTask('apply', 'save');
		$this->registerTask('clear', 'reset');
	}

	/**
	 * Save assets
	 */
	public function save()
	{
		Session::checkToken() or throw new \Exception(Text::_('JINVALID_TOKEN'), 403);

		// Initialise variables.
		$app	= Factory::getApplication();
		$model	= $this->getModel('group');
		$data	= $app->getInput()->get('jform', array(), 'post', 'array');
		$id		= $app->getInput()->get('id', 0);

		// Attempt to save the configuration.
		$return = $model->save($data);

		// Check the return value.
		if ($return === false)
		{
			// Save failed, go back to the screen and display a notice.
			$message = Text::sprintf('JERROR_SAVE_FAILED', $model->getError());
			$this->setRedirect('index.php?option=com_aclmanager&view=group', $message, 'error');
			return false;
		}

		// Set the success message.
		$message = Text::_('COM_ACLMANAGER_PERMISSIONS_SAVED');

		// Set the redirect based on the task.
		switch ($this->getTask())
		{
			case 'apply':
				$this->setRedirect('index.php?option=com_aclmanager&view=group&id='.$id, $message);
				break;

			case 'save':
			default:
				$this->setRedirect('index.php?option=com_aclmanager', $message);
				break;
		}

		return true;
	}

	/**
	 * Reset permissions for group
	 */
	public function reset()
	{
		Session::checkToken() or throw new \Exception(Text::_('JINVALID_TOKEN'), 403);

		// Initialise variables.
		$app		= Factory::getApplication();
		$session 	= Factory::getSession();
		$model		= $this->getModel('group');
		$data		= $app->getInput()->get('jform', array(), 'post', 'array');
		$id			= $app->getInput()->get('id', 0);

		// Attempt to save the configuration.
		$return = $model->reset($data,$this->getTask());

		// Check the return value.
		if ($return === false)
		{
			// Save failed, go back to the screen and display a notice.
			$message = Text::sprintf('JERROR_SAVE_FAILED', $model->getError());
			$this->setRedirect('index.php?option=com_aclmanager&view=group', $message, 'error');
			return false;
		}

		// Set reset session
		switch ($this->getTask())
		{
			case 'reset':
				$session->set('aclmanager_reset', 'revert');
				break;

			case 'clear':
			default:
				$session->set('aclmanager_reset', 'clear');
				break;
		}

		// Set the redirect based on the task.
		$this->setRedirect('index.php?option=com_aclmanager&view=group&id='.$id);

		return true;
	}

}