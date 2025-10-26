<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Helper;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use JoomShaper\Component\EasyStore\Administrator\Supports\Arr;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper class for managing collections in the EasyStore component.
 *
 * This class provides methods to retrieve and manipulate collection data.
 *
 * @since 1.4.0
 */
final class CollectionHelper
{
    /**
     * Get collection data for the current request.
     *
     * This method retrieves collection data based on the current request parameters.
     * It handles requests from both the EasyStore component and SP Page Builder.
     *
     * @since 1.4.0
     *
     * @return object|null The collection data object or null if not applicable.
     */
    public static function getCollectionData()
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $input = $app->input;
        $option = $input->get('option', '', 'string');
        $view = $input->get('view', '', 'string');
        $collectionId = $input->get('id', 0, 'int');

        $views = ['ajax', 'page'];

        // Check if the request is from SP Page Builder frontend editor, then return a mock collection data.
        if ($option === 'com_sppagebuilder' && in_array($view, $views)) {
            return (object) [
                'title' => 'Summer Essentials Collection',
                'description' => 'Discover our curated Summer Essentials Collection, featuring must-have items for the season. From breezy outfits to beach accessories, find everything you need for a stylish and comfortable summer.',
                'image' => Uri::root(true) . '/media/com_easystore/images/thumbnail.jpg',
            ];
        }

        if ($option !== 'com_easystore' || $view !== 'collection' || !$collectionId) {
            return null;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('*')
            ->from('#__easystore_collections')
            ->where($db->quoteName('id') . ' = :collectionId');

        $query->bind(':collectionId', $collectionId, ParameterType::INTEGER);
        $db->setQuery($query);
        $collection = $db->loadObject();

        if (!empty($collection->image)) {
            $collection->image = Uri::root(true) . '/' . $collection->image;
        }

        return $collection;
    }

    /**
     * Get collections as options for a select list.
     *
     * This method retrieves all collections and returns them as an array of objects,
     * each containing an 'id' and 'title' property.
     *
     * @since 1.4.0
     *
     * @return array An array of objects with 'id' and 'title' properties.
     */
    public static function getCollectionsAsOptions()
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('id', 'title')));
		$query->from($db->quoteName('#__easystore_collections'));
		$db->setQuery($query);

		try
		{
			$results = $db->loadObjectList() ?? [];
		}
		catch (Exception $_error)
		{
			return [];
		}

        return Arr::make($results)->map(function ($item) {
            return (object) ['value' => $item->id, 'label' => $item->title];
        });
	}

     /**
     * Get the products of a collection
     *
     * @param int $collectionId The collection id
     *
     * @return array The product ids
     *
     * @since 1.4.0
     */
    public static function getCollectionProducts($collectionId)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('product_id')
            ->from($db->quoteName('#__easystore_collection_product_map'))
            ->where($db->quoteName('collection_id') . ' = :collection_id')
            ->bind(':collection_id', $collectionId, ParameterType::INTEGER);

        $db->setQuery($query);
        $productIds = $db->loadColumn();

        return $productIds ?? [];
    }
}
