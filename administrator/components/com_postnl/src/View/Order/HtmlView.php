<?php

/**
 * @package     COM_POSTNL
 * @subpackage  View
 *
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace Simonisvis\Component\PostNL\Administrator\View\Order;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * View to display order details
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The order object
     *
     * @var  object
     */
    protected $item;

    /**
     * Order items
     *
     * @var  array
     */
    protected $orderItems;

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
        $this->item = $this->get('Item');

        if (!$this->item) {
            throw new \Exception(Text::_('COM_POSTNL_ERROR_ORDER_NOT_FOUND'), 404);
        }

        $this->orderItems = $this->getModel()->getOrderItems($this->item->id);

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
        $orderId = $this->item->id;
        $hasTracking = !empty($this->item->tracking_number);

        ToolbarHelper::title(
            Text::_('COM_POSTNL') . ': ' . Text::_('COM_POSTNL_ORDER') . ' #' . ($this->item->order_number ?? $this->item->id),
            'shipping'
        );

        // Add "Create PostNL Label" button if no tracking yet
        if (!$hasTracking) {
            ToolbarHelper::custom(
                'shipment.create',
                'shipping',
                'shipping',
                'COM_POSTNL_CREATE_LABEL',
                false
            );
        } else {
            // Add "Print Label" button if tracking exists
            ToolbarHelper::custom(
                'shipment.print',
                'print',
                'print',
                'COM_POSTNL_PRINT_LABEL',
                false
            );
        }

        // Add back button
        ToolbarHelper::back('JTOOLBAR_BACK', Route::_('index.php?option=com_postnl&view=orders'));
    }
}
