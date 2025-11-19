<?php
/**
 * @package		ACL Manager for Joomla
 * @copyright 	Copyright (c) 2011-2014 Sander Potjer
 * @license 	GNU General Public License version 3 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\DatabaseInterface;

// No direct access.
defined('_JEXEC') or die;

/**
 * ACL Manager Controller
 */
class AclmanagerController extends BaseController
{
	/**
	 * @var		string	The default view.
	 */
	protected $default_view = 'home';

	/**
	 * Method to construct
	 */
	function __construct()
	{
		parent::__construct();

		// Load js & css
		$app = Factory::getApplication();
		$doc = Factory::getDocument();
		$layout	= $app->getInput()->get('layout', null);
		if ($layout =='print') {
			$doc->addStyleSheet(Uri::root(true).'/administrator/components/com_aclmanager/assets/css/print.css?v=246');
		} else {
			$doc->addStyleSheet(Uri::root(true).'/administrator/components/com_aclmanager/assets/css/aclmanager.css?v=246');
		}
	}

	/**
	 * Method to display a view.
	 */
	public function display($cachable = false, $urlparams = false)
	{
		require_once JPATH_COMPONENT.'/helpers/aclmanager.php';

		// Variables
		$app = Factory::getApplication();
		$view	= $app->getInput()->get('view', 'home');
		$id		= $app->getInput()->get('id');

		// Get needed language files
		AclmanagerHelper::getLanguages();

		// Check for updates
		if($view == 'home') {
			$updateInfo = LiveUpdate::getUpdateInformation();
			if($updateInfo->hasUpdates == 1) {
				$app->enqueueMessage(Text::sprintf('COM_ACLMANAGER_UPDATE_FOUND', $updateInfo->version), 'warning');
			}
		}

		// Check for User Group ID.
		if (($view == 'group') && (!$id)) {
			// Somehow the person just went to the form - we don't allow that.
			$this->setError(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(Route::_('index.php?option=com_aclmanager', false));

			return false;
		}

		// Check for User ID.
		if (($view == 'user') && (!$id)) {
			// Somehow the person just went to the form - we don't allow that.
			$this->setError(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(Route::_('index.php?option=com_aclmanager', false));

			return false;
		}

		// Check if default asset permissions are stored
		$rules   = json_decode(Access::getAssetRules('com_aclmanager'), true);

		if(empty($rules)) {
			$db = Factory::getContainer()->get(DatabaseInterface::class);

			// Set default rules
			$query = $db->getQuery(true);
			$query->update($db->quoteName('#__assets'))
				->set($db->quoteName('rules') . ' = ' . $db->quote('{"core.admin":{"7":1},"core.manage":{"6":1},"core.edit":[],"aclmanager.diagnostic":{"6":1}}'))
				->where($db->quoteName('name') . ' = ' . $db->quote('com_aclmanager'));
			$db->setQuery($query);
			$db->execute();
		}

		parent::display();

		return $this;

	}

	/**
	 * Cancel operation
	 */
	function cancel()
	{
		// Redirect home
		$this->setRedirect('index.php?option=com_aclmanager');
	}
}
