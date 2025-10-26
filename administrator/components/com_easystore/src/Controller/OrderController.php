<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Controller\FormController;
use JoomShaper\Component\EasyStore\Administrator\Model\OrderModel;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;

/**
 * Controller for a single Order
 *
 * @since  1.0.0
 */
class OrderController extends FormController
{
    /**
     * Method to check if you can add a new record.
     *
     * @param   array  $data  An array of input data.
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    protected function allowAdd($data = [])
    {
        $acl = AccessControl::create();
        return $acl->canCreate();
    }

    /**
     * Method to check if you can edit a record.
     *
     * @param   array   $data  An array of input data.
     * @param   string  $key   The name of the key for the primary key.
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    protected function allowEdit($data = [], $key = 'id')
    {
        $acl = AccessControl::create();
        return $acl->canEdit() || $acl->setContext('order')->canEditOwn($data[$key]);
    }

    public function add()
    {
        /**
         * @var CMSApplication
         */
        $app     = Factory::getApplication();
        $model   = new OrderModel();
        $orderId = $model->createNewOrder();

        if ($orderId) {
            $app->redirect(Route::_('/administrator/index.php?option=com_easystore&view=order&layout=edit&id=' . $orderId . '#/manage-order'));
            exit;
        }

        throw new \Exception('Error creating order.');
    }


    /**
     * Method to cancel an edit.
     *
     * @param   string  $key  The name of the primary key of the URL variable.
     *
     * @return  bool  True if access level checks pass, false otherwise.
     *
     * @since   1.0.1
     */
    public function cancel($key = 'id')
    {
        /** @var OrderModel $model */
        $model   = $this->getModel();
        $table   = $model->getTable();
        $context = "$this->option.edit.$this->context";

        $recordId = $this->input->getInt($key);

        // Attempt to check-in the current record.
        if ($recordId && $table->hasField('checked_out') && $model->checkin($recordId) === false) {
            // Check-in failed, go back to the record and display a notice.
            $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()), 'error');

            $this->setRedirect(
                Route::_(
                    'index.php?option=' . $this->option . '&view=' . $this->view_item
                        . $this->getRedirectToItemAppend($recordId, $key),
                    false
                )
            );

            return false;
        }

        // Clean the session data and redirect.
        $this->releaseEditId($context, $recordId);
        $this->app->setUserState($context . '.data', null);

        $url = 'index.php?option=' . $this->option . '&view=' . $this->view_list
            . $this->getRedirectToListAppend();

        // Check if there is a return value
        $return = $this->input->get('return', null, 'base64');

        if (!\is_null($return) && Uri::isInternal($return)) {
            $url = base64_decode($return);
        }

        // Redirect to the list screen.
        $this->setRedirect(Route::_($url, false));

        return true;
    }
}
