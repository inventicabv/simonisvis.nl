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
use JoomShaper\Component\EasyStore\Administrator\Model\OrderModel;
use JoomShaper\Component\EasyStore\Administrator\Concerns\HasAsset;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Orders table
 *
 * @since  1.0.0
 */
class OrderTable extends Table
{
    /**
     * Use the Assetable trait for managing asset_id
     *
     * @since 1.3.0
     */
    use HasAsset;

    /**
     * @var string
     * @since 1.6.1
     */
    public $custom_invoice_id = null;

    /**
     * Indicates that columns fully support the NULL value in the database
     *
     * @var    bool
     * @since  1.0.0
     */
    protected $_supportNullValue = true;

    /**
     * The context of the order table for access control.
     *
     * @var string
     * @since 1.3.0
     */
    protected $context = 'com_easystore.order';

    /**
     * Constructor
     *
     * @param   DatabaseDriver  $db  A database connector object
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__easystore_orders', 'id', $db);
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
            $this->modified    = $date->toSql(true);
        } else {
            if (!(int) $this->created) {
                $this->created = $date->toSql(true);
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

        if (!empty($this->shipping_type)) {
            $chunk                = explode(':', $this->shipping_type);
            $this->shipping_value = (float) $chunk[0];
            $this->shipping_type  = $chunk[1];
        }

        // Will work later
        $this->order_shipping_address_id = 0;
        $this->order_billing_address_id  = 0;

        return parent::store($updateNulls);
    }

    /**
     * Function to generate Unique ID
     *
     * @return string
     */
    private function generateUniqueId()
    {
        $isUnique = false;

        while (!$isUnique) {
            $uniqueId = uniqid();
            $uniqueId = '#' . strtoupper($uniqueId);
            $isUnique = OrderModel::isTrackingIdUnique($uniqueId);
        }

        return $uniqueId;
    }
}
