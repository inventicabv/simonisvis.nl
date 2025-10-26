<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Model;

use Joomla\Database\ParameterType;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use JoomShaper\Component\EasyStore\Administrator\Constants\Status;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * EasyStore Component Categories Model
 *
 * @since  1.0.0
 */
class CategoriesModel extends ListModel
{
    /**
     * Constructor.
     *
     * @param   array                     $config   An optional associative array of configuration settings.
     * @param   MVCFactoryInterface|null  $factory  The factory.
     *
     * @since   1.0.0
     */
    public function __construct($config = [], ?MVCFactoryInterface $factory = null)
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'title', 'a.title',
                'alias', 'a.alias',
                'published', 'a.published',
                'access', 'a.access', 'access_level',
                'language', 'a.language', 'language_title',
                'checked_out', 'a.checked_out',
                'checked_out_time', 'a.checked_out_time',
                'created', 'a.created',
                'created_by', 'a.created_by',
                'lft', 'a.lft',
                'rgt', 'a.rgt',
                'level', 'a.level',
                'path', 'a.path',
            ];
        }

        parent::__construct($config, $factory);
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function populateState($ordering = 'a.lft', $direction = 'asc')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);

        $access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
        $this->setState('filter.access', $access);

        $language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
        $this->setState('filter.language', $language);

        // List state information.
        parent::populateState($ordering, $direction);
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string  A store id.
     *
     * @since   1.0.0
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.access');
        $id .= ':' . $this->getState('filter.language');
        $id .= ':' . $this->getState('filter.level');

        return parent::getStoreId($id);
    }

    /**
     * Method to get a database query to list categories.
     *
     * @return  \Joomla\Database\DatabaseQuery
     *
     * @since   1.0.0
     */
    protected function getListQuery()
    {
        // Create a new query object.
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);
        $user  = $this->getCurrentUser();
        $acl   = AccessControl::create();

        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                'a.id, a.title, a.alias, a.published, a.access' .
                ', a.checked_out, a.checked_out_time, a.created_by' .
                ', a.path, a.parent_id, a.level, a.lft, a.rgt' .
                ', a.image, a.language, a.ordering'
            )
        );
        $query->from($db->quoteName('#__easystore_categories', 'a'));

        // Join over the language
        $query->select(
            [
                $db->quoteName('l.title', 'language_title'),
                $db->quoteName('l.image', 'language_image'),
            ]
        )
            ->join(
                'LEFT',
                $db->quoteName('#__languages', 'l'),
                $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('a.language')
            );

        // Join over the users for the checked out user.
        $query->select($db->quoteName('uc.name', 'editor'))
            ->join(
                'LEFT',
                $db->quoteName('#__users', 'uc'),
                $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out')
            );

        // Join over the asset groups.
        $query->select($db->quoteName('ag.title', 'access_level'))
            ->join(
                'LEFT',
                $db->quoteName('#__viewlevels', 'ag'),
                $db->quoteName('ag.id') . ' = ' . $db->quoteName('a.access')
            );

        // Join over the users for the author.
        $query->select($db->quoteName('ua.name', 'author_name'))
            ->join(
                'LEFT',
                $db->quoteName('#__users', 'ua'),
                $db->quoteName('ua.id') . ' = ' . $db->quoteName('a.created_by')
            );

        // Filter on the level.
        if ($level = (int) $this->getState('filter.level')) {
            $query->where($db->quoteName('a.level') . ' <= :level')
                ->bind(':level', $level, ParameterType::INTEGER);
        }

        // Filter by access level.
        if ($access = (int) $this->getState('filter.access')) {
            $query->where($db->quoteName('a.access') . ' = :access')
                ->bind(':access', $access, ParameterType::INTEGER);
        }

        // Implement View Level Access
        if (!$acl->isAdmin()) {
            $groups = $user->getAuthorisedViewLevels();
            $query->whereIn($db->quoteName('a.access'), $groups);
        }

        // Filter by published state
        $published = (string) $this->getState('filter.published');

        if (is_numeric($published)) {
            $published = (int) $published;
            $query->where($db->quoteName('a.published') . ' = :published')
                ->bind(':published', $published, ParameterType::INTEGER);
        } elseif ($published === '') {
            $query->whereIn($db->quoteName('a.published'), [Status::UNPUBLISHED, Status::PUBLISHED]);
        }

        $query->where($db->quoteName('a.alias') . ' != ' . $db->quote('root'));

        // Filter by search in title
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $search = (int) substr($search, 3);
                $query->where($db->quoteName('a.id') . ' = :search')
                    ->bind(':search', $search, ParameterType::INTEGER);
            } else {
                $search = '%' . str_replace(' ', '%', trim($search)) . '%';
                $query->extendWhere(
                    'AND',
                    [
                        $db->quoteName('a.title') . ' LIKE :title',
                        $db->quoteName('a.alias') . ' LIKE :alias',
                    ],
                    'OR'
                )
                    ->bind(':title', $search, ParameterType::STRING)
                    ->bind(':alias', $search, ParameterType::STRING);
            }
        }

        // Filter on the language.
        if ($language = $this->getState('filter.language')) {
            $query->where($db->quoteName('a.language') . ' = :language')
                ->bind(':language', $language);
        }

        // Add the list ordering clause
        $listOrdering = $this->getState('list.ordering', 'a.lft');
        $listDirn     = $db->escape($this->getState('list.direction', 'ASC'));

        if ($listOrdering == 'a.access') {
            $query->order('a.access ' . $listDirn . ', a.lft ' . $listDirn);
        } else {
            $query->order($db->escape($listOrdering) . ' ' . $listDirn);
        }

        return $query;
    }

    /**
     * Method to get an array of data items.
     *
     * @return  mixed  An array of data items on success, false on failure.
     *
     * @since   1.0.0
     */
    public function getItems()
    {
        $items = parent::getItems();

        return $items;
    }

    /**
     * Function to get Categories / Category by Id
     *
     * @param int|null $id
     * @param object $params
     * @return object
     */
    public function getCategories(object $params)
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true);
        $query->select(['c.id', 'c.title AS name', 'c.alias', 'c.description', 'c.parent_id', 'c.level', 'c.image', 'c.published', 'c.is_gift_card', 'c.is_uncategorised']);
        $query->from($db->quoteName('#__easystore_categories', 'c'));

        if (!empty($params->published) && $params->published !== null) {
            $query->where($db->quoteName('c.published') . " = " . $db->quote($params->published));
        }

        $query->where($db->quoteName('c.alias') . ' != ' . $db->quote('root'));

        if (!empty($params->excludePresets)) {
            $query->where($db->quoteName('c.is_gift_card') . " != 1");
            $query->where($db->quoteName('c.is_uncategorised') . " != 1");
        }

        $db->setQuery($query);

        try {
            $categories = $db->loadObjectList();
            $types      = [
                'is_gift_card'     => 'boolean',
                'is_uncategorised' => 'boolean',
            ];

            return EasyStoreHelper::typeCorrection($categories, $types);
        } catch (\Exception $e) {
            return [];
        }
    }
}
