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
use Joomla\CMS\Date\Date;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Application\ApplicationHelper;
use JoomShaper\Component\EasyStore\Administrator\Model\CouponModel;
use JoomShaper\Component\EasyStore\Administrator\Concerns\HasAsset;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Coupons table
 *
 * @since  1.0.0
 */
class CouponTable extends Table
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
     * The context of the coupon table for managing access control
     *
     * @var string
     */
    protected $context = 'com_easystore.coupon';

    /**
     * Constructor
     *
     * @param   DatabaseDriver  $db  A database connector object
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__easystore_coupons', 'id', $db);
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

        $this->alias = ApplicationHelper::stringURLSafe($this->alias);

        // Check for valid code.
        if (trim($this->code) == '') {
            throw new \UnexpectedValueException(Text::_('COM_EASYSTORE_ERROR_COUPON_CODE_EMPTY'));
        } else {
            if (!CouponModel::checkIfCodeUnique($this->code, $this->id)) {
                throw new \UnexpectedValueException(Text::_('COM_EASYSTORE_ERROR_COUPON_CODE_NOT_UNIQUE'));
            }
        }

        if (!empty($this->has_date) && empty($this->end_date)) {
            throw new \UnexpectedValueException(Text::_('COM_EASYSTORE_ERROR_COUPON_END_DATE_EMPTY'));
        }

        if (!empty($this->has_date) && !empty($this->end_date)) {
            $startDate = new Date($this->start_date);
            $endDate   = new Date($this->end_date);

            if ($startDate > $endDate) {
                throw new \UnexpectedValueException(Text::_('COM_EASYSTORE_ERROR_COUPON_END_DATE_SMALLER_THEN_START_DATE'));
            }
        }

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
            // New coupon. A coupon created and created_by field can be set by the user,
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

        if (empty($this->has_date) && !empty($this->end_date)) {
            $this->end_date = null;
        }

        // Verify that the alias is unique
        $table = new static($this->getDbo());

        if ($table->load(['alias' => $this->alias]) && ($table->id != $this->id || $this->id == 0)) {
            $this->setError(Text::_('COM_EASYSTORE_ERROR_UNIQUE_ALIAS'));

            // Is the existing coupon trashed?
            if ($table->published === -2) {
                $this->setError(Text::_('COM_EASYSTORE_ERROR_UNIQUE_ALIAS_TRASHED'));
            }

            return false;
        }

        return parent::store($updateNulls);
    }
}
