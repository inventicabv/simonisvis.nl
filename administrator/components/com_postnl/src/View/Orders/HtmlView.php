<?php

/**
 * @package     COM_POSTNL
 * @subpackage  View
 *
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace Simonisvis\Component\PostNL\Administrator\View\Orders;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;

/**
 * View class for a list of orders.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * An array of items
     *
     * @var  array
     */
    protected $items;

    /**
     * The pagination object
     *
     * @var  \Joomla\CMS\Pagination\Pagination
     */
    protected $pagination;

    /**
     * The model state
     *
     * @var  \Joomla\CMS\Object\CMSObject
     */
    protected $state;

    /**
     * Display the view
     *
     * @param   string  $tpl  Template name
     *
     * @return void
     *
     * @throws \Exception
     */
    public function display($tpl = null)
    {
        $this->items      = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state      = $this->get('State');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function addToolbar()
    {
        ToolbarHelper::title(Text::_('COM_POSTNL') . ': ' . Text::_('COM_POSTNL_ORDERS'), 'shipping');

        // Add API test buttons
        ToolbarHelper::custom('config.testapi', 'wifi', 'wifi', Text::_('COM_POSTNL_TEST_API_CONNECTION'), false);
        ToolbarHelper::custom('config.testshipment', 'box', 'box', Text::_('COM_POSTNL_TEST_SHIPMENT'), false);

        ToolbarHelper::divider();

        // Add refresh button
        ToolbarHelper::custom('orders.refresh', 'refresh', 'refresh', 'JTOOLBAR_REFRESH', false);

        // Add link to plugin configuration
        ToolbarHelper::preferences('com_postnl');
    }
}
