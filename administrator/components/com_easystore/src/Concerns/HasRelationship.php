<?php

/**
 * @package     EasyStore.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Concerns;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Throwable;

/**
 * Trait HasRelationship
 * This trait provides methods for managing many-to-many entity relationships.
 *
 * @since 1.5.0
 */
trait HasRelationship
{
    /**
     * Get related entity records by entity ID.
     *
     * @param int $entityId The ID of the entity.
     * @param string $entityType The type of entity (e.g., 'product', 'brand', 'collection').
     * @param string $relatedType The type of related entity (e.g., 'brand', 'product', 'collection').
     * @param string $tableName The name of the mapping table.
     *
     * @return array An array of related entity IDs.
     *
     * @since 1.5.0
     */
    public function getRelatedRecords(int $entityId, string $entityType, string $relatedType, string $tableName)
    {
        $columnName = $entityType . '_id';
        return $this->getRecords($entityId, $columnName, $relatedType, $tableName);
    }

    /**
     * Get records based on a record ID and column name.
     *
     * @param int $recordId The ID of the record to query.
     * @param string $columnName The name of the column to use in the WHERE clause.
     * @param string $relatedType The type of related entity.
     * @param string $tableName The name of the mapping table.
     *
     * @return array An array of IDs.
     * @throws Throwable If there's an error executing the database query.
     *
     * @since 1.5.0
     */
    private function getRecords(int $recordId, string $columnName, string $relatedType, string $tableName)
    {
        $db           = Factory::getContainer()->get(DatabaseInterface::class);
        $selectClause = $db->quoteName($relatedType . '_id');

        $query = $db->getQuery(true)
            ->select($selectClause)
            ->from($db->quoteName($tableName))
            ->where($db->quoteName($columnName) . ' = :recordId');
        $query->bind(':recordId', $recordId, ParameterType::INTEGER);
        $db->setQuery($query);

        try {
            return $db->loadColumn() ?? [];
        } catch (Throwable $error) {
            return [];
        }
    }

    public function getRelatedIds(int $recordId, string $columnName, string $relatedType, string $tableName)
    {
        $db           = Factory::getContainer()->get(DatabaseInterface::class);
        $selectClause = $db->quoteName($relatedType);

        $query = $db->getQuery(true)
            ->select($selectClause)
            ->from($db->quoteName($tableName))
            ->where($db->quoteName($columnName) . ' = :recordId');
        $query->bind(':recordId', $recordId, ParameterType::INTEGER);
        $db->setQuery($query);

        try {
            return $db->loadColumn() ?? [];
        } catch (Throwable $error) {
            return [];
        }
    }

    /**
     * Delete a record from the mapping table based on the given record ID and column name.
     *
     * @param int $recordId The ID of the record to delete.
     * @param string $columnName The name of the column to use in the WHERE clause.
     * @param string $tableName The name of the mapping table.
     *
     * @return bool True if the deletion was successful, false otherwise.
     * @throws Throwable
     *
     * @since 1.5.0
     */
    private function deleteRecord(int $recordId, string $columnName, string $tableName)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->delete($tableName)
            ->where($db->quoteName($columnName) . ' = :recordId');
        $query->bind(':recordId', $recordId, ParameterType::INTEGER);
        $db->setQuery($query);

        try {
            $db->execute();
        } catch (Throwable $error) {
            throw $error;
        }

        return true;
    }

    /**
     * Create an array of entity pairs for a given entity ID and related entities array.
     *
     * @param int $entityId The ID of the entity.
     * @param array $relatedIds An array of related entity IDs.
     * @param bool $entityFirst Whether the entity ID should be the first element in each pair.
     *
     * @return array An array of entity pairs.
     *
     * @since 1.5.0
     */
    private function makeRelationshipData(int $entityId, array $relatedIds, bool $entityFirst = false)
    {
        if (empty($relatedIds)) {
            return [];
        }

        return array_map(function ($relatedId) use ($entityId, $entityFirst) {
            return $entityFirst ? [$entityId, $relatedId] : [$relatedId, $entityId];
        }, $relatedIds);
    }

    /**
     * Prepare an array of values for insertion into the database.
     *
     * @param array $values An array of arrays containing values to be inserted.
     *
     * @return string A string of comma-separated values ready for SQL insertion.
     *
     * @since 1.5.0
     */
    private function prepareInsertValues(array $values)
    {
        if (empty($values)) {
            return '';
        }

        $values = array_map(function ($value) {
            return implode(', ', $value);
        }, $values);

        return implode('), (', $values);
    }

    /**
     * Store relationship records in the database.
     *
     * @param string $values A string of comma-separated values ready for SQL insertion.
     * @param string $tableName The name of the mapping table.
     * @param string $firstColumn The name of the first column.
     * @param string $secondColumn The name of the second column.
     *
     * @return bool True if the insertion was successful, false otherwise.
     * @throws Throwable
     *
     * @since 1.5.0
     */
    private function storeRelationshipRecord(string $values, string $tableName, string $firstColumn, string $secondColumn)
    {
        if (empty($values)) {
            return false;
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->insert($tableName)
            ->columns([$db->quoteName($firstColumn), $db->quoteName($secondColumn)])
            ->values($values);
        $db->setQuery($query);

        try {
            $db->execute();
        } catch (Throwable $error) {
            throw $error;
        }

        return true;
    }

    /**
     * Store entity relationships.
     *
     * @param int $entityId The ID of the entity.
     * @param array $relatedIds An array of related entity IDs.
     * @param string $entityType The type of entity (e.g., 'product', 'brand', 'collection').
     * @param string $relatedType The type of related entity (e.g., 'brand', 'product', 'collection').
     * @param string $tableName The name of the mapping table.
     * @param bool $entityFirst Whether the entity ID should be the first element in each pair.
     *
     * @return bool True if the operation was successful, false otherwise.
     *
     * @since 1.5.0
     */
    public function storeRelationships(
        int $entityId,
        array $relatedIds,
        string $entityType,
        string $relatedType,
        string $tableName,
        bool $entityFirst = false
    ) {
        $columnName = $entityType . '_id';
        $this->deleteRecord($entityId, $columnName, $tableName);

        $values = $this->prepareInsertValues(
            $this->makeRelationshipData($entityId, $relatedIds, $entityFirst)
        );

        $firstColumn  = $entityFirst ? $entityType . '_id' : $relatedType . '_id';
        $secondColumn = $entityFirst ? $relatedType . '_id' : $entityType . '_id';

        return $this->storeRelationshipRecord($values, $tableName, $firstColumn, $secondColumn);
    }

    /**
     * Get detailed product information for a list of product IDs.
     *
     * @param array $productIds An array of product IDs.
     *
     * @return array An array of product objects with id, title, and image.
     *
     * @since 1.5.0
     */
    public function getProductDetails(array $productIds)
    {
        if (empty($productIds)) {
            return [];
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('p.id, p.title, p.published, media.src as image')
            ->from($db->quoteName('#__easystore_products', 'p'))
            ->join('LEFT', $db->quoteName('#__easystore_media', 'media') . ' ON (' . $db->quoteName('media.product_id') . ' = ' . $db->quoteName('p.id') . ' AND ' . $db->quoteName('media.is_featured') . ' = 1' . ')')
            ->where($db->quoteName('p.id') . ' IN (' . implode(',', $productIds) . ')')
            ->group($db->quoteName('p.id'));

        $db->setQuery($query);

        try {
            $products = $db->loadObjectList();

            if (empty($products)) {
                return [];
            }

            $fallbackImage = Uri::root(true) . '/media/com_easystore/images/thumbnail.jpg';

            return array_map(function ($product) use ($fallbackImage) {
                $product->image = !empty($product->image)
                ? Uri::root(true) . '/' . $product->image
                : $fallbackImage;
                $product->url = Route::_('index.php?option=com_easystore&view=product&layout=edit&id=' . $product->id, false);

                return $product;
            }, $products);
        } catch (Throwable $error) {
            return [];
        }
    }

    /**
     * Save related IDs to the database.
     *
     * @param int $parentId The ID of the parent record
     * @param array $newIds Array of IDs to relate
     * @param string $relationColumn Name of the relation column
     * @param string $idColumn Name of the primary ID column
     * @param string $tableName Database table name
     *
     * @return bool Success status
     *
     * @since 1.5.0
     */
    public function saveRelatedIds(
        int $parentId,
        array $newIds,
        string $relationColumn,
        string $idColumn,
        string $tableName
    ): bool {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            // Get current related IDs
            $currentIds = $this->fetchCurrentRelations(
                $db,
                $tableName,
                $relationColumn,
                $parentId,
                $idColumn
            );

            // Calculate differences
            $idsToAdd    = array_diff($newIds, $currentIds);
            $idsToRemove = array_diff($currentIds, $newIds);

            // Update relations
            if (!empty($idsToAdd)) {
                $this->addRelations(
                    $db,
                    $tableName,
                    $relationColumn,
                    $parentId,
                    $idsToAdd,
                    $idColumn
                );
            }

            if (!empty($idsToRemove)) {
                $this->removeRelations(
                    $db,
                    $tableName,
                    $relationColumn,
                    $idsToRemove,
                    $idColumn
                );
            }

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Fetch current relation IDs
     *
     * @param DatabaseInterface $db
     * @param string $table
     * @param string $relationColumn
     * @param int $parentId
     * @param string $idColumn
     *
     * @return array
     *
     * @since 1.5.0
     */
    private function fetchCurrentRelations(
        DatabaseInterface $db,
        string $table,
        string $relationColumn,
        int $parentId,
        string $idColumn
    ): array {
        $query = $db->getQuery(true)
            ->select($db->quoteName($idColumn))
            ->from($db->quoteName($table))
            ->where($db->quoteName($relationColumn) . ' = :parentId')
            ->bind(':parentId', $parentId, ParameterType::INTEGER);

        return $db->setQuery($query)->loadColumn() ?? [];
    }

    /**
     * Add new relations
     *
     * @param DatabaseInterface $db
     * @param string $table
     * @param string $relationColumn
     * @param int $parentId
     * @param array $ids
     * @param string $idColumn
     *
     * @return void
     *
     * @since 1.5.0
     */
    private function addRelations(
        DatabaseInterface $db,
        string $table,
        string $relationColumn,
        int $parentId,
        array $ids,
        string $idColumn
    ): void {
        $query = $db->getQuery(true)
            ->update($db->quoteName($table))
            ->set($db->quoteName($relationColumn) . ' = :parentId')
            ->where($db->quoteName($idColumn) . ' IN (' . implode(',', $ids) . ')')
            ->bind(':parentId', $parentId, ParameterType::INTEGER);

        $db->setQuery($query)->execute();
    }

    /**
     * Remove relations
     *
     * @param DatabaseInterface $db
     * @param string $table
     * @param string $relationColumn
     * @param array $ids
     * @param string $idColumn
     *
     * @return void
     *
     * @since 1.5.0
     */
    private function removeRelations(
        DatabaseInterface $db,
        string $table,
        string $relationColumn,
        array $ids,
        string $idColumn
    ): void {
        $query = $db->getQuery(true)
            ->update($db->quoteName($table))
            ->set($db->quoteName($relationColumn) . ' = NULL')
            ->where($db->quoteName($idColumn) . ' IN (' . implode(',', $ids) . ')');

        $db->setQuery($query)->execute();
    }
}
