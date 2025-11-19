<?php
/**
 * @package		ACL Manager for Joomla
 * @copyright 	Copyright (c) 2011-2014 Sander Potjer
 * @license 	GNU General Public License version 3 or later
 */

use Joomla\CMS\MVC\View\HtmlView;

// No direct access.
defined('_JEXEC') or die;

/**
 * HTML View class for the ACL Manager component
 */
class AclmanagerViewNotauthorised extends HtmlView
{
	public function display($tpl = null)
	{
		// Display the view
		parent::display($tpl);
	}
}