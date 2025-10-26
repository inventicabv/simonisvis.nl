<?php
/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2024 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Site\Model;

\defined('_JEXEC') or die('Restricted Direct Access!');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use JoomShaper\Component\EasyStore\Administrator\Constants\ProductListSource;
use JoomShaper\Component\EasyStore\Site\Model\ProductsModel;

/**
 * Model class for Collection view.
 *
 * @since  1.4.0
 */
class CollectionModel extends ItemModel
{
    /**
     * Model context string.
     *
     * @var        string
     */
    protected $_context = 'com_easystore.collection';

    /**
     * Method to auto-populate the model state.
     *
     * Collection. Calling getState in this method will result in recursion.
     *
     * @since   1.4.0
     *
     * @return void
     */
    protected function populateState()
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();

        // Load state from the request.
        $pk = $app->input->getInt('id');
        $this->setState('collection.id', $pk);

        $offset = $app->input->getUInt('limitstart');
        $this->setState('list.offset', $offset);

        // Load the parameters.
        $params = $app->getParams();
        $this->setState('params', $params);

        $this->setState('filter.language', Multilanguage::isEnabled());
    }

    /**
     * Method to get collection data.
     *
     * @param   integer  $pk  The id of the collection.
     *
     * @return  object|boolean  Menu item data object on success, boolean false
     *                          on failure.
     *
     * @since   1.4.0
     */
    public function getItem($pk = null)
    {
        $pk = (int) ($pk ?: $this->getState('collection.id'));

        if (!$pk) {
            return [];
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__easystore_collections')
            ->where($db->quoteName('id') . ' = :pk')
            ->bind(':pk', $pk, ParameterType::INTEGER);

        $db->setQuery($query);
        $item = $db->loadObject();

        if (!$item) {
            return [];
        }

        $productsModel = new ProductsModel();
        $productsModel->setState(
            'attr',
            array_merge(
                $productsModel->getState('attr', []),
                [
                    'source'        => ProductListSource::COLLECTION,
                    'collection_id' => $pk,
                ]
            )
        );

        $item->products = $productsModel->getItems();

        return $item;
    }

    /**
     * Get the pagination object from the products model
     *
     * @return object The pagination object
     *
     * @since 1.4.0
     */
    public function getPagination()
    {
        $productsModel = new ProductsModel();
        return $productsModel->getPagination();
    }
}
