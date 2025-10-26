<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\View\Products;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use JoomShaper\Component\EasyStore\Administrator\Constants\Status;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper as EasyStoreSiteHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View class for a list of products.
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
     * The default image src
     *
     * @var  string
     *
     * @since 1.2.0
     */
    public $defaultThumbnailSrc;

    /**
     * Display the view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     *
     * @since 1.0.0
     */
    public function display($tpl = null)
    {
        $this->items               = $this->get('Items');
        $this->pagination          = $this->get('Pagination');
        $this->state               = $this->get('State');
        $this->filterForm          = $this->get('FilterForm');
        $this->activeFilters       = $this->get('ActiveFilters');
        $this->defaultThumbnailSrc = EasyStoreSiteHelper::getPlaceholderImage();

        foreach ($this->items as &$item) {
            $images    = json_decode($item->images ?? '');
            $item->src = EasyStoreHelper::getFirstImage($images);
        }

        if (empty($this->items) && $this->isEmptyState = $this->get('IsEmptyState')) {
            $this->setLayout('emptystate');
        }

        // Check for errors.
        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        // We don't need toolbar in the modal window.
        if ($this->getLayout() !== 'modal') {
            $this->addToolbar();

            // We do not need to filter by language when multilingual is disabled
            if (!Multilanguage::isEnabled()) {
                unset($this->activeFilters['language']);
                $this->filterForm->removeField('language', 'filter');
            }
        }
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
        $toolbar = Toolbar::getInstance();
        $acl     = AccessControl::create();
        ToolbarHelper::title(Text::_('COM_EASYSTORE_MANAGER_PRODUCTS'), 'cart');

        if ($acl->canCreate()) {
            $toolbar->addNew('product.add');
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
                $childBar->publish('products.publish')->listCheck(true);
                $childBar->unpublish('products.unpublish')->listCheck(true);
                $childBar->archive('products.archive')->listCheck(true);

                $childBar->standardButton('featured', 'JFEATURE', 'products.featured')
                    ->listCheck(true);
                $childBar->standardButton('unfeatured', 'JUNFEATURE', 'products.unfeatured')
                    ->listCheck(true);
                $childBar->checkin('products.checkin')->listCheck(true);

                if ((int) $this->state->get('filter.published') !== Status::TRASHED) {
                    $childBar->trash('products.trash')->listCheck(true);
                }
            }
        }

        if (!$this->isEmptyState && (int) $this->state->get('filter.published') === Status::TRASHED && $acl->canDelete()) {
            $toolbar->delete('products.delete', 'JTOOLBAR_EMPTY_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        HTMLHelper::_('bootstrap.modal');

        if ($acl->canImport()) {
            ToolbarHelper::modal('easystoreProductImport', 'icon-upload', 'Import', 'button-options btn-primary');
        }

        if ($acl->canExport()) {
            $toolbar->standardButton('download', 'COM_EASYSTORE_PRODUCT_EXPORT', 'products.export')->icon('icon-download')->buttonClass('btn button-options btn-primary');
        }

        if ($acl->isAdmin() || $acl->canManageOptions()) {
            $toolbar->preferences('com_easystore');
        }

        $toolbar->help('Products', false, 'https://www.joomshaper.com/documentation/easystore/adding-products');
    }
}
