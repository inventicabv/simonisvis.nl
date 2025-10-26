<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Filesystem\Path;

/**
 * Media Model class
 *
 * @since 1.0.0
 */
class MediaModel extends BaseDatabaseModel
{
    /**
     * Table columns for the both images and temp_images tables.
     *
     * @var array
     */
    private $columns = [];

    public function __construct()
    {
        $this->columns = [
            'id',
            'product_id',
            'client_id',
            'name',
            'type',
            'is_featured',
            'width',
            'height',
            'src',
            'alt_text',
            'ordering',
            'created',
            'created_by',
            'modified_by',
        ];
    }

    /**
     * Get the columns after excluding a list of columns.
     *
     * @param   array   $excludes   The excluding columns.
     *
     * @return  array   The column list after exclusion.
     * @since   4.1.0
     */
    private function getColumns(array $excludes = []): array
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $columnsAfterExclusion = array_filter($this->columns, function ($column) use ($excludes) {
            return !\in_array($column, $excludes);
        });

        return array_map(function ($column) use ($db) {
            return $db->quoteName($column);
        }, array_values($columnsAfterExclusion));
    }

    /**
     * Get temporary or saved images.
     *
     * @param string|int|null   $productOrClientId
     * @param bool           $isTemporary
     *
     * @return  void
     * @since   4.1.0
     */
    public function getImages($productOrClientId, bool $isTemporary = false)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $tableName      = $isTemporary ? '#__easystore_temp_media' : '#__easystore_media';
        $excludeColumns = ['width', 'height', 'created', 'created_by', 'modified_by'];

        if ($isTemporary) {
            $excludeColumns[] = 'product_id';
        } else {
            $excludeColumns[] = 'client_id';
        }


        $query->select(implode(',', $this->getColumns($excludeColumns)))
            ->from($db->quoteName($tableName))
            ->where($db->quoteName($isTemporary ? 'client_id' : 'product_id') . ' = ' . $db->quote($productOrClientId))
            ->order($db->quoteName('ordering') . ' ASC');

        $db->setQuery($query);

        try {
            $media = $db->loadObjectList();

            if (!empty($media)) {
                foreach ($media as &$mediaItem) {
                    $mediaItem->src = Uri::root(true) . '/' . Path::clean($mediaItem->src);
                }

                unset($mediaItem);
            }

            return $media;
        } catch (Exception $error) {
            return [];
        }
    }

    /**
     * Store the images to the database.
     *
     * @param   array   $images         The images data array.
     * @param   bool    $isTemporary    Check if the image stored into the temporary table or permanent one.
     *
     * @return  void
     * @since   4.1.0
     */
    public function store(array $images, bool $isTemporary = false)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $tableName      = $isTemporary ? '#__easystore_temp_media' : '#__easystore_media';
        $excludeColumns = $isTemporary ? ['id', 'product_id'] : ['id', 'client_id'];
        $columns        = $this->getColumns($excludeColumns);


        $query->insert($db->quoteName($tableName))->columns($columns);

        foreach ($images as $image) {
            $productOrClientValue = $isTemporary ? $image->client_id : $image->product_id;

            $item = [
                $db->quote($productOrClientValue),
                $db->quote($image->name),
                $db->quote($image->type),
                $db->quote($image->is_featured),
                $db->quote($image->width),
                $db->quote($image->height),
                $db->quote($image->src),
                $db->quote($image->alt_text),
                $db->quote($image->ordering),
                $db->quote(Factory::getDate('now')),
                $db->quote(Factory::getApplication()->getIdentity()->id),
                $db->quote(Factory::getApplication()->getIdentity()->id),
            ];

            $query->values(implode(',', $item));
        }

        $db->setQuery($query);

        try {
            $db->execute();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Check if a product has featured image or not.
     *
     * @param   mixed   $productOrClientId      The product or client ID.
     * @param   bool    $isTemporary            Where we need to check into temporary table or the permanent table.
     *
     * @return  bool true if product has featured image, false otherwise.
     * @since   4.1.0
     */
    public function hasFeaturedImage($productOrClientId, bool $isTemporary = false): bool
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $tableName  = $isTemporary ? '#__easystore_temp_media' : '#__easystore_media';
        $columnName = $isTemporary ? 'client_id' : 'product_id';

        $query->select('COUNT(id)')
            ->from($db->quoteName($tableName))
            ->where($db->quoteName($columnName) . ' = ' . $db->quote($productOrClientId))
            ->where($db->quoteName('is_featured') . ' = 1');

        $db->setQuery($query);

        try {
            return $db->loadResult() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the maximum ordering value of a specific product images.
     *
     * @param   mixed       $productOrClientId  The product or client ID.
     * @param   bool        $isTemporary        Where we need to check into temporary table or the permanent table.
     *
     * @return  int     The maximum ordering value.
     * @since   4.1.0
     */
    public function getMaximumOrdering($productOrClientId, bool $isTemporary = false): int
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $tableName  = $isTemporary ? '#__easystore_temp_media' : '#__easystore_media';
        $columnName = $isTemporary ? 'client_id' : 'product_id';

        $query->select('MAX(ordering)')
            ->from($db->quoteName($tableName))
            ->where($db->quoteName($columnName) . ' = ' . $db->quote($productOrClientId));

        $db->setQuery($query);

        try {
            return $db->loadResult() ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Clear the temporary images by client id.
     *
     * @param   string      $clientId  The client ID.
     *
     * @return  bool     True after successfully clearing, false otherwise.
     * @since   4.1.0
     */
    public function clearTemporaryImages(string $clientId): bool
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->delete('#__easystore_temp_media')
            ->where($db->quoteName('client_id') . ' = ' . $db->quote($clientId));

        $db->setQuery($query);

        try {
            $db->execute();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete product image form database
     *
     * @param array      $imageId       Image Id's
     * @param bool    $isTemporary   True after successfully clearing, false otherwise.
     * @return bool
     */
    public function deleteImages($imageId = [], bool $isTemporary = false): bool
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $table = $isTemporary ? '#__easystore_temp_media' : '#__easystore_media';

        $query->delete($table)
            ->where($db->quoteName('id') . ' IN (' . implode(',', $imageId) . ')');

        $db->setQuery($query);

        try {
            $db->execute();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update Image ordering
     *
     * @param object $imageInfo
     * @param bool $isTemporary
     * @return bool
     */
    public function updateImageOrdering(object $imageInfo, bool $isTemporary = false)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $fields = [
            $db->quoteName('id') . ' = ' . $db->quote($imageInfo->id),
            $db->quoteName('is_featured') . ' = ' . $db->quote($imageInfo->is_featured),
            $db->quoteName('ordering') . ' = ' . $db->quote($imageInfo->ordering),
        ];

        if (!$isTemporary) {
            $queryTable  = '#__easystore_media';
            $queryColumn = 'product_id';
            $queryValue  = $imageInfo->product_id;
        } else {
            $queryTable  = '#__easystore_temp_media';
            $queryColumn = 'client_id';
            $queryValue  = $imageInfo->client_id;
        }

        $query->update($db->quoteName($queryTable))
            ->set($fields)
            ->where($db->quoteName('id') . ' = ' . $db->quote($imageInfo->id))
            ->where($db->quoteName($queryColumn) . ' = ' . $db->quote($queryValue));

        $db->setQuery($query);

        try {
            $db->execute();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Function to refresh Featured image
     *
     * @param mixed $id
     * @param bool $isTemporary
     * @return bool
     */
    public function refreshFeaturedImage($id, bool $isTemporary)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        if (!$isTemporary) {
            $queryTable  = '#__easystore_media';
            $queryColumn = 'product_id';
        } else {
            $queryTable  = '#__easystore_temp_media';
            $queryColumn = 'client_id';
        }

        $query->select($db->quoteName('id'))
            ->from($db->quoteName($queryTable))
            ->where($db->quoteName($queryColumn) . ' = ' . $db->quote($id))
            ->order($db->quoteName('ordering') . ' ASC');

        $db->setQuery($query);

        $toBeFeatured = $db->loadObject();

        if (empty($toBeFeatured)) {
            return false;
        }

        $updateFeaturedQuery = $db->getQuery(true);

        $fields = [
            $db->quoteName('is_featured') . ' = IF(' . $db->quoteName('id') . ' = ' . $toBeFeatured->id . ', 1, 0)',
        ];

        $conditions = [$db->quoteName($queryColumn) . ' = ' . $db->quote($id)];
        $updateFeaturedQuery->update($db->quoteName($queryTable))->set($fields)->where($conditions);
        $db->setQuery($updateFeaturedQuery);

        try {
            $db->execute();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
