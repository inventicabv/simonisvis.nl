<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Table;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * User table
 *
 * @since  1.0.0
 */
class CustomerTable extends Table
{
    /**
     * Indicates that columns fully support the NULL value in the database
     *
     * @var    bool
     * @since  1.0.0
     */
    protected $_supportNullValue = true;

    /**
     * Constructor
     *
     * @param   DatabaseDriver  $db  A database connector object
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__easystore_users', 'id', $db);
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
            if (!(int) $this->created) {
                $this->created = $date->toSql();
            }

            if (empty($this->created_by)) {
                $this->created_by = $user->get('id');
            }

            // Existing item
            $this->modified_by = $user->get('id');
            $this->modified    = $date->toSql();
        } else {
            // New User. A User created and created_by field can be set by the user,
            // so we don't touch either of these if they are set.
            if (!(int) $this->created) {
                $this->created = $date->toSql();
            }

            if (empty($this->created_by)) {
                $this->created_by = $user->get('id');
            }

            if (!(int) $this->modified) {
                $this->modified = $this->created;
            }

            if (empty($this->modified_by)) {
                $this->modified_by = $this->created_by;
            }
        }

        return parent::store($updateNulls);
    }
}
