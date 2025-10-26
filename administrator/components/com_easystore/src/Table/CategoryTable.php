<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Table;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Nested;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Application\ApplicationHelper;
use JoomShaper\Component\EasyStore\Administrator\Concerns\HasAsset;

/**
 * Category table
 *
 * @since  1.0.0
 */
class CategoryTable extends Nested
{
    /**
     * Use the Assetable trait for managing asset_id
     *
     * @since 1.3.0
     */
    use HasAsset;

    /**
     * Indicates that columns fully support the NULL value in the database
     *
     * @var    bool
     * @since  1.0.0
     */
    protected $_supportNullValue = true;

    /**
     * The context for the permission management
     *
     * @var string
     */
    protected $context = 'com_easystore.category';

    /**
     * Constructor
     *
     * @param   DatabaseDriver  $db  Database connector object
     *
     * @since   1.0.0
     */
    public function __construct(DatabaseDriver $db)
    {
        $this->typeAlias = 'com_easystore.category';

        parent::__construct('#__easystore_categories', 'id', $db);
    }

    /**
     * Overloaded check method to ensure data integrity.
     *
     * @return  bool  True on success.
     *
     * @since   1.0.0
     * @throws  \UnexpectedValueException
     */
    public function check()
    {
        try {
            parent::check();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());

            return false;
        }

        // Check for valid name.
        if (trim($this->title) == '') {
            throw new \UnexpectedValueException(Text::_('COM_EASYSTORE_ERROR_TITLE_EMPTY'));
        }

        if (empty($this->alias)) {
            $this->alias = $this->title;
        }

        $this->alias = ApplicationHelper::stringURLSafe($this->alias, $this->language);

        if (trim(str_replace('-', '', $this->alias)) == '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        if (!(int) $this->checked_out_time) {
            $this->checked_out_time = null;
        }

        return true;
    }

    /**
     * Overridden \JTable::store to set modified data and user id.
     *
     * @param   bool  $updateNulls  True to update fields even if they are null.
     *
     * @return  bool  True on success.
     *
     * @since   1.0.0
     */
    public function store($updateNulls = true)
    {
        $date = Factory::getDate();
        $user = Factory::getApplication()->getIdentity();

        if ($this->id) {
            // Existing item
            $this->modified_by = $user->id;
            $this->modified    = $date->toSql();
        } else {
            // New category. A category created and created_by field can be set by the user,
            // so we don't touch either of these if they are set.
            if (!(int) $this->created) {
                $this->created = $date->toSql();
            }

            if (empty($this->created_by)) {
                $this->created_by = $user->id;
            }

            if (!(int) $this->modified) {
                $this->modified = $this->created;
            }

            if (empty($this->modified_by)) {
                $this->modified_by = $this->created_by;
            }
        }

        // Verify that the alias is unique
        $table = new static($this->getDbo());

        if ($table->load(['alias' => $this->alias]) && ($table->id != $this->id || $this->id == 0)) {
            $this->setError(Text::_('JLIB_DATABASE_ERROR_CATEGORY_UNIQUE_ALIAS'));

            // Is the existing category trashed?
            if ($table->published === -2) {
                $this->setError(Text::_('JLIB_DATABASE_ERROR_CATEGORY_UNIQUE_ALIAS_TRASHED'));
            }

            return false;
        }

        return parent::store($updateNulls);
    }


    /**
     * Method to delete a node and, optionally, its child nodes from the table.
     *
     * @param   int|null  $pk        The primary key of the node to delete.
     * @param   bool       $children  True to delete child nodes, false to move them up a level.
     *
     * @return  bool  True on success.
     */
    public function delete($pk = null, $children = false)
    {
        return parent::delete($pk, $children);
    }

    /**
     * Get the type alias for the history table
     *
     * @return  string  The alias as described above
     *
     * @since   1.0.0
     */
    public function getTypeAlias()
    {
        return $this->typeAlias;
    }
}
