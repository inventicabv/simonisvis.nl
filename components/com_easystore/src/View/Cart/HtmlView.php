<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Site\View\Cart;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use JoomShaper\Component\EasyStore\Site\Traits\Token;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View for the cart of the EasyStore component
 */
class HtmlView extends BaseHtmlView
{
    use Token;

    /**
     * The cart products
     *
     * @var  \stdClass
     */
    protected $items;

    /**
     * The cart coupon info
     *
     * @var  \stdClass
     */
    protected $coupon;

    /**
     * Pricing info
     *
     * @var  \stdClass
     */
    protected $pricing;

    /**
     * The page parameters
     *
     * @var    \Joomla\Registry\Registry|null
     *
     */
    protected $params = null;

    /**
     * Display the view
     *
     * @param   string  $template  The name of the layout file to parse.
     * @return  void
     */
    public function display($template = null)
    {
        $this->item = $this->get('Item');

        $this->params = ComponentHelper::getParams('com_easystore');
        $app          = Factory::getApplication();
        $active       = $app->getMenu()->getActive();

        if (
            $active
            && $active->component == 'com_easystore'
            && $active->query['view'] == 'cart'
        ) {
            $temp       = clone $this->params;
            $menuParams = $active->getParams();
            $temp->merge($menuParams);
            $this->params = $temp;
        }

        $this->params->merge($app->getParams());

        $this->_prepareDocument();

        // Call the parent display to display the layout file
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
