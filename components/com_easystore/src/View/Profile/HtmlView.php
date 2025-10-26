<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Site\View\Profile;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Router\Route;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper as SiteEasyStoreHelper;

/**
 * View for the customer profile of the EasyStore component
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The profile object
     *
     * @var  \stdClass
     */
    protected $item;

    /**
     * The Form object
     *
     * @var  \Joomla\CMS\Form\Form
     */
    protected $form;

    /**
     * The model state
     *
     * @var  CMSObject
     */
    protected $state;

    /**
     * The page parameters
     *
     * @var    \Joomla\Registry\Registry|null
     *
     * @since  1.0.0
     */
    protected $params = null;

    /**
     * Display the view
     *
     * @param   string  $tpl  The name of the layout file to parse.
     * @return  void
     */
    public function display($tpl = null)
    {
        $app  = Factory::getApplication();
        $user = $this->getCurrentUser();

        if (!$user->id) {
            $app->redirect(Route::_('index.php?option=com_users&view=login', false));
        }

        $this->item   = $this->get('Data');
        $this->form   = $this->getModel()->getForm(new CMSObject(['id' => $user->id]));
        $this->state  = $this->get('State');
        $this->params = $this->state->get('params');

        $canDo                      = ContentHelper::getActions('com_easystore');
        $this->item->havePermission = $canDo->get('core.create');

        if ($this->getLayout() == 'edit') {
            $this->item->countries       = SiteEasyStoreHelper::getOptionsFromJson('country');
            $this->item->shipping_states = SiteEasyStoreHelper::getOptionsFromJson('state', $this->item->shipping_country_code);
            $this->item->billing_states  = SiteEasyStoreHelper::getOptionsFromJson('state', $this->item->billing_country_code);
            $this->item->phone_codes     = SiteEasyStoreHelper::getOptionsFromJson('phoneCode');
        }

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        // View also takes responsibility for checking if the user logged in with remember me.
        $cookieLogin = $user->get('cookieLogin');

        if (!empty($cookieLogin)) {
            // If so, the user must login to edit the password and other data.
            // What should happen here? Should we force a logout which destroys the cookies?
            /** @var CMSApplication */
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_('JGLOBAL_REMEMBER_MUST_LOGIN'), 'message');
            $app->redirect(Route::_('index.php?option=com_users&view=login', false));

            return false;
        }

        $this->params->merge($app->getParams());

        $this->_prepareDocument();

        // Call the parent display to display the layout file
        parent::display($tpl);
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
