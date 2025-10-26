<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\View\Orders;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use JoomShaper\Component\EasyStore\Administrator\Constants\Status;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Orders view class for the Order package.
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
     * @var  Pagination
     */
    protected $pagination;

    /**
     * The model state
     *
     * @var  object
     */
    protected $state;

    /**
     * Form object for search filters
     *
     * @var  \Joomla\CMS\Form\Form
     */
    public $filterForm;

    /**
     * The active search filters
     *
     * @var  array
     */
    public $activeFilters;

    /**
     * Is this view an Empty State
     *
     * @var   bool
     * @since 1.0.0
     */
    private $isEmptyState = false;

    /**
     * Display the view
     *
     * @param   string|null  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @throws  GenericDataException
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Written this way because we only want to call IsEmptyState if no items, to prevent always calling it when not needed.
        if (empty($this->items) && $this->isEmptyState = $this->get('IsEmptyState')) {
            $this->setLayout('emptystate');
        }

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        // We don't need toolbar in the modal window.
        if ($this->getLayout() !== 'modal') {
            $this->addToolbar();
        }

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
        $toolbar    = $this->getDocument()->getToolbar();
        $acl     = AccessControl::create();

        ToolbarHelper::title(Text::_('COM_EASYSTORE_MANAGER_ORDERS'), 'fa fa-clipboard-list');

        if ($acl->canCreate()) {
            $toolbar->addNew('order.add');
        }

        if (!$this->isEmptyState) {
            /** @var DropdownButton $dropdown */
            $dropdown = $toolbar->dropdownButton('status-group', 'JTOOLBAR_CHANGE_STATUS')
            ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();

            if ($acl->canEditState()) {
                $childBar->publish('orders.publish')->listCheck(true);
                $childBar->checkin('orders.checkin')->listCheck(true);

                if ((int) $this->state->get('filter.published') !== Status::TRASHED) {
                    $childBar->trash('orders.trash')->listCheck(true);
                }
            }
        }

        if (!$this->isEmptyState && (int) $this->state->get('filter.published') === Status::TRASHED && $acl->canDelete()) {
            $toolbar->delete('orders.delete', 'JTOOLBAR_EMPTY_TRASH')
            ->message('JGLOBAL_CONFIRM_DELETE')
            ->listCheck(true);
        }

        if ($acl->isAdmin() || $acl->canManageOptions()) {
            $toolbar->preferences('com_easystore');
        }

        $toolbar->help('orders', false, 'https://www.joomshaper.com/documentation/easystore/managing-orders');
    }
}
