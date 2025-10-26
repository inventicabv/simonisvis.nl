<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Site\View\Collections;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View for the featured collections of the EasyStore component
 *
 * @since   1.4.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The model state
     *
     * @var  \JObject
     */
    protected $state = null;

    /**
     * The featured collections array
     *
     * @var  \stdClass[]
     */
    protected $items = null;

    /**
     * The pagination object.
     *
     * @var  \JPagination
     */
    protected $pagination = null;

    /**
     * The user object
     *
     * @var  \JUser|null
     */
    protected $user = null;

    /**
     * The page class suffix
     *
     * @var    string
     * @since   1.4.0
     */
    protected $pageclass_sfx = '';

    /**
     * The page parameters
     *
     * @var    \Joomla\Registry\Registry|null
     * @since   1.4.0
     */
    protected $params = null;

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise an Error object.
     */
    public function display($tpl = null)
    {
        /** @var CMSApplication */
        $app  = Factory::getApplication();
        $user = Factory::getApplication()->getIdentity();

        $this->items      = $this->get('Items');
        $this->pagination = $this->get('Pagination');

        // Flag indicates to not add limitstart=0 to URL
        $this->pagination->hideEmptyLimitstart = true;
        $this->params                          = ComponentHelper::getParams('com_easystore');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->params->merge($app->getParams());

        $this->_prepareDocument();

        parent::display($tpl);
    }

    protected function _prepareDocument()
    {
        /** @var CMSApplication */
        $app = Factory::getApplication();
        /**
         * Because the application sets a default page title,
         * we need to get it from the menu item itself
         */
        $menu = $app->getMenu()->getActive();

        if ($menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        } else {
            $this->params->def('page_heading');
        }

        $title = $this->params->def('page_title');

        $this->setDocumentTitle($title);
    }
}
