<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Controller;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Application\CMSApplication;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Default Controller of EasyStore component
 *
 * @since  1.0.0
 */
class DisplayController extends BaseController
{
    /**
     * The default view.
     *
     * @var    string
     * @since  1.6
     */
    protected $default_view = 'dashboard';

    /**
     * Method to display a view.
     *
     * @param   bool  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     *
     * @return  BaseController|bool  This object to support chaining.
     *
     * @since   1.0
     */

    public function display($cachable = false, $urlparams = [])
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $document = $app->getDocument();
        $document->addScriptOptions('easystore.adminBase', Uri::root() . 'administrator/index.php');
        return parent::display($cachable, $urlparams);
    }
}
