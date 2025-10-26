<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * EasyStore database ORM class.
 * This class contains some basic functionalities of managing
 * one-to-one, one-to-many, and many-to-many relationships.
 */

class EasyStoreDatabaseHelper
{
    /**
     * Detect the changes happen to the pivot table data. We may add or remove multiple items
     * to the pivot table without any trace, so by using this function we could detect the
     * removable items and the new entries.
     *
     * @param   array       $payload        The payload data by which we detect the removable and the new entries.
     * @param   string      $table          The pivot table name.
     * @param   int         $foreignId      One of the key column by which we get all the records.
     * @param   string      $foreignKey     The foreign key name.
     * @param   string      $referenceKey   The reference key name, by which we find out the removable and new entries.
     * @param   array       $extraCondition Extra conditions in array.
     *
     * @return  object      Return an object containing the removable and newEntry data array.
     */
    public static function detectPivotTableChanges(array $payload, int $foreignId, string $table, string $foreignKey, string $referenceKey, array $extraCondition = [])
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select($referenceKey)
            ->from($db->quoteName($table))
            ->where($db->quoteName($foreignKey) . ' = ' . $db->quote($foreignId));

        if (!empty($extraCondition)) {
            foreach ($extraCondition as $condition) {
                $query->where($db->quoteName($condition['key']) . ' ' . $condition['operator'] . ' ' . $db->quote($condition['value']));
            }
        }

        $db->setQuery($query);

        $records = [];

        try {
            $records = $db->loadColumn();
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }

        $removable  = [];
        $newEntries = [];

        $intersect = array_intersect($records, $payload);

        $removable = array_filter($records, function ($record) use ($intersect) {
            return !\in_array($record, $intersect);
        });

        $newEntries = array_filter($payload, function ($item) use ($intersect) {
            return !\in_array($item, $intersect);
        });

        return (object) [
            'removable'  => $removable,
            'newEntries' => $newEntries,
        ];
    }
}
