<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * EasyStore Product helper.
 *
 * @since  1.0.0
 */
class EasyStoreProductHelper
{
    /**
     * Get all variants for a product
     *
     * @param int $productId    Product Id
     * @return mixed
     */
    public static function getProductVariantById(int $productId)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select(
                [
                    $db->quoteName('id'),
                    $db->quoteName('name'),
                ]
            )
            ->from($db->quoteName('#__easystore_product_variants'))
            ->where($db->quoteName('product_id') . ' = ' . $db->quote($productId))
            ->order($db->quoteName('ordering'));

        $db->setQuery($query);

        try {
            $variant_list   =  $db->loadAssocList();
            $variantSubForm = [];
            $i              = 0;

            if (!empty($variant_list)) {
                foreach ($variant_list as $k => $v) {
                    $variantSubForm['variant_list' . $i]['id']       = (string)$v['id'];
                    $variantSubForm['variant_list' . $i]['name']     = (string)$v['name'];
                    $i++;
                }
            }

            return $variantSubForm;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Get all variation options values
     *
     * @param int $variantId    Variant Id
     * @return mixed
     */
    public static function getProductVariantOptionsById(int $variantId)
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $query = $db->getQuery(true)
                ->select(
                    [
                        $db->quoteName('pvo.id'),
                        $db->quoteName('pvo.product_variant_id'),
                        $db->quoteName('pvo.value'),
                        $db->quoteName('pvo.code'),
                        $db->quoteName('pvo.is_color'),
                        $db->quoteName('pvo.image_id'),
                    ]
                )
                ->from($db->quoteName('#__easystore_product_variant_options', 'pvo'))
                ->where($db->quoteName('pvo.product_variant_id') . ' = ' . $db->quote($variantId))
                ->order($db->quoteName('pvo.ordering'));

            $db->setQuery($query);
            $options = $db->loadAssocList();

            $optionsSubform = [];

            foreach ($options as $i => $option) {
                $optionsSubform['values' . $i] = [
                    'id'                 => (int) $option['id'],
                    'product_variant_id' => (int) $option['product_variant_id'],
                    'value'              => (string) $option['value'],
                    'is_color'           => (string) $option['is_color'],
                    'code'               => (string) $option['code'],
                    'image_id'           => $option['image_id'],
                ];
            }

            return $optionsSubform;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Summary of insertProductVariant
     * @param int $productId
     * @param mixed $variants
     * @return mixed
     */
    public static function insertProductVariant(int $productId, $variants)
    {
        $variants = json_decode(json_encode($variants));

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        // Check no variation data
        if (empty($variants)) {
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__easystore_product_variants'))
                ->where($db->quoteName('product_id') . ' = :productId')
                ->bind(':productId', $productId);

            $db->setQuery($query)->execute();

            return [];
        }


        // Add or update new variant with options
        try {
            $order      = 1;
            $isExistsId = 0;

            foreach ($variants as $key => $variant) {
                $variantData             = new \stdClass();
                $variantData->product_id = $productId;
                $variantData->id         = $variant->id;
                $variantData->name       = $variant->name;
                $variantData->ordering   = $order;

                if (isset($variant->id) && $variant->id > 0) {
                    $isExistsId = self::isExists('#__easystore_product_variants', 'id', $variant->id);

                    if ($isExistsId > 0) {
                        $db->updateObject('#__easystore_product_variants', $variantData, 'id');

                        // Check variant values
                        if (empty($variant->values)) {
                            $query = $db->getQuery(true)
                                ->delete($db->quoteName('#__easystore_product_variant_options'))
                                ->where($db->quoteName('product_variant_id') . ' = :variantId')
                                ->bind(':variantId', $variant->id);

                            $db->setQuery($query)->execute();

                            return [];
                        }

                        if (!empty($variant->values)) {
                            $productVariantId = $variant->id;

                            array_walk(
                                $variant->values,
                                function ($value) use ($productVariantId) {
                                    $value->product_variant_id = $productVariantId;

                                    $db               = Factory::getContainer()->get(DatabaseInterface::class);
                                    $isExistsOptionId = self::isExists('#__easystore_product_variant_options', 'id', $value->id);

                                    if ($value->id > 0) {
                                        if ($isExistsOptionId > 0) {
                                            $db->updateObject('#__easystore_product_variant_options', $value, 'id');
                                        }
                                    } else {
                                        $db->insertObject('#__easystore_product_variant_options', $value);
                                    }
                                }
                            );
                        }
                    }
                } else {
                    $db->insertObject('#__easystore_product_variants', $variantData);
                    $variantId  = $db->insertid();
                    $isExistsId = self::isExists('#__easystore_product_variants', 'id', $variantId);

                    if (empty($isExistsId)) {
                        if (!empty($variant->values)) {
                            array_walk(
                                $variant->values,
                                function ($value) use ($variantId) {
                                    $value->product_variant_id = $variantId;

                                    $db = Factory::getContainer()->get(DatabaseInterface::class);
                                    $db->insertObject('#__easystore_product_variant_options', $value);
                                }
                            );
                        }
                    }
                }
                $order++;
            }
        } catch (\Throwable $th) {
            throw $th;
        }

        return $variantData;
    }


    private static function isExists(string $table, string $fieldName, int $fieldValue)
    {
        $db     = Factory::getContainer()->get(DatabaseInterface::class);
        $query  = $db->getQuery(true)
            ->select([$db->quoteName($fieldName)])
            ->from($db->quoteName($table))
            ->where($db->quoteName($fieldName) . ' = ' . $db->quote($fieldValue))
            ->order($db->quoteName($fieldName));

        $db->setQuery($query);

        return $db->loadResult();
    }

    public static function generateIds($values)
    {
        if (!empty($values)) {
            $counter = 0;
            array_walk_recursive($values, function (&$value, $key) use (&$counter) {
                if ($key === 'id') {
                    $value = $counter++; // Assign auto-incremented integer value to "id" key
                }
            });

            return json_encode($values);
        }
    }


    /**
     * Check if List ID exists.
     *
     * @param   int     $listID     ID of list item (category/tag).
     * @param   string  $tableName  The name of the table
     *
     * @return  int
     * @since   1.0.0
     */
    public static function validateListId($listID, $tableName)
    {
        $listTable = Factory::getApplication()->bootComponent('com_easystore')->getMVCFactory()->createTable($tableName, 'Administrator');

        $data              = [];
        $data['id']        = $listID;

        if (!$listTable->load($data)) {
            $listID = 0;
        }

        return (int) $listID;
    }

    /**
     * Check product inventory status
     *
     * @param object $item
     * @return bool
     * @since 1.0.0
     */
    public static function getStockStatus($item)
    {
        $invStatus = false;

        if (!$item->is_tracking_inventory && $item->inventory_status) {
            $invStatus = true;
        } elseif ($item->is_tracking_inventory && (int) $item->quantity > 0) {
            $invStatus = true;
        }

        return $invStatus;
    }

    /**
     * Check product variants inventory status
     *
     * @param int $productId            Product id
     * @param int $isTrackingInventory  Is tracking inventory
     * @return bool                     Return true if product is in stock otherwise false
     * @since 1.0.0
     */
    public static function getVariantsStockStatus($productId, $isTrackingInventory)
    {
        // Get a database connection.
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        // Create a new query object.
        $query = $db->getQuery(true);

        $query->select('*')
            ->from('#__easystore_product_skus')
            ->where($db->quoteName('product_id') . ' = ' . $productId);

        // Set the query for execution
        $db->setQuery($query);
        $result = $db->loadObjectList();

        $outOfStock = true;

        foreach ($result as $sku) {
            if (!$isTrackingInventory) {
                if ($sku->inventory_status > 0) {
                    $outOfStock = false;
                    break;
                }
            } else {
                if ($sku->inventory_amount > 0) {
                    $outOfStock = false;
                    break;
                }
            }
        }

        return !$outOfStock;
    }
}
