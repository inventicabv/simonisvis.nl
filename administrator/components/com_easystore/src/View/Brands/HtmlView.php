<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\View\Brands;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use JoomShaper\Component\EasyStore\Administrator\Constants\Status;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Brands view class for the Brand package.
 * 
 * @since  1.5.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * List of items.
     *
     * @var array
     */
    protected $items;

    /**
     * Pagination object.
     * 
     * @var Pagination
     */
    protected $pagination;

    /**
     * State object.
     * 
     * @var object
     */
    protected $state;

    /**
     * Filter form object.
     * 
     * @var Form
     */
    public $filterForm;

    /**
     * Active search filters.
     *
     * @var array
     */
    public $activeFilters;


    /**
     * Indicates whether the list is empty.
     *
     * @var bool
     */
    protected $isEmptyState = false;


    public function display($tpl = null)
    {
        $this->state         = $this->get('State');
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        if (!\count($this->items) && $this->isEmptyState = $this->get('IsEmptyState')) {
            $this->setLayout('emptystate');
        }

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        // We don't need to filter by language when multilingual is disabled
        if (!Multilanguage::isEnabled()) {
            unset($this->activeFilters['language']);
            $this->filterForm->removeField('language', 'filter');
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
        $acl = AccessControl::create();

       $toolbar    = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_EASYSTORE_MANAGER_BRANDS'), 'list');

        if ($acl->canCreate()) {
            ToolbarHelper::addNew('brand.add');
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
                $childBar->publish('brands.publish')->listCheck(true);
                $childBar->unpublish('brands.unpublish')->listCheck(true);
                $childBar->checkin('brands.checkin')->listCheck(true);
                $childBar->archive('brands.archive')->listCheck(true);

                if ((int) $this->state->get('filter.published') !== Status::TRASHED) {
                    $childBar->trash('brands.trash')->listCheck(true);
                }
            }

        }

        if ((int) $this->state->get('filter.published') === Status::TRASHED && $acl->canDelete()) {
            $toolbar->delete('brands.delete')
                ->text('JTOOLBAR_EMPTY_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        if ($acl->isAdmin() || $acl->canManageOptions()) {
            $toolbar->preferences('com_easystore');
        }

        $toolbar->help('Brands', false, 'https://www.joomshaper.com/documentation/easystore/creating-brands');

    }

}