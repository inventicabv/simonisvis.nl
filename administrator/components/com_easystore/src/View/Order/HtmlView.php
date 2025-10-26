<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\View\Order;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Component\Content\Administrator\Helper\ContentHelper;
use JoomShaper\Component\EasyStore\Administrator\Concerns\OrderEditable;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * HTML View class for the Order
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    use OrderEditable;

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
        $this->canDo = ContentHelper::getActions('com_easystore');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->hideMainMenu();
        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Hide the main left side menu
     *
     * @return void
     */
    protected function hideMainMenu()
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);
    }

    protected function addToolbar()
    {
        $toolbar = Toolbar::getInstance();
        $acl     = AccessControl::create();

        $this->getDocument()->getWebAssetManager()
            ->useScript('com_easystore.alpine.admin')
            ->useStyle('com_easystore.site');

        $base = Route::_('index.php?option=com_easystore&view=order&layout=edit&id=' . $this->item->id . '#/');

        $html = '<div x-data="easystore_order(\'' . $base . '\', \'' . $this->item->order_status . '\')" class="easystore-order-buttons">';
        $html .= '<button type="button" x-show="showSaveAsDraftButton" x-cloak  @click="triggerSaveAsDraftButton" class="btn btn-link ms-2">' . Text::_('COM_EASYSTORE_ORDER_TOOLBAR_BUTTON_SAVE_AS_DRAFT') . '</button>';

        $restricted_status_list = ['paid', 'partially_refunded', 'refunded'];
        $is_paid                = in_array($this->item->payment_status, $restricted_status_list);

        if ($acl->canManage() && !$is_paid) {
            $html .= '<button type="button" x-show="showEditButton" x-cloak @click="triggerEditButton" class="btn btn-primary ms-2"><span class="icon-edit" aria-hidden="true"></span> ' . Text::_('COM_EASYSTORE_ORDER_TOOLBAR_BUTTON_EDIT') . '</button>';
        }

        $html .= '<button type="button" x-show="showSaveButton" x-cloak  @click="triggerSaveButton" class="btn btn-primary ms-2 ml-auto"><span class="icon-check me-2" aria-hidden="true"></span> ' . Text::_('COM_EASYSTORE_ORDER_TOOLBAR_BUTTON_SAVE') . '</button>';
        $html .= '<a href="' . Route::_('index.php?option=com_easystore&task=order.cancel&id=' . $this->item->id) . '" x-cloak  class="btn btn-primary ms-2"><span class="icon-times" aria-hidden="true"></span> ' . Text::_('COM_EASYSTORE_ORDER_TOOLBAR_BUTTON_CLOSE') . '</a>';
        $html .= '</div>';

        if ($acl->canEdit() || $acl->setContext('order')->canEditOwn($this->item->id)) {
            $toolbar->customHtml($html);
        }

        $toolbar->help('order', false, 'https://www.joomshaper.com/documentation/easystore/manually-creating-new-order');
    }
}
