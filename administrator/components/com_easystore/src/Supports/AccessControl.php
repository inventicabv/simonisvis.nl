<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Supports;

use Throwable;
use RuntimeException;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


final class AccessControl
{
    /**
     * Joomla User instance
     *
     * @var User
     */
    private $user;

    /**
     * The permission context.
     *
     * @var string
     */
    private $context = null;

    /**
     * The asset key where to check the permissions.
     *
     * @var string
     */
    private $assetKey = 'com_easystore';

    /**
     * The access control instance
     *
     * @var AccessControl
     */
    private static $instance = null;

    /**
     * The constructor method for creating an acl instance
     *
     * @param User $user The Joomla User
     */
    private function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Create the instance of the AccessControl
     *
     * @return self
     */
    public static function create()
    {
        /** @var CMSApplication $app */
        $app  = Factory::getApplication();
        $user = $app->getIdentity();

        if (is_null(self::$instance)) {
            self::$instance = new self($user);
        }

        return self::$instance;
    }

    /**
     * Set the context for grabbing the record by id.
     *
     * @param string $context The context value e.g. product, order, category etc.
     * @return self
     */
    public function setContext(string $context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Set the asset for the Joomla access control. Asset is basically the component name (e.g. com_easystore)
     * This asset is required to get the Joomla Access Controls. The default value is com_easystore
     * If you need to change the component and need to check the acl for other component then set this.
     *
     * @param string $assetKey The asset name
     * @return self
     */
    public function setAsset(string $assetKey)
    {
        $this->assetKey = $assetKey;

        return $this;
    }

    /**
     * Reset the context and the asset key after checking the permission.
     *
     * @return self
     */
    private function reset()
    {
        $this->context    = null;
        $this->assetKey   = 'com_easystore';

        return $this;
    }

    /**
     * Check if the user can do the following actions
     *
     * @param string|array<string> $actions The actions to check the permission
     * @return bool
     */
    public function can($actions)
    {
        if (!is_array($actions)) {
            $actions = [$actions];
        }

        $canDo = true;

        foreach ($actions as $action) {
            $canDo = $canDo && $this->user->authorise($action, $this->assetKey);
        }

        $this->reset();

        return $canDo;
    }

    /**
     * User has core manage access or not.
     *
     * @return bool
     */
    public function canManage()
    {
        return $this->can('core.manage');
    }

    /**
     * User has create access or not.
     *
     * @return bool
     */
    public function canCreate()
    {
        return $this->can('core.create');
    }

    /**
     * User has edit access or not.
     *
     * @return bool
     */
    public function canEdit()
    {
        return $this->can('core.edit');
    }

    /**
     * User has edit has his/her own item access or not.
     *
     * @return bool
     */
    public function canEditOwn($itemId)
    {
        $item = $this->getItem($itemId);
        return $this->can('core.edit.own') && (int) $item->created_by === (int) $this->user->id;
    }

    /**
     * User has edit state access or not.
     *
     * @return bool
     */
    public function canEditState()
    {
        return $this->can('core.edit.state');
    }

    /**
     * User has delete access or not.
     *
     * @return bool
     */
    public function canDelete()
    {
        return $this->can('core.delete');
    }

    /**
     * User has option edit access or not.
     *
     * @return bool
     */
    public function canManageOptions()
    {
        return $this->can('core.options');
    }

    /**
     * User has admin access or not.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->can('core.admin');
    }

    /**
     * User has the import ability or not.
     *
     * @return bool
     */
    public function canImport()
    {
        return $this->can('core.import');
    }

    /**
     * User has the export ability or not.
     *
     * @return bool
     */
    public function canExport()
    {
        return $this->can('core.export');
    }

    /**
     * Get the record from the table by it's primary key.
     *
     * @param string    $table      The table name.
     * @param int       $recordId   The record id or primary key value.
     * @param string    $pk         The primary key.
     *
     * @return object|null
     */
    private function getRecord(string $table, int $recordId, string $pk = 'id')
    {
        try {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);
            $query->select('*')
                ->from($db->quoteName($table))
                ->where($db->quoteName($pk) . ' = ' . $recordId);
            $db->setQuery($query);

            return $db->loadObject();
        } catch (Throwable $error) {
            return null;
        }
    }

    /**
     * Get the item by item id and according to the context.
     *
     * @param int $itemId The item id.
     * @return object|null
     * @throws RuntimeException
     */
    private function getItem(int $itemId)
    {
        if (empty($this->context)) {
            throw new RuntimeException(
                sprintf('Missing the context from where to fetch the item by ID. Maybe you missed to set context by setContext() method')
            );
        }

        $contextTableMap = [
            'media'      => 'media',
            'cart'       => 'cart',
            'temp_media' => 'temp_media',
            'wishlist'   => 'wishlist',
            'category'   => 'categories',
        ];

        $tablename = isset($contextTableMap[$this->context]) ? $contextTableMap[$this->context] : $this->context . 's';
        $tablename =  '#__easystore_' . $tablename;

        return $this->getRecord($tablename, $itemId);
    }
}
