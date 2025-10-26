<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Site\View\Brands;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View for the brands list of the EasyStore component
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The list of brands
     *
     * @var    mixed
     * @since  1.0.0
     */
    protected $items;

    /**
     * Pagination object
     *
     * @var    \Joomla\CMS\Pagination\Pagination
     * @since  1.0.0
     */
    protected $pagination;

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
        $app = Factory::getApplication();

        // Get some data from the models
        $this->items = $this->get('Items');

        $this->pagination = $this->get('Pagination');
        $this->params = $app->getParams();

        // Call the parent display method
        parent::display($template);
    }
    
}
