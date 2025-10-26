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

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * EasyStore Component Category Model
 *
 * @since  1.0.0
 */
class CategoryModel extends AdminModel
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
    public $typeAlias = 'com_easystore.category';

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
     * Method to get a Category.
     *
     * @param   int  $pk  An optional id of the object to get, otherwise the id from the model state is used.
     *
     * @return  mixed  Category data object on success, false on failure.
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
        $form = $this->loadForm('com_easystore.category', 'category', ['control' => 'jform', 'load_data' => $loadData]);

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
        $data = $app->getUserState('com_easystore.edit.category.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_easystore.category', $data);

        return $data;
    }

    /**
     * Method to save the form data.
     *
     * @param   array   $data        The form data.
     * @param   bool    $isImport    True if it is called from import product.
     *
     * @return  bool|int             True on success & int for $isImport = true.
     *
     * @since   1.0.0
     */
    public function save($data, $isImport = false)
    {
        /** @var \JoomShaper\Component\EasyStore\Administrator\Table\CategoryTable $table */
        $table      = $this->getTable();
        $input      = Factory::getApplication()->getInput();
        $pk         = (!empty($data['id'])) ? $data['id'] : (int) $this->getState($this->getName() . '.id');
        $isNew      = true;
        $context    = $this->option . '.' . $this->name;

        if ($isImport) {
            $returnId = 0;
        }

        // Include the plugins for the save events.
        PluginHelper::importPlugin($this->events_map['save']);

        try {
            // Load the row if saving an existing category.
            if ($pk > 0) {
                $table->load($pk);
                $isNew = false;
            }

            if (empty($data['parent_id'])) {
                $data['parent_id'] = $this->getRootParentId(); // Set default parent id
            }

            // Set the new parent id if parent id not matched OR while New/Save as Copy .
            if ($table->parent_id != $data['parent_id'] || $data['id'] == 0) {
                $table->setLocation($data['parent_id'], 'last-child');
            }

            // Alter the title for save as copy
            if ($input->get('task') === 'save2copy') {
                $origTable = $this->getTable();
                $origTable->load($input->getInt('id'));

                if ($data['title'] === $origTable->title) {
                    list($title, $alias)  = $this->generateNewTitleLocally($data['alias'], $data['title']);
                    $data['title']        = $title;
                    $data['alias']        = $alias;
                } elseif ($data['alias'] === $origTable->alias) {
                    $data['alias'] = '';
                }

                $data['published'] = Status::UNPUBLISHED;
            }

            if (empty($data['created_by'])) {
                $user                    = Factory::getApplication()->getIdentity();
                $data['created_by']      = $user->id;
            }

            $data['level'] = 1;

            if (!empty($data['parent_id'])) {
                $data['level'] = $this->calculateLevelByParentId($data['parent_id']);
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

            // Rebuild the path for the category:
            if (!$table->rebuildPath($table->id)) {
                $this->setError($table->getError());

                return false;
            }

            // Rebuild the paths of the category's children:
            if (!$table->rebuild($table->id, $table->lft, $table->level, $table->path)) {
                $this->setError($table->getError());

                return false;
            }

            // Trigger the after save event.
            Factory::getApplication()->triggerEvent($this->event_after_save, [$context, $table, $isNew]);

            if ($isImport) {
                $returnId = $table->id;
            }
        } catch (\Exception $e) {
            $this->setError($e->getMessage());

            return false;
        }

        $this->setState($this->getName() . '.id', $table->id);
        $this->setState($this->getName() . '.new', $isNew);

        // Clear the cache
        $this->cleanCache();

        return $isImport ? $returnId : true;
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
        return [$title, $alias];
    }

    /**
     * Function to calculate level of a category if it has parent
     *
     * @param int $id
     * @return int
     *
     * @since 1.0.0
     */
    protected function calculateLevelByParentId(int $id)
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select($db->quoteName('level'));
        $query->from($db->quoteName('#__easystore_categories'));
        $query->where($db->quoteName('id') . ' = ' . $id);

        $db->setQuery($query);
        $db->execute();
        $results = $db->loadObject();

        $calculatedLevel = $results->level + 1;

        return $calculatedLevel;
    }

    /**
     * Method to save the reordered nested set tree.
     * First we save the new order values in the lft values of the changed ids.
     * Then we invoke the table rebuild to implement the new ordering.
     *
     * @param   array    $idArray   An array of primary key ids.
     * @param   int  $lftArray  The lft value
     *
     * @return  bool  False on failure or error, True otherwise
     *
     * @since   1.0.0
     */
    public function saveorder($idArray = null, $lftArray = null)
    {
        /** @var CategoryTable */
        $table = $this->getTable();

        if (!$table->saveorder($idArray, $lftArray)) {
            $this->setError($table->getError());

            return false;
        }

        // Clear the cache
        $this->cleanCache();

        return true;
    }

    /**
     * Get the Root Id of Categories
     *
     * @return int
     */
    public function getRootParentId()
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select($db->quoteName('id'))
            ->from($db->quoteName('#__easystore_categories'))
            ->where([$db->quoteName('title') . ' = ' . $db->quote('ROOT'), $db->quoteName('alias') . ' = ' . $db->quote('root'), $db->quoteName('level') . ' = 0']);

        $db->setQuery($query);
        $rootCategory = $db->loadObject();

        return !empty($rootCategory) ? $rootCategory->id : 0;
    }
}
