<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Site\View\Orders;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View for the orders list of the EasyStore component
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The list of orders
     *
     * @var    array|false
     * @since  1.0.0
     */
    protected $items;

    /**
     * @var    \Joomla\Registry\Registry
     * @since  1.2.0
     */
    protected $params;

    /**
     * Display the view
     *
     * @param   string  $template  The name of the layout file to parse.
     * @return  void
     */
    public function display($template = null)
    {
        // Get some data from the models
        $this->items = $this->get('Items');

        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $app  = Factory::getApplication();
        $user = $this->getCurrentUser();

        if (!$user->id) {
            $app->redirect(Route::_('index.php?option=com_users&view=login', false));
        }

        // View also takes responsibility for checking if the user logged in with remember me.
        $cookieLogin = $user->get('cookieLogin');

        if (!empty($cookieLogin)) {
            // If so, the user must login to edit the password and other data.
            // What should happen here? Should we force a logout which destroys the cookies?
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_('JGLOBAL_REMEMBER_MUST_LOGIN'), 'message');
            $app->redirect(Route::_('index.php?option=com_users&view=login', false));

            return false;
        }

        $this->params = ComponentHelper::getParams('com_easystore');

        /** @var CMSApplication */
        $app    = Factory::getApplication();
        $active = $app->getMenu()->getActive();

        if (
            $active
            && $active->component === 'com_easystore'
            && $active->query['view'] === 'orders'
        ) {
            $temp       = clone $this->params;
            $menuParams = $active->getParams();
            $temp->merge($menuParams);
            $this->params = $temp;
        }

        $this->params->merge($app->getParams());

        $this->_prepareDocument();

        parent::display($template);
    }

    /**
     * Prepares the document
     *
     * @return  void
     *
     * @throws \Exception
     *
     * @since  1.2.0
     */
    protected function _prepareDocument()
    {
        $app = Factory::getApplication();

        // Because the application sets a default page title,
        // we need to get it from the menu item itself
        $menu = $app->getMenu()->getActive();

        if ($menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        } else {
            $this->params->def('page_heading');
        }

        $title = $this->params->def('page_title');

        $this->setDocumentTitle($title);

        if ($this->params->get('menu-meta_description')) {
            $this->getDocument()->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->params->get('menu-meta_keywords')) {
            $this->getDocument()->setMetaData('keywords', $this->params->get('menu-meta_keywords'));
        }

        if ($this->params->get('robots')) {
            $this->getDocument()->setMetaData('robots', $this->params->get('robots'));
        }
    }
}
