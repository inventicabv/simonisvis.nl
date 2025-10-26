<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\View\Analytics;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View class for an analytics.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * Display the view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        $this->addToolbar();
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @throws \Exception
     * @since   1.0.0
     */
    protected function addToolbar()
    {
        $acl     = AccessControl::create();
        $toolbar    = $this->getDocument()->getToolbar();
        ToolbarHelper::title(Text::_('COM_EASYSTORE_MANAGER_ANALYTICS'), 'chart');

        if ($acl->canCreate()) {
            $buttonHtml = '<a href="' . Route::_('index.php?option=com_easystore&view=product&layout=edit') . '" class="button-new btn btn-secondary"><span class="icon-new me-2" aria-hidden="true"></span> ' . Text::_('COM_EASYSTORE_APP_PRODUCTS_ADD_NEW_PRODUCER') . '</a>';
            $toolbar->customHtml($buttonHtml);
        }

        if ($acl->isAdmin() || $acl->canManageOptions()) {
            $toolbar->preferences('com_easystore');
        }

        $toolbar->help('analytics', false, 'https://www.joomshaper.com/documentation/easystore/analytics');
    }
}
