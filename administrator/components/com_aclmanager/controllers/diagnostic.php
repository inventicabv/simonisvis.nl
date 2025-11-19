<?php
/**
 * @package		ACL Manager for Joomla
 * @copyright 	Copyright (c) 2011-2014 Sander Potjer
 * @license 	GNU General Public License version 3 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\MVC\Controller\BaseController;

// No direct access.
defined('_JEXEC') or die;

/**
 * ACL Manager Diagnostic Controller
 */
class AclmanagerControllerDiagnostic extends BaseController
{

	/**
	 * Fix wrong stored assets
	 */
	public function fixAssetIssues()
	{
		Session::checkToken() or throw new \Exception(Text::_('JINVALID_TOKEN'), 403);

		$model = $this->getModel('diagnostic');
		$model->fixAssetIssues();
		$this->setRedirect(Route::_('index.php?option=com_aclmanager&view=diagnostic', false));
	}

	/**
	 * Add missing assets
	 */
	public function fixMissingAssets()
	{
		Session::checkToken() or throw new \Exception(Text::_('JINVALID_TOKEN'), 403);

		$model = $this->getModel('diagnostic');
		$model->fixMissingAssets();
		$this->setRedirect(Route::_('index.php?option=com_aclmanager&view=diagnostic', false));
	}

	/**
	 * Fix admin conflicts
	 */
	public function fixAdminConflicts()
	{
		Session::checkToken() or throw new \Exception(Text::_('JINVALID_TOKEN'), 403);

		$model = $this->getModel('diagnostic');
		$model->fixAdminConflicts();
		$this->setRedirect(Route::_('index.php?option=com_aclmanager&view=diagnostic', false));
	}

	/**
	 * Fix orphan assets
	 */
	public function fixOrphanAssets()
	{
		Session::checkToken() or throw new \Exception(Text::_('JINVALID_TOKEN'), 403);

		$model = $this->getModel('diagnostic');
		$model->fixOrphanAssets();
		$this->setRedirect(Route::_('index.php?option=com_aclmanager&view=diagnostic', false));
	}

	/**
	 * Rebuild the assets table
	 */
	public function rebuild()
	{
		Session::checkToken() or throw new \Exception(Text::_('JINVALID_TOKEN'), 403);

		$this->setRedirect(Route::_('index.php?option=com_aclmanager&view=diagnostic', false));

		// Initialise variables.
		$model = $this->getModel('diagnostic');

		if ($model->rebuild()) {
			// Rebuild succeeded.
			$this->setMessage(Text::_('COM_ACLMANAGER_DIAGNOSTIC_REBUILD_SUCCESS'));
			return true;
		} else {
			// Rebuild failed.
			$this->setMessage(Text::_('COM_ACLMANAGER_DIAGNOSTIC_REBUILD_FAILED'));
			return false;
		}
	}
}
