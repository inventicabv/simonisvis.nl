<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\View\Reviews;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use JoomShaper\Component\EasyStore\Administrator\Constants\Status;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View class for a list of reviews.
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
     * @var   \Joomla\CMS\Object\CMSObject
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
     * All transition, which can be executed of one if the items
     *
     * @var  array
     */
    protected $transitions = [];

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
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
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

        if (!count((array)$this->items) && $this->isEmptyState = $this->get('IsEmptyState')) {
            $this->setLayout('emptystate');
        }

        // Check for errors.
        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();
        parent::display($tpl);
    }


    /**
     * Add the page title and toolbar.
     *
     * @return void
     *
     * @since   1.0.0
     */
    protected function addToolbar()
    {
        $acl     = AccessControl::create();
        $toolbar    = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_EASYSTORE_MANAGER_REVIEWS'), 'comments-2');

        if ($acl->canCreate()) {
            $toolbar->addNew('review.add');
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
                $childBar->publish('reviews.publish')->listCheck(true);
                $childBar->unpublish('reviews.unpublish')->listCheck(true);
                $childBar->archive('reviews.archive')->listCheck(true);
                $childBar->checkin('reviews.checkin')->listCheck(true);

                if ((int) $this->state->get('filter.published') !== Status::TRASHED) {
                    $childBar->trash('reviews.trash')->listCheck(true);
                }
            }
        }

        if (!$this->isEmptyState && (int) $this->state->get('filter.published') === Status::TRASHED && $acl->canDelete()) {
            $toolbar->delete('reviews.delete', 'JTOOLBAR_EMPTY_TRASH')
            ->message('JGLOBAL_CONFIRM_DELETE')
            ->listCheck(true);
        }

        if ($acl->isAdmin() || $acl->canManageOptions()) {
            $toolbar->preferences('com_easystore');
        }

        $toolbar->help('Reviews', false, 'https://www.joomshaper.com/documentation/easystore/managing-customer-reviews');
    }
}
