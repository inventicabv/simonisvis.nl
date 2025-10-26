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

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;
use Joomla\CMS\Router\Route;
use JoomShaper\Component\EasyStore\Administrator\Model\CustomerModel;

/**
 * Controller for a single user
 *
 * @since  1.0.0
 */
class CustomerController extends FormController
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
        return $acl->canEdit() || $acl->setContext('user')->canEditOwn($data[$key]);
    }

    public function save($key = null, $urlVar = null)
    {
        // Check for request forgeries.
        $this->checkToken();
        $app     = $this->app;
        $model   = new CustomerModel();
        $table   = $model->getTable();
        $data    = $this->input->post->get('jform', [], 'array');
        $context = "$this->option.edit.$this->context";

        // Determine the name of the primary key for the data.
        if (empty($key)) {
            $key = $table->getKeyName();
        }

        // To avoid data collisions the urlVar may be different from the primary key.
        if (empty($urlVar)) {
            $urlVar = $key;
        }

        $recordId = $this->input->getInt($urlVar);

        // Populate the row id from the session.
        $data[$key] = $recordId;

        $form = $model->getForm($data, false);

        if (!$form) {
            $app->enqueueMessage($model->getError(), 'error');

            return false;
        }

        $objData = (object) $data;
        $app->triggerEvent(
            'onContentNormaliseRequestData',
            [$this->option . '.' . $this->context, $objData, $form]
        );
        $data = (array) $objData;

        // Test whether the data is valid.
        $validData = $model->validate($form, $data);

        // Check for validation errors.
        if ($validData === false) {
            // Get the validation messages.
            $errors = $model->getErrors();

            // Push up to three validation messages out to the user.
            for ($i = 0, $n = \count($errors); $i < $n && $i < 3; $i++) {
                if ($errors[$i] instanceof \Exception) {
                    $app->enqueueMessage($errors[$i]->getMessage(), 'warning');
                } else {
                    $app->enqueueMessage($errors[$i], 'warning');
                }
            }

            /**
             * We need the filtered value of calendar fields because the UTC normalisation is
             * done in the filter and on output. This would apply the Timezone offset on
             * reload. We set the calendar values we save to the processed date.
             */
            $filteredData = $form->filter($data);

            foreach ($form->getFieldset() as $field) {
                if ($field->type === 'Calendar') {
                    $fieldName = $field->fieldname;

                    if (isset($filteredData[$fieldName])) {
                        $data[$fieldName] = $filteredData[$fieldName];
                    }
                }
            }

            // Save the data in the session.
            $app->setUserState($context . '.data', $data);

            // Redirect back to the edit screen.
            $this->setRedirect(
                Route::_(
                    'index.php?option=' . $this->option . '&view=' . $this->view_item
                        . $this->getRedirectToItemAppend($recordId, $urlVar),
                    false
                )
            );

            return false;
        }

        if (!isset($validData['tags'])) {
            $validData['tags'] = [];
        }

        // Attempt to save the data.
        if (!$model->save($validData)) {
            // Save the data in the session.
            $app->setUserState($context . '.data', $validData);

            // Redirect back to the edit screen.
            $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $model->getError()), 'error');

            $this->setRedirect(
                Route::_(
                    'index.php?option=' . $this->option . '&view=' . $this->view_item
                        . $this->getRedirectToItemAppend($recordId, $urlVar),
                    false
                )
            );
        }

        // Redirect back to the edit screen.
        $this->setRedirect(
            Route::_(
                'index.php?option=com_easystore&view=customers',
                false
            )
        );
    }
}
