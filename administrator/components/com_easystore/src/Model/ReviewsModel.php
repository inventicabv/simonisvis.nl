<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Model;

use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;
use Joomla\CMS\MVC\Model\ListModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * This models supports retrieving a list of reviews.
 *
 * @since  1.0.0
 */
class ReviewsModel extends ListModel
{
    /**
     * Constructor.
     *
     *
     * @param   array   $config   An optional associative array of configuration settings.
     *
     * @since   1.0.0
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id',
                'a.id',
                'review',
                'a.review',
                'rating',
                'a.rating',
                'access_title',
                'a.access',
                'published',
                'a.published',
                'modified',
                'a.modified',
                'modified_by',
                'a.modified_by',
                'created',
                'a.created',
                'created_by',
                'a.created_by',
            ];
        }

        parent::__construct($config);
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
    protected function populateState($ordering = 'a.id', $direction = 'asc')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);

        $access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
        $this->setState('filter.access', $access);

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
     *
     * @since   1.0.0
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.access');
        $id .= ':' . $this->getState('filter.published');

        return parent::getStoreId($id);
    }

    /**
     * Method to create a query for a list of items.
     *
     * @return  DatabaseQuery
     *
     * @since  1.0.0
     */
    protected function getListQuery()
    {
        // Create a new query object.
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);
        $user  = $this->getCurrentUser();

        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                'a.*'
            )
        );
        $query->from($db->quoteName('#__easystore_reviews', 'a'));

        // Join over the users for the checked out user.
        $query->select($db->quoteName('uc.name', 'user_name'))
        ->join('LEFT', $db->quoteName('#__users', 'uc'), $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.created_by'));

        // Join over the products for the checked out user.
        $query->select($db->quoteName('p.title', 'product_name'))
        ->join('LEFT', $db->quoteName('#__easystore_products', 'p'), $db->quoteName('p.id') . ' = ' . $db->quoteName('a.product_id'));

        // Join over the users for the author.
        $query->select($db->quoteName('ua.name', 'author_name'))
        ->join('LEFT', $db->quoteName('#__users', 'ua'), $db->quoteName('ua.id') . ' = ' . $db->quoteName('a.created_by'))
        ->select($db->quoteName('ug.title', 'access_title'))
        ->join('LEFT', $db->quoteName('#__viewlevels', 'ug'), $db->quoteName('ug.id') . ' = ' . $db->quoteName('a.access'));


        // Filter by published state
        $published = (string) $this->getState('filter.published');

        if (is_numeric($published)) {
            $published = (int) $published;
            $query->where($db->quoteName('a.published') . ' = :published')
            ->bind(':published', $published, ParameterType::INTEGER);
        } elseif ($published === '') {
            $query->whereIn($db->quoteName('a.published'), [0, 1]);
        }

        // Filter by access level.
        if ($access = (int) $this->getState('filter.access')) {
            $query->where($db->quoteName('a.access') . ' = :access')
              ->bind(':access', $access, ParameterType::INTEGER);
        }

        // Filter by search in customer name
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $ids = (int) substr($search, 3);
                $query->where($db->quoteName('a.id') . ' = :id')
                ->bind(':id', $ids, ParameterType::INTEGER);
            } else {
                $search = '%' . str_replace(' ', '%', trim($search)) . '%';
                $query->extendWhere(
                    'AND',
                    [
                        $db->quoteName('ua.name') . ' LIKE :title',
                    ],
                    'OR'
                );
                $query->bind(':title', $search);
            }
        }

        // Add the list ordering clause
        $listOrdering = $this->getState('list.ordering', 'a.id');
        $listDirn     = $db->escape($this->getState('list.direction', 'ASC'));

        if ($listOrdering == 'a.access') {
            $query->order('a.access ' . $listDirn . ', a.id ' . $listDirn);
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
}
