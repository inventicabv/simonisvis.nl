<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;
use JoomShaper\Component\EasyStore\Administrator\Supports\Arr;

\defined('_JEXEC') or die('Restricted Direct Access!');

/**
 * Category listing model.
 *
 * @since 1.4.0
 */
class CollectionsModel extends ListModel
{
    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @see     \JControllerLegacy
     *
     * @since   1.4.0
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'title', 'a.title',
                'alias', 'a.alias',
                'checked_out', 'a.checked_out',
                'checked_out_time', 'a.checked_out_time',
                'published', 'a.published',
                'access', 'a.access', 'access_level',
                'created', 'a.created',
                'created_by', 'a.created_by',
                'ordering', 'a.ordering',
                'language', 'a.language', 'language_title',
                'product_count', 'a.product_count'
            );
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * Collection. Calling getState in this method will result in recursion.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since   1.4.0
     */
    protected function populateState($ordering = 'a.title', $direction = 'asc')
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();

        $forcedLanguage = $app->getInput()->get('forcedLanguage', '', 'cmd');

        // Adjust the context to support modal layouts.
        if ($layout = $app->input->get('layout')) {
            $this->context .= '.' . $layout;
        }

        // Adjust the context to support forced languages.
        if ($forcedLanguage) {
            $this->context .= '.' . $forcedLanguage;
        }

        // List state information.
        parent::populateState($ordering, $direction);

        // Force a language.
        if (!empty($forcedLanguage)) {
            $this->setState('filter.language', $forcedLanguage);
        }
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
     * @since   1.4.0
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.access');
        $id .= ':' . $this->getState('filter.language');
        $id .= ':' . serialize($this->getState('filter.tag'));
        $id .= ':' . $this->getState('filter.level');

        return parent::getStoreId($id);
    }

/**
     * Build an SQL query to load the list data.
     *
     * @return  \JDatabaseQuery
     *
     * @since   1.4.0
     */
    protected function getListQuery()
    {
        $container = Factory::getContainer();
        $app = Factory::getApplication();
        $db = $container->get('DatabaseDriver');
        $query = $db->getQuery(true);
        $user = $app->getIdentity();

        $query->select(
            $db->quoteName(
                explode(
                    ', ',
                    $this->getState(
                        'list.select',
                        'a.id, a.title, a.alias, a.published, a.access, a.created, a.created_by, a.ordering, a.language, ' .
                        'a.checked_out, a.checked_out_time, a.image, a.metadata, a.metatitle, a.metadesc, a.metakey'
                    )
                )
            )
        );

        $query->from($db->quoteName('#__easystore_collections', 'a'));

        $query->select($db->quoteName('l.title', 'language_title'))
            ->select($db->quoteName('l.image', 'language_image'))
            ->join(
                'LEFT',
                $db->quoteName('#__languages', 'l') . ' ON ' . $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('a.language')
            );

        // Join over the users for the checked out user.
        $query->select($db->quoteName('uc.name', 'editor'))
            ->join(
                'LEFT',
                $db->quoteName('#__users', 'uc') . ' ON ' . $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out')
            );

        $query->select($db->quoteName('ua.name', 'author_name'))
        ->join(
            'LEFT',
            $db->quoteName('#__users', 'ua') . ' ON ' . $db->quoteName('ua.id') . ' = ' . $db->quoteName('a.created_by')
        );

        // Join over the asset groups.
        $query->select($db->quoteName('ag.title', 'access_level'))
            ->join(
                'LEFT',
                $db->quoteName('#__viewlevels', 'ag') . ' ON ' . $db->quoteName('ag.id') . ' = ' . $db->quoteName('a.access')
            );

        // Filter by access level.
        if ($access = $this->getState('filter.access')) {
            $query->where($db->quoteName('a.access') . ' = :access');
            $query->bind(':access', $access, ParameterType::INTEGER);
        }

        // Implement View Level Access
        if (!$user->authorise('core.admin')) {
            $query->whereIn($db->quoteName('a.access'), $user->getAuthorisedViewLevels());
        }

        // Filter by published state
        $published = (string) $this->getState('filter.published');

        if (is_numeric($published)) {
            $query->where($db->quoteName('a.published') . ' = :published');
            $query->bind(':published', $published, ParameterType::INTEGER);
        } elseif ($published === '') {
            $query->where('(' . $db->quoteName('a.published') . ' = 0 OR ' . $db->quoteName('a.published') . ' = 1)');
        }

        // Filter by search in name.
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $search = substr($search, 3);
                $query->where($db->quoteName('a.id') . ' = :id');
                $query->bind(':id', $search, ParameterType::INTEGER);
            } else {
                $search = '%' . trim($search) . '%';
                $query->where(
                    '(' . $db->quoteName('a.title') . ' LIKE :title OR ' . $db->quoteName('a.alias') . ' LIKE :alias)'
                );
                $query->bind(':title', $search);
                $query->bind(':alias', $search);
            }
        }

        // Filter on the language.
        if ($language = $this->getState('filter.language')) {
            $query->where($db->quoteName('a.language') . ' = :language');
            $query->bind(':language', $language);
        }

        // Add product count of each collections
        $query->select('COUNT(DISTINCT p.id) AS product_count')
            ->leftJoin(
                $db->quoteName('#__easystore_collection_product_map', 'cpm') . ' ON ' . $db->quoteName('cpm.collection_id') . ' = ' . $db->quoteName('a.id')
            )
            ->leftJoin(
                $db->quoteName('#__easystore_products', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('cpm.product_id')
            )
            ->group($db->quoteName('a.id'));

        // Add the list ordering clause.
        $orderCol = $db->escape($this->state->get('list.ordering', 'a.ordering'));
        $orderDirn = $db->escape($this->state->get('list.direction', 'ASC'));

        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    /**
     * Method to get an array of data items.
     *
     * @return  array  An array of data items on success, false on failure.
     *
     * @since   1.4.0
     */
    public function getItems()
    {
        $items = parent::getItems();

        return Arr::make($items)->map(function ($item) {
            $item->image = !empty($item->image)
                ? Uri::root(true) . '/' . $item->image
                : Uri::root(true) . '/media/com_easystore/images/thumbnail.jpg';
            $item->url = Route::_('administrator/index.php?option=com_easystore&view=collection&id=' . $item->id, false);
            $item->product_count = $item->product_count ?? 0;
            return $item;
        });
    }
}
