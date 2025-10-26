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
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Application\ApplicationHelper;
use JoomShaper\Component\EasyStore\Administrator\Concerns\HasAsset;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Tags table
 *
 * @since  1.0.0
 */
class UserTable extends Table
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
     * The asset context for checking the permission
     *
     * @var string
     */
    protected $context = 'com_easystore.user';

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

        // Check for valid title.
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
            $this->modified_by = $user->get('id');
            $this->modified    = $date->toSql();
        } else {
            // New tag. A tag created and created_by field can be set by the user,
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

        // Verify that the alias is unique
        $table = new static($this->getDbo());

        if ($table->load(['alias' => $this->alias]) && ($table->id != $this->id || $this->id == 0)) {
            $this->setError(Text::_('COM_EASYSTORE_ERROR_UNIQUE_ALIAS'));

            // Is the existing tag trashed?
            if ($table->published === -2) {
                $this->setError(Text::_('COM_EASYSTORE_ERROR_UNIQUE_ALIAS_TRASHED'));
            }

            return false;
        }

        return parent::store($updateNulls);
    }
}
