<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2024, JoomShaper
 * @license     MIT
 */

namespace JoomShaper\Component\EasyStore\Administrator\View\Collections;

\defined('_JEXEC') or die('Restricted Direct Access!');

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use JoomShaper\Component\EasyStore\Administrator\Constants\Status;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;

class HtmlView extends BaseHtmlView
{
    /**
     * An array of items
     *
     * @var array
     *
     * @since   1.4.0
     */
    protected $items;

    /**
     * The pagination object
     *
     * @var \JPagination
     *
     * @since   1.4.0
     */
    protected $pagination;

    /**
     * The model state
     *
     * @var \JObject
     *
     * @since   1.4.0
     */
    protected $state;

    /**
     * Form object for search filters
     *
     * @var \JForm
     *
     * @since   1.4.0
     */
    public $filterForm;

    /**
     * The active search filters
     *
     * @var array
     *
     * @since   1.4.0
     */
    public $activeFilters;

    /**
     * Is this view an Empty State
     *
     * @var   bool
     * @since 1.4.0
     */
    private $isEmptyState = false;

    /**
     * Display the view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise an Error object.
     *
     * @since   1.4.0
     */
    public function display($tpl = null)
    {
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        $errors = $this->get('Errors');

        if (empty($this->items) && $this->isEmptyState = $this->get('IsEmptyState')) {
            $this->setLayout('emptystate');
        }

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        return parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   1.4.0
     */
    protected function addToolbar()
    {
        $acl     = AccessControl::create();
        $toolbar    = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_EASYSTORE_COLLECTIONS_TOOLBAR_LABEL'), 'list');

        if ($acl->canCreate()) {
            $toolbar->addNew('collection.add');
        }

		if (!$this->isEmptyState) {
			$dropdown = $toolbar->dropdownButton('status-group')
				->text('JTOOLBAR_CHANGE_STATUS')
				->toggleSplit(false)
				->icon('icon-ellipsis-h')
				->buttonClass('btn btn-action')
				->listCheck(true);

            $childBar = $dropdown->getChildToolbar();

			if ($acl->canEditState())
			{
				$childBar->publish('collections.publish')->listCheck(true);
				$childBar->unpublish('collections.unpublish')->listCheck(true);
				$childBar->archive('collections.archive')->listCheck(true);
				$childBar->checkin('collections.checkin')->listCheck(true);
	
				if ((int) $this->state->get('filter.published') !== -2)
				{
					$childBar->trash('collections.trash')->listCheck(true);
				}
			}
		}


        if ((int) $this->state->get('filter.published') === Status::TRASHED && $acl->canDelete()) {
            $toolbar->delete('collections.delete')
                ->text('JTOOLBAR_EMPTY_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        if ($acl->isAdmin() || $acl->canManageOptions()) {
            $toolbar->preferences('com_easystore');
        }

        $toolbar->help('Collections', false, 'https://www.joomshaper.com/documentation/easystore/collections');
    }
}
