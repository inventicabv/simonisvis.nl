<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Model;

\defined('_JEXEC') or die('Restricted Direct Access!');

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\String\StringHelper;
use JoomShaper\Component\EasyStore\Administrator\Concerns\HasRelationship;
use Throwable;

/**
 * Model class for Category View
 *
 * @since 1.4.0
 */
class CollectionModel extends AdminModel
{
    use HasRelationship;

    /**
     * The type alias for this content type.
     *
     * @var    string
     *
     * @since  1.4.0
     */
    public $typeAlias = 'com_easystore.collection';

    /**
     * Name of the form
     *
     * @var string
     *
     * @since  1.0.0
     */
    protected $formName = 'collection';

    /**
     * @var    string  The name of the pivot table for collections.
     * @since  1.5.0
     */
    private const COLLECTIONS_TABLE = '#__easystore_collection_product_map';

    /**
     * Method to test whether a record can be deleted.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
     *
     * @since   1.4.0
     */
    protected function canDelete($record)
    {
        if (empty($record->id) || (int) $record->published !== -2) {
            return false;
        }

        return Factory::getApplication()->getIdentity()->authorise('core.delete', 'com_easystore.collection.' . (int) $record->id);
    }

    /**
     * Method to test whether a record can have its state edited.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to change the state of the record. Defaults to the permission set in the component.
     *
     * @since   1.4.0
     */
    protected function canEditState($record)
    {
        // Check against the category.
        if (!empty($record->id)) {
            return Factory::getApplication()->getIdentity()->authorise('core.edit.state', 'com_easystore.collection.' . (int) $record->id);
        }

        // Default to component settings if category not known.
        return parent::canEditState($record);
    }

    /**
     * Method to check if you can save a record.
     *
     * @param   array   $data  An array of input data.
     * @param   string  $key   The name of the key for the primary key.
     *
     * @return  boolean
     *
     * @since   1.4.0
     */
    protected function canSave($data = array(), $key = 'id')
    {
        if (empty($data[$key])) {
            return false;
        }

        return Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_easystore.collection.' . $data[$key]);
    }

    /**
     * Method to get the row form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  Form|boolean  A Form object on success, false on failure
     *
     * @since   1.4.0
     */
    public function getForm($data = array(), $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_easystore.' . $this->formName, $this->formName, array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   1.4.0
     */
    protected function loadFormData()
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();

        // Check the session for previously entered form data.
        $data = $app->getUserState('com_easystore.edit.collection.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_easystore.collection', $data);

        return $data;
    }

    /**
     * Method to save the form data.
     *
     * @param   array  $data  The form data.
     *
     * @return  boolean  True on success.
     *
     * @since   1.4.0
     */
    public function save($data)
    {
        $table = $this->getTable();
        $input = Factory::getApplication()->getInput();
        $pk = !empty($data['id']) ? (int) $data['id'] : (int) $this->getState($this->getName() . '.id', 0);

        if ($pk > 0) {
            $table->load($pk);
        }

        // Alter the name for save as copy
        if ($input->get('task') === 'save2copy') {
            $origTable = clone $this->getTable();
            $origTable->load($input->getInt('id'));

            if ($data['title'] == $origTable->title) {
                list($title, $alias) = $this->generateUniqueTitleAlias($data['alias'], $data['title']);
                $data['title'] = $title;
                $data['alias'] = $alias;
            } else {
                if ($data['alias'] === $origTable->alias) {
                    $data['alias'] = '';
                }
            }

            $data['published'] = 0;
        }

        try {
            if (!$table->bind($data)) {
                return false;
            }

            if (!$table->check()) {
                return false;
            }

            if (!$table->store()) {
                return false;
            }

            // Store the products to the pivot table.
            $collectionId = $table->id ?? 0;
            $products = $data['products'] ?? [];

            if (is_string($products)) {
                $products = json_decode($products, true);
            }

            if (empty($products) || !is_array($products)) {
                $products = [];
            }

            if ($collectionId) {
                $this->storeRelationships($collectionId, $products, 'collection', 'product', self::COLLECTIONS_TABLE, true);
            }
        } catch (Throwable $error) {
            throw $error;
        }

        $this->setState($this->getName() . '.id', $table->id);

        return true;
    }

    /**
     * Method to change the published state of one or more records.
     * Your can perform your own logic before publish/unpublish the record item.
     *
     * @param array    $pks    A list of the primary keys to change.
     * @param integer  $value  The value of the published state.
     *
     * @return boolean  True on success.
     *
     * @since 1.4.0
     */
    public function publish(&$pks, $value = 1)
    {
        return parent::publish($pks, $value);
    }

    /**
     * Generate new alias & title values if duplicate alias found.
     *
     * @param string  $alias  The alias string.
     * @param string  $title  The title string.
     *
     * @return array  The updated title, alias array.
     *
     * @since 1.4.0
     */
    private function generateUniqueTitleAlias(string $alias, string $title): array
    {
        $table = $this->getTable();

        while ($table->load(['alias' => $alias])) {
            if ($title === $table->title) {
                $title = StringHelper::increment($title);
            }

            $alias = StringHelper::increment($alias, 'dash');
        }

        return [$title, $alias];
    }

    /**
     * Prepare and sanitize the table prior to saving.
     *
     * @param   \Joomla\CMS\Table\Table  $table  The Table object
     *
     * @return  void
     *
     * @since   1.4.0
     */
    protected function prepareTable($table)
    {
    }

    /**
     * A protected method to get a set of ordering conditions.
     *
     * @param   Table  $table  A Table object.
     *
     * @return  array  An array of conditions to add to ordering queries.
     *
     * @since   1.4.0
     */
    protected function getReorderConditions($table)
    {
        return [];
    }

    /**
     * Preprocess the form.
     *
     * @param   Form    $form   Form object.
     * @param   object  $data   Data object.
     * @param   string  $group  Group name.
     *
     * @return  void
     *
     * @since   1.4.0
     */
    protected function preprocessForm(Form $form, $data, $group = 'easystore')
    {
        parent::preprocessForm($form, $data, $group);
    }

    /**
     * Method to get a single record.
     *
     * @param   integer  $pk  The id of the primary key.
     *
     * @return  mixed  Object on success, false on failure.
     *
     * @since   1.4.0
     */
    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        $collectionId = $item->id ?? 0;
        $item->products = $this->getRelatedRecords($collectionId, 'collection', 'product', self::COLLECTIONS_TABLE);

        /** @var CMSApplication */
        $app      = Factory::getApplication();
        $document = $app->getDocument();
        $document->addScriptOptions('easystore_collection_products', $this->getCollectionProducts($collectionId));

        return $item;
    }

    /**
     * Get products associated with a collection.
     *
     * @param int $collectionId The ID of the collection.
     *
     * @return array An array of product objects with id, title, and image.
     *
     * @since 1.4.0
     */
    private function getCollectionProducts(int $collectionId)
    {
        $products = $this->getRelatedRecords($collectionId, 'collection', 'product', self::COLLECTIONS_TABLE);

        return $this->getProductDetails($products);
    }
}
