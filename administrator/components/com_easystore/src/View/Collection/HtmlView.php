<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2024, JoomShaper
 * @license     MIT
 */

namespace JoomShaper\Component\EasyStore\Administrator\View\Collection;

\defined('_JEXEC') or die('Restricted Direct Access!');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;

class HtmlView extends BaseHtmlView
{
    /**
     * The \JForm object
     *
     * @var \JForm
     *
     * @since   1.4.0
     */
    protected $form;

    /**
     * The active item
     *
     * @var object
     *
     * @since   1.4.0
     */
    protected $item;

    /**
     * The model state
     *
     * @var \JObject
     *
     * @since   1.4.0
     */
    protected $state;

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
        // Initialize the variables.
        $this->form  = $this->get('Form');
        $this->item  = $this->get('Item');
        $this->state = $this->get('State');

        // Check for errors.
        if ((count($errors = $this->get('Errors')))) {
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
        $app = Factory::getApplication();
        $app->getInput()->set('hidemainmenu', true);
		$acl = AccessControl::create();
        $user       = $app->getIdentity();
        $userId     = (int) $user->id;
        $isNew      = (int) $this->item->id === 0;
        $checkedOut = !(is_null($this->item->checked_out) || (int) $this->item->checked_out === $userId);

		
		ToolbarHelper::title($isNew ? Text::_('COM_EASYSTORE_COLLECTION_NEW') : Text::_('COM_EASYSTORE_COLLECTION_EDIT'), 'book edit-collection');

		// Build the actions for new and existing records.
		if ($isNew) {
			// For new records, check the create permission.
			ToolbarHelper::apply('collection.apply');

			ToolbarHelper::saveGroup(
				[
					['save', 'collection.save'],
					['save2new', 'collection.save2new']
				],
				'btn-success'
			);

			ToolbarHelper::cancel('collection.cancel');
		} else {
			// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
			$isItemEditable = $acl->canEdit() || $acl->setContext('collection')->canEditOwn($this->item->id);

            $toolbarButtons = [];

			// Can't save the record if it's checked out and editable
			if (!$checkedOut && $isItemEditable) {
				ToolbarHelper::apply('collection.apply');

                $toolbarButtons[] = ['save', 'collection.save'];

				// We can save this record, but check the create permission to see if we can return to make a new one.
				if ($acl->canCreate()) {
					$toolbarButtons[] = ['save2new', 'collection.save2new'];
				}
			}

			// If checked out, we can still save
			if ($acl->canCreate()) {
				$toolbarButtons[] = ['save2copy', 'collection.save2copy'];
			}

            ToolbarHelper::saveGroup(
                $toolbarButtons,
                'btn-success'
            );

            ToolbarHelper::cancel('collection.cancel', 'JTOOLBAR_CLOSE');
        }

        ToolbarHelper::divider();
        ToolbarHelper::help('Collection:_New_or_Edit', false, 'https://www.joomshaper.com/documentation/easystore/collections');
    }
}
