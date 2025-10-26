<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2024, JoomShaper
 * @license     MIT
 */

namespace JoomShaper\Component\EasyStore\Administrator\Table;

\defined('_JEXEC') or die('Restricted Direct Access!');

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use JoomShaper\Component\EasyStore\Administrator\Concerns\HasAsset;
use JoomShaper\Component\EasyStore\Administrator\Concerns\HasMetadata;

/**
 * Collection Table class.
 *
 * @since   1.4.0
 */
class CollectionTable extends Table
{
    use HasMetadata;
    use HasAsset;

    /**
     * The context for asset management.
     *
     * @var    string
     * @since  1.4.0
     */
    protected $context = 'com_easystore.collection';

    /**
     * Constructor
     *
     * @param   DatabaseDriver  $db  Database connector object
     *
     * @since   1.4.0
     */
    public function __construct(DatabaseDriver $db)
    {
        $this->typeAlias = $this->context;
        parent::__construct('#__easystore_collections', 'id', $db);
    }

    /**
     * Method to bind an associative array or object to the Table instance.This
     * method only binds properties that are publicly accessible and optionally
     * takes an array of properties to ignore when binding.
     *
     * @param   array|object  $src     An associative array or object to bind to the Table instance.
     * @param   array|string  $ignore  An optional array or space separated list of properties to ignore while binding.
     *
     * @return  boolean  True on success.
     *
     * @since   1.4.0
     * @throws  \InvalidArgumentException
     */
    public function bind($src, $ignore = array())
    {
        return parent::bind($src, $ignore);
    }

    /**
     * Stores a contact.
     *
     * @param   boolean  $updateNulls  True to update fields even if they are null.
     *
     * @return  boolean  True on success, false on failure.
     *
     * @since   1.4.0
     */
    public function store($updateNulls = true)
    {
        $date   = Factory::getDate()->toSql();
        $userId = Factory::getApplication()->getIdentity()->id;

        // Set created date if not set.
        if (!(int) $this->created) {
            $this->created = $date;
        }

        if ($this->id) {
            // Existing item
            $this->modified_by = $userId;
            $this->modified    = $date;
        } else {
            // Field created_by field can be set by the user, so we don't touch it if it's set.
            if (empty($this->created_by)) {
                $this->created_by = $userId;
            }

            if (!(int) $this->modified) {
                $this->modified = $date;
            }

            if (empty($this->modified_by)) {
                $this->modified_by = $userId;
            }
        }

        // Verify that the alias is unique
        $table = new self($this->getDbo(), $this->getDispatcher());

        if ($table->load(['alias' => $this->alias]) && ($table->id != $this->id || $this->id == 0)) {
            $this->setError(Text::_('COM_EASYSTORE_ERROR_UNIQUE_ALIAS'));

            return false;
        }

        return parent::store($updateNulls);
    }

    /**
     * Overloaded check function
     *
     * @return  boolean  True on success, false on failure
     *
     * @see     \JTable::check
     * @since   1.4.0
     */
    public function check()
    {
        try {
            parent::check();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());

            return false;
        }

        if (empty($this->ordering)) {
            $this->ordering = 0;
        }

        if (!(int) $this->checked_out) {
            $this->checked_out = null;
        }

        if (!(int) $this->checked_out_time) {
            $this->checked_out_time = null;
        }

        // Generate a valid alias
        $this->alias = $this->generateAlias();
        $this->validateMetadata($this);

        return true;
    }

    /**
     * Generate a valid alias from title / date.
     * Remains public to be able to check for duplicated alias before saving
     *
     * @return  string
     *
     * @since   1.4.0
     */
    public function generateAlias()
    {
        if (empty($this->alias)) {
            $this->alias = $this->title;
        }

        $this->alias = ApplicationHelper::stringURLSafe($this->alias, $this->language);

        if (trim(str_replace('-', '', $this->alias)) === '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        return $this->alias;
    }

    /**
     * Get the type alias for the history and tags mapping table
     *
     * @return  string  The alias as described above
     *
     * @since   1.4.0
     */
    public function getTypeAlias()
    {
        return $this->typeAlias;
    }
}
