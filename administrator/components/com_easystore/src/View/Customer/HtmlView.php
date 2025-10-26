<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\View\Customer;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * HTML View class for Single Customer in EasyStore component
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The Form object
     *
     * @var  \Joomla\CMS\Form\Form
     */
    protected $form;

    /**
     * The active item
     *
     * @var  object
     */
    protected $item;

    /**
     * The model state
     *
     * @var  CMSObject
     */
    protected $state;

    /**
     * The actions the user is authorised to perform
     *
     * @var    CMSObject
     *
     * @since  1.0.0
     */
    protected $canDo;

    /**
     * Display the view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        $this->form  = $this->get('Form');
        $this->item  = $this->get('Item');
        $this->state = $this->get('State');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @since  1.0.0
     *
     * @return void
     */
    protected function addToolbar()
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        $isNew      = (int) $this->item->id === 0;
        $toolbar    = Toolbar::getInstance();
        $acl        = AccessControl::create();

        ToolbarHelper::title($isNew ? Text::_('COM_EASYSTORE_MANAGER_CUSTOMER_NEW') : Text::_('COM_EASYSTORE_MANAGER_CUSTOMER_EDIT'), 'users');

        // Build the actions for new and existing records.
        if ($isNew) {
            $toolbar->apply('customer.apply');

            $toolbar->cancel('customer.cancel', 'JTOOLBAR_CANCEL');
        } else {
            // Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
            $isEditable = $acl->canEdit() || $acl->setContext('customer')->canEditOwn($this->item->id);

            // Can't save the record if it's checked out and editable
            if ($isEditable) {
                $toolbar->apply('customer.apply');
            }

            $saveGroup = $toolbar->dropdownButton('save-group');

            $saveGroup->configure(
                function (Toolbar $childBar) use ($isEditable, $acl) {
                    // Can't save the record if it's checked out and editable
                    if ($isEditable) {
                        $childBar->save('customer.save');

                        // We can save this record, but check the create permission to see if we can return to make a new one.
                        if ($acl->canCreate()) {
                            $childBar->save2new('customer.save2new');
                        }
                    }
                }
            );

            $toolbar->cancel('customer.cancel');
        }

        $toolbar->divider();
        $toolbar->help('Customers:_New_or_Edit', false, 'https://www.joomshaper.com/documentation/easystore');
    }
}
