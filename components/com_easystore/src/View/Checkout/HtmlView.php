<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Site\View\Checkout;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Site\Traits\Token;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View for the checkout of the EasyStore component
 */
class HtmlView extends BaseHtmlView
{
    use Token;

    /**
     * The cart item
     *
     * @var    mixed
     * @since  1.0.0
     */
    protected $item;

    /**
     * Pagination object
     *
     * @var    \Joomla\CMS\Pagination\Pagination
     * @since  1.0.0
     */
    protected $pagination;

    /**
     * Guest checkout
     *
     * @var bool
     */
    protected $allowGuestCheckout = false;

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
        /** @var CMSApplication */
        $app                      = Factory::getApplication();
        $user                     = $app->getIdentity();
        $settings                 = SettingsHelper::getSettings();
        $this->allowGuestCheckout = $settings->get('checkout.allow_guest_checkout', false);


        $token = $this->getToken();

        if (empty($token)) {
            $url = Route::_('index.php?option=com_easystore&view=cart', false);
            $app->redirect($url);
        }

        if (!$this->allowGuestCheckout && $user->guest) {
            $redirect = 'index.php?option=com_easystore&view=checkout&step=information&cart_token=' . $token;
            $url      = Route::_('index.php?option=com_users&view=login&return=' . base64_encode($redirect), false);
            $app->redirect($url);
        }

        $this->item = $this->get('Item');

        if (empty($this->item->cart->items)) {
            return $app->enqueueMessage(Text::_('COM_EASYSTORE_INVALID_CHECKOUT_ERROR'), 'error');
        }

        $this->params = ComponentHelper::getParams('com_easystore');

        /** @var CMSApplication */
        $app    = Factory::getApplication();
        $active = $app->getMenu()->getActive();

        if (
            $active
            && $active->component === 'com_easystore'
            && $active->query['view'] === 'checkout'
        ) {
            $temp       = clone $this->params;
            $menuParams = $active->getParams();
            $temp->merge($menuParams);
            $this->params = $temp;
        }

        $this->params->merge($app->getParams());
        $this->_prepareDocument();

        $eventName = 'onEasystoreCheckoutBeforeRender';
        $event = AbstractEvent::create($eventName, ['subject' => $this->item]);

        try {
            $dispatcher = Factory::getApplication()->getDispatcher();
            $dispatcher->dispatch($eventName, $event);
            $this->item->onEasystoreCheckoutBeforeRender = $event->getArgument('subject');
        } catch (\Throwable $th) {
            Factory::getApplication()->enqueueMessage($th->getMessage());
        }

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
