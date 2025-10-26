<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Component\ComponentHelper;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * EasyStore Component Review Model
 *
 * @since  1.0.0
 */
class ReviewModel extends AdminModel
{
    /**
     * @var    string  The prefix to use with controller messages.
     * @since  1.0.0
     */
    protected $text_prefix = 'COM_EASYSTORE';

    /**
     * @var    string  The type alias for this content type.
     * @since  1.0.0
     */
    public $typeAlias = 'com_easystore.review';

    /**
     * Method to test whether a record can be deleted.
     *
     * @param   object  $record  A record object.
     *
     * @return  bool  True if allowed to delete the record. Defaults to the permission set in the component.
     *
     * @since   1.0.0
     */
    protected function canDelete($record)
    {
        if (empty($record->id) || $record->published != -2) {
            return false;
        }

        return parent::canDelete($record);
    }

    /**
     * Auto-populate the model state.
     *
     * @note Calling getState in this method will result in recursion.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function populateState()
    {
        $app = Factory::getApplication();

        // Load the User state.
        $pk = $app->getInput()->getInt('id');
        $this->setState($this->getName() . '.id', $pk);

        // Load the parameters.
        $params = ComponentHelper::getParams('com_easystore');
        $this->setState('params', $params);
    }

    /**
     * Method to get a review.
     *
     * @param   int  $pk  An optional id of the object to get, otherwise the id from the model state is used.
     *
     * @return  mixed     Review data object on success, false on failure.
     *
     * @since   1.0.0
     */
    public function getItem($pk = null)
    {
        if ($result = parent::getItem($pk)) {
            $orm           = new EasyStoreDatabaseOrm();
            $productsModel = new ProductsModel();

            if (!is_null($result->product_id)) {
                $result->product            = $orm->hasOne($result->product_id, '#__easystore_products', 'id')->loadObject();
                $result->product->thumbnail = '';
                $media                      = $productsModel->getMedia($result->product_id);

                if (!empty($media)) {
                    $result->product->thumbnail = $media->thumbnail->src;
                }
            }

            // Convert the modified date to local user time for display in the form.
            $tz = new \DateTimeZone(Factory::getApplication()->get('offset'));

            if ((int) $result->modified) {
                $date = new Date($result->modified);
                $date->setTimezone($tz);
                $result->modified = $date->toSql(true);
            } else {
                $result->modified = null;
            }
        }

        return $result;
    }

    /**
     * Method to get the row form.
     *
     * @param   array    $data      Data for the form.
     * @param   bool     $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  bool|\Joomla\CMS\Form\Form  A Form object on success, false on failure
     *
     * @since   1.0.0
     */
    public function getForm($data = [], $loadData = true)
    {
        $input = Factory::getApplication()->getInput();
        $acl   = AccessControl::create();

        // Get the form.
        $form = $this->loadForm('com_easystore.review', 'review', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        $asset = $this->typeAlias . '.' . $input->get('id');

        if (!$acl->setAsset($asset)->canEditState()) {
            // Disable fields for display.
            $form->setFieldAttribute('ordering', 'disabled', 'true');
            $form->setFieldAttribute('published', 'disabled', 'true');

            // Disable fields while saving.
            // The controller has already verified this is a record you can edit.
            $form->setFieldAttribute('ordering', 'filter', 'unset');
            $form->setFieldAttribute('published', 'filter', 'unset');
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   1.0.0
     */
    protected function loadFormData()
    {
        /** @var CMSApplication $app */
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_easystore.edit.review.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_easystore.review', $data);

        return $data;
    }

    /**
     * Method to save the form data.
     *
     * @param   array  $data  The form data.
     *
     * @return  bool  True on success.
     *
     * @since   1.0.0
     */
    public function save($data)
    {
        /** @var \JoomShaper\Component\EasyStore\Administrator\Table\ReviewTable $table */
        $table      = $this->getTable();
        $input      = Factory::getApplication()->getInput();
        $pk         = (!empty($data['id'])) ? $data['id'] : (int) $this->getState($this->getName() . '.id');
        $isNew      = true;
        $context    = $this->option . '.' . $this->name;

        // Include the plugins for the save events.
        PluginHelper::importPlugin($this->events_map['save']);

        try {
            // Load the row if saving an existing review.
            if ($pk > 0) {
                $table->load($pk);
                $isNew = false;
            }

            // Bind the data.
            if (!$table->bind($data)) {
                $this->setError($table->getError());

                return false;
            }

            // Prepare the row for saving
            $this->prepareTable($table);

            // Check the data.
            if (!$table->check()) {
                $this->setError($table->getError());

                return false;
            }

            // Trigger the before save event.
            $result = Factory::getApplication()->triggerEvent($this->event_before_save, [$context, $table, $isNew, $data]);

            if (in_array(false, $result, true)) {
                $this->setError($table->getError());

                return false;
            }

            // Store the data.
            if (!$table->store()) {
                $this->setError($table->getError());

                return false;
            }

            // Trigger the after save event.
            Factory::getApplication()->triggerEvent($this->event_after_save, [$context, $table, $isNew]);
        } catch (\Exception $e) {
            $this->setError($e->getMessage());

            return false;
        }

        $this->setState($this->getName() . '.id', $table->id);
        $this->setState($this->getName() . '.new', $isNew);

        // Clear the cache
        $this->cleanCache();

        return true;
    }
}
