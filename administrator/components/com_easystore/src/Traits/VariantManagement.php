<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Traits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;

trait VariantManagement
{
    /**
     * Manage saving the product options (add/update/delete) from one place.
     *
     * @param   array       $productOption  The product options array
     * @param   int     $productId      The product ID
     *
     * @return  void
     */
    protected function manageProductOptions(array $productOption, int $productId)
    {
        $removedIds = $this->deleteRemovableRecords($productOption, $productId, '#__easystore_product_options', 'id', 'product_id');

        $ordering = 0;

        foreach ($productOption as $option) {
            if (\is_string($option)) {
                $option = \json_decode($option);
            }

            if (!empty($option->id) && \in_array($option->id, $removedIds)) {
                continue;
            }

            if (!empty($option->isLatest)) {
                continue;
            }

            if (!empty($option)) {
                $optionData = (object) [
                    'id'         => $option->id ?? null,
                    'product_id' => $productId,
                    'name'       => $option->name,
                    'type'       => $option->type,
                    'ordering'   => ++$ordering,
                ];

                $result = EasyStoreDatabaseOrm::updateOrCreate('#__easystore_product_options', $optionData, 'id');

                if (!empty($result) && !empty($option->values)) {
                    $removedValueIds = $this->deleteRemovableRecords($option->values, $result->id, '#__easystore_product_option_values', 'id', 'option_id');

                    foreach ($option->values as $index => $value) {
                        if (!empty($value->id) && \in_array($value->id, $removedValueIds)) {
                            continue;
                        }

                        if (!empty($value->isLatest)) {
                            continue;
                        }

                        $optionValueId = !empty($value->is_new) ? null : $value->id ?? null;

                        $optionValueData = (object) [
                            'id'         => $optionValueId,
                            'product_id' => $productId,
                            'option_id'  => $result->id,
                            'name'       => $value->name,
                            'color'      => !empty($value->color) ? $value->color : '',
                            'ordering'   => $index + 1,
                        ];

                        EasyStoreDatabaseOrm::updateOrCreate('#__easystore_product_option_values', $optionValueData, 'id');
                    }
                }
            }
        }
    }

    private function manageProductVariants(array $variants, int $productId, array $imageIdMapping)
    {
        if (empty($productId)) {
            return;
        }

        $ids = array_map(function ($variant) {
            return $variant->id ?? null;
        }, $variants);

        $ids = array_filter($ids, function ($id) {
            return !empty($id);
        });

        $removedIds = $this->deleteRemovableRecords($variants, $productId, '#__easystore_product_skus', 'id', 'product_id');
        $ordering   = 0;

        foreach ($variants as $variant) {
            if (\is_string($variant)) {
                $variant = \json_decode($variant);
            }

            if (!empty($variant->id) && \in_array($variant->id, $removedIds)) {
                continue;
            }

            $imageId = null;

            if (!empty($variant->image->id)) {
                if (!empty($imageIdMapping)) {
                    $imageId = $imageIdMapping[$variant->image->id];
                } else {
                    $imageId = $variant->image->id;
                }
            }

            $payload = (object) [
                'id'                => $variant->id ?? null,
                'product_id'        => $productId,
                'combination_name'  => $variant->combination->name,
                'combination_value' => $this->reorderCombinationValue($variant->combination->value),
                'image_id'          => $imageId,
                'price'             => $this->formatToNumeric($variant->price, 'decimal'),
                'inventory_status'  => !empty($variant->inventory_status) ? 1 : 0,
                'inventory_amount'  => $this->formatToNumeric($variant->inventory_amount),
                'visibility'        => !empty($variant->visibility) ? 1 : 0,
                'is_taxable'        => !empty($variant->is_taxable) ? 1 : 0,
                'sku'               => $variant->sku,
                'weight'            => $variant->weight,
                'unit'              => $variant->unit,
                'ordering'          => ++$ordering,
                'modified'          => Factory::getDate()->toSql(),
            ];

            if (!$variant->id) {
                $payload->created = Factory::getDate()->toSql();
            }

            EasyStoreDatabaseOrm::updateOrCreate('#__easystore_product_skus', $payload, 'id');
        }
    }

    /**
     * Remove the removed records detecting from the payload data. After finding out the removable records
     * delete them and return the deleted ids.
     *
     * @param   array       $data           The payload data.
     * @param   int     $foreignId      The foreign id by which we will detect the records from the database table.
     * @param   string      $table          The table name.
     * @param   string      $pk             The primary key column name.
     * @param   string      $foreignKey     The foreign key column name.
     *
     * @return  array       The removed `$pk`s.
     */
    protected function deleteRemovableRecords(array $data, int $foreignId, string $table, string $pk, string $foreignKey)
    {
        $ids = array_map(function ($item) use ($pk) {
            if (is_string($item)) {
                $item = json_decode($item);
            }

            return $item->$pk ?? null;
        }, $data);

        $ids = array_filter($ids, function ($id) {
            return !empty($id);
        });

        $ids = array_values($ids);

        $changes = static::detectPivotTableChanges($ids, $foreignId, $table, $foreignKey, $pk);

        if (!empty($changes->removable)) {
            EasyStoreDatabaseOrm::removeByIds($table, $changes->removable);
        }

        return $changes->removable;
    }

    /**
     * Detect the changes happen to the pivot table data. We may add or remove multiple items
     * to the pivot table without any trace, so by using this function we could detect the
     * removable items and the new entries.
     *
     * @param   array       $payload        The payload data by which we detect the removable and the new entries.
     * @param   string      $table          The pivot table name.
     * @param   int     $foreignId      One of the key column by which we get all the records.
     * @param   string      $foreignKey     The foreign key name.
     * @param   string      $referenceKey   The reference key name, by which we find out the removable and new entries.
     * @param   array       $extraCondition Extra conditions in array.
     *
     * @return  object      Return an object containing the removable and newEntry data array.
     */
    protected static function detectPivotTableChanges(array $payload, int $foreignId, string $table, string $foreignKey, string $referenceKey, array $extraCondition = [])
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
        } catch (\Throwable $error) {
            throw $error;
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

    protected function reorderCombinationValue($combination)
    {
        if (empty($combination)) {
            return $combination;
        }

        $combinationArray = explode(';', $combination);
        $combinationArray = array_filter($combinationArray, function ($item) {
            return !empty($item);
        });
        $combinationArray = array_values($combinationArray);

        natcasesort($combinationArray);

        return implode(';', $combinationArray);
    }

    /**
     * Function to format value to Numeric
     *
     * @param string $value
     * @param string $type
     * @param float $default
     * @return number
     */
    protected function formatToNumeric($value, $type = 'int', $default = 0)
    {
        if ($type === 'int') {
            return (!empty($value) && is_numeric($value)) ? (int) $value : $default;
        } elseif ($type === 'decimal') {
            return (!empty($value) && is_numeric($value)) ? number_format((float) $value, 2, '.', '') : number_format((float) $default, 2, '.', '');
        } else {
            return $default;
        }
    }

    /**
     * Function to Copy variant data to new table for Save 2 Copy
     *
     * @param int $origTableId
     * @param int $newTableId
     * @return bool
     */
    protected function copyOptionsAndVariants($origTableId, $newTableId)
    {
        $db           = Factory::getContainer()->get(DatabaseInterface::class);
        $options      = EasyStoreDatabaseOrm::get('#__easystore_product_options', 'product_id', $origTableId)->loadObjectList();
        $optionValues = EasyStoreDatabaseOrm::get('#__easystore_product_option_values', 'product_id', $origTableId)->loadObjectList();
        $skus         = EasyStoreDatabaseOrm::get('#__easystore_product_skus', 'product_id', $origTableId)->loadObjectList();

        foreach ($options as $option) {
            $previousOptionId = $option->id;
            unset($option->id);
            $option->product_id = $newTableId;
            $db->insertObject('#__easystore_product_options', $option, 'id');
            $newOptionId = $option->id;

            foreach ($optionValues as $optionValue) {
                if ($optionValue->option_id == $previousOptionId) {
                    unset($optionValue->id);
                    $optionValue->product_id = $newTableId;
                    $optionValue->option_id  = $newOptionId;
                    $db->insertObject('#__easystore_product_option_values', $optionValue);
                }
            }
        }

        foreach ($skus as $sku) {
            unset($sku->id);
            $sku->product_id = $newTableId;
            $db->insertObject('#__easystore_product_skus', $sku);
        }
    }
}
