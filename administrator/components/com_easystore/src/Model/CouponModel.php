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
use Joomla\String\StringHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Component\ComponentHelper;
use JoomShaper\Component\EasyStore\Administrator\Constants\Status;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * EasyStore Component Coupon Model
 *
 * @since  1.0.0
 */
class CouponModel extends AdminModel
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
    public $typeAlias = 'com_easystore.coupon';

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
        if (empty($record->id) || (int) $record->published !== Status::TRASHED) {
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
     * Method to get a Coupon.
     *
     * @param   int  $pk  An optional id of the object to get, otherwise the id from the model state is used.
     *
     * @return  mixed  Coupon data object on success, false on failure.
     *
     * @since   1.0.0
     */
    public function getItem($pk = null)
    {
        if ($result = parent::getItem($pk)) {
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
     * @param   bool  $loadData  True if the form is to load its own data (default case), false if not.
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
        $form = $this->loadForm('com_easystore.coupon', 'coupon', ['control' => 'jform', 'load_data' => $loadData]);

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
        $data = $app->getUserState('com_easystore.edit.coupon.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_easystore.coupon', $data);

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
        /** @var \JoomShaper\Component\EasyStore\Administrator\Table\CouponTable $table */
        $table      = $this->getTable();
        $pk         = (!empty($data['id'])) ? $data['id'] : (int) $this->getState($this->getName() . '.id');
        $isNew      = true;
        $context    = $this->option . '.' . $this->name;

        // Include the plugins for the save events.
        PluginHelper::importPlugin($this->events_map['save']);

        try {
            // Load the row if saving an existing coupon.
            if ($pk > 0) {
                $table->load($pk);
                $isNew = false;
            }

            if (empty($data['has_date'])) {
                $data['has_date'] = 0;
                $data['end_date'] = null;
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

    /**
     * Method to change the title & alias.
     *
     * @param   string   $alias     The alias.
     * @param   string   $title     The title.
     *
     * @return  array  Contains the modified title and alias.
     *
     * @since   1.0.0
     */
    private function generateNewTitleLocally($alias, $title)
    {
        // Alter the title & alias
        $table = $this->getTable();

        while ($table->load(['alias' => $alias])) {
            $title = StringHelper::increment($title);
            $alias = StringHelper::increment($alias, 'dash');
        }
        return [$alias, $title];
    }

    /**
     * Function to check if code is unique
     *
     * @param string $code
     * @param int $id
     * @return bool
     *
     * @since 1.0.0
     */
    public static function checkIfCodeUnique(string $code, int $id)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select($db->quoteName('id'));
        $query->from($db->quoteName('#__easystore_coupons'));
        $query->where($db->quoteName('code') . ' = ' . $db->quote($code));
        if ($id > 0) {
            $query->where($db->quoteName('id') . ' != ' . $id);
        }

        $db->setQuery($query);
        $db->execute();
        $numRows = $db->getNumRows();

        if ($numRows > 0) {
            return false;
        }

        return true;
    }

    /**
     * Get Coupon data by code
     *
     * @param string $code Coupon code
     * @return object|null
     */
    public static function getByCode(string $code)
    {
        $app   = Factory::getApplication();
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('*');
        $query->from($db->quoteName('#__easystore_coupons'));
        $query->where($db->quoteName('code') . ' = ' . $db->quote($code));

        $db->setQuery($query);

        try {
            $result = $db->loadObject();
        } catch (\RuntimeException $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }

        return $result;
    }

    /**
     * Retrieves the total redeemed amount of a specified coupon.
     *
     * This function calculates the total number of times a coupon has been redeemed
     * by summing up the coupon usage counts from all users who have used the coupon.
     *
     * @param int $couponId The ID of the coupon for which the redeemed amount is being calculated.
     *
     * @since  1.2.0
     * @return int The total redeemed count of the coupon.
     */
    public function getCouponRedeemedAmount(int $couponId)
    {
        $orm             = new EasyStoreDatabaseOrm();
        $couponUsage     = $orm->hasMany($couponId, '#__easystore_user_coupon_usage', 'coupon_id')->loadObjectList();
        $redeemedCount   = 0;

        foreach ($couponUsage as $value) {
            $redeemedCount += (int) $value->coupon_count;
        }

        return $redeemedCount;
    }
}
