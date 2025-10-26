<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Traits;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Router\Route;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Utilities\ArrayHelper;
use JoomShaper\Component\EasyStore\Site\Traits\ProductMedia;
use JoomShaper\Component\EasyStore\Administrator\Model\CategoryModel;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


/**
 * Product Export trait
 *
 * @since 1.1.0
 */
trait Export
{
    use ProductMedia;

    /**
     * Function for Export products to csv file
     *
     * @return void
     *
     * @since 1.1.0
     * @since 1.2.1 improve this function
     */
    public function export()
    {
        $this->exportProducts();
    }

    /**
     * Export products to a CSV file
     *
     * @return void
     *
     * @throws \Exception
     * @since 1.2.1
     */
    public function exportProducts()
    {
        ini_set('max_execution_time', 300);

        $productIds = $this->input->post->getString('cids');

        $orm                                          = new EasyStoreDatabaseOrm();
        $products                                     = $this->getProducts($productIds, $orm);
        list($productList, $mediaFiles, $exportDir)   = $this->getProductsForExport($products, $orm);

        $csvData     = $this->generateCsvData($productList);
        $zipFilePath = $this->createZipArchive($exportDir, $csvData, $mediaFiles);

        $this->redirectToDownload($zipFilePath);
    }

    /**
     * Create export directory
     *
     * @return string Path to the created directory
     * @throws \Exception
     */
    private function createExportDirectory(): string
    {
        $exportDir = JPATH_ROOT . '/tmp/com_easystore/exports/' . uniqid();
        if (!Folder::create($exportDir)) {
            throw new \Exception("Failed to create export directory");
        }
        return $exportDir;
    }

    /**
     * Process product media files
     *
     * @param array  $products  Array of product objects
     * @param string $exportDir Path to the export directory
     *
     * @return array Array of processed media file names
     */
    private function processProductMedia(array $products, string $exportDir): array
    {
        $mediaFiles   = [];
        $productMedia = [];
        foreach ($products as $product) {
            $media = $this->getMedia($product->id, false);
            if (!empty($media->gallery)) {
                foreach ($media->gallery as &$image) {
                    $newImageName = $this->copyMediaFile($image->src, $product->id, $exportDir);
                    if ($newImageName) {
                        $image->src   = 'media/' . $newImageName;
                        $mediaFiles[] = $newImageName;
                    }
                }
            }
            $productMedia[$product->id] = $media->gallery;
            $product->media             = $media->gallery;
        }
        return [$mediaFiles, $productMedia];
    }

    /**
     * Copy media file to export directory
     *
     * @param string $imageUrl  Original image URL
     * @param int    $productId Product ID
     * @param string $exportDir Path to the export directory
     *
     * @return string|null New image name or null if copy failed
     */
    private function copyMediaFile(string $imageUrl, int $productId, string $exportDir): ?string
    {
        $originalExtension = pathinfo($imageUrl, PATHINFO_EXTENSION);
        $newImageName      = 'product_' . $productId . '_' . uniqid() . '.' . $originalExtension;
        $localPath         = $exportDir . '/' . $newImageName;

        if (File::copy(JPATH_ROOT . '/' . $imageUrl, $localPath)) {
            return $newImageName;
        } else {
            Factory::getApplication()->enqueueMessage("Failed to copy file: " . $imageUrl, 'warning');
            return null;
        }
    }

    /**
     * Generate CSV data from products
     *
     * @param array $products Array of product objects
     *
     * @return string CSV data
     */
    private function generateCsvData(array $products): string
    {


        $csvHeaders = [
            "Title",
            "Alias",
            "Description",
            "Category",
            "Brand",
            "Tags",
            "Collections",
            "Weight",
            "Unit",
            "Dimension",
            "Regular Price",
            "Is Taxable",
            "Has Sale",
            "Featured",
            "Is Tracking Inventory",
            "Inventory Status",
            "Enable Out of Stock Sell",
            "Quantity",
            "SKU",
            "Additional Data",
            "Discount Type",
            "Discount Value",
            "Published",
            "UpSell Products",
            "CrossSell Products",
            "_END_",
        ];

        $csvData = implode(',', $csvHeaders) . PHP_EOL;

        foreach ($products as $product) {
            $csvData .= $this->generateProductCsvLine((object) $product);
        }

        return $csvData;
    }

    /**
     * Generate CSV line for a single product
     *
     * @param object $product Product object
     *
     * @return string CSV line for the product
     */
    private function generateProductCsvLine(object $product): string
    {
        $line = '';
        foreach ($product as $key => $value) {
            if ($key === 'media') {
                $line .= $this->generateMediaCsvData($value);
            } elseif ($key === 'options') {
                $line .= $this->generateOptionsCsvData($value);
            } elseif ($key === 'skus') {
                $line .= $this->generateSkusCsvData($value);
            } else {
                $line .= '"' . str_replace('"', '""', $value ?? '') . '",';
            }
        }

        return rtrim($line, ',') . ',_END_' . PHP_EOL;
    }

    /**
     * Generate CSV data for media
     *
     * @param array $media Array of media objects
     *
     * @return string CSV data for media
     */
    private function generateMediaCsvData(array $media): string
    {
        $mediaData = '';
        $mediaData .= '"_START_MEDIA_",';
        foreach ($media as $image) {
            $mediaData .= '"' . str_replace('"', '""', $image['src'] ?? '') . '",';
        }
        $mediaData .= '"_END_MEDIA_",';
        return $mediaData;
    }

    /**
     * Generate CSV data for options
     *
     * @param array $options Array of option objects
     *
     * @return string CSV data for options
     */
    private function generateOptionsCsvData(array $options): string
    {
        $optionsData = '';
        foreach ($options as $option) {
            $optionsData .= '"_OPTIONS_",';
            $optionsData .= '"' . str_replace('"', '""', $option['name'] ?? '') . '",';
            $optionsData .= '"' . str_replace('"', '""', $option['type'] ?? '') . '",';

            if (!empty($option['option_values'])) {
                foreach ($option['option_values'] as $optionValue) {
                    $optionsData .= '"_OPTION_VALUES_",';
                    $optionsData .= '"' . str_replace('"', '""', $optionValue['name'] ?? '') . '",';

                    if (!empty($optionValue['color'])) {
                        $optionsData .= '"' . str_replace('"', '""', $optionValue['color'] ?? '') . '",';
                    }
                }
            }
        }
        return $optionsData;
    }

    /**
     * Generate CSV data for SKUs
     *
     * @param array $skus Array of SKU objects
     *
     * @return string CSV data for SKUs
     */
    private function generateSkusCsvData(array $skus): string
    {
        $skusData = '';
        foreach ($skus as $sku) {
            $skusData .= '"_SKU_",';
            $skusData .= '"' . str_replace('"', '""', $sku['combination_name']) . '",';
            $skusData .= '"' . str_replace('"', '""', !empty($sku['price']) ? $sku['price'] : '0.00') . '",';
            $skusData .= '"' . str_replace('"', '""', !empty($sku['inventory_status']) ? $sku['inventory_status'] : '0') . '",';
            $skusData .= '"' . str_replace('"', '""', !empty($sku['inventory_amount']) ? $sku['inventory_amount'] : '0') . '",';
            $skusData .= '"' . str_replace('"', '""', !empty($sku['sku']) ? $sku['sku'] : 'NULL') . '",';
            $skusData .= '"' . str_replace('"', '""', !empty($sku['weight']) ? $sku['weight'] : 'NULL') . '",';
            $skusData .= '"' . str_replace('"', '""', !empty($sku['visibility']) ? $sku['visibility'] : '1') . '",';
            $skusData .= '"' . str_replace('"', '""', !empty($sku['image_id']) ? $sku['image_id'] : 'NULL') . '",';
        }
        return $skusData;
    }

    /**
     * Create ZIP archive with CSV and media files
     *
     * @param string $exportDir  Path to the export directory
     * @param string $csvData    CSV data to be written
     * @param array  $mediaFiles Array of media file names
     *
     * @return string Path to the created ZIP file
     * @throws \Exception
     */
    private function createZipArchive(string $exportDir, string $csvData, array $mediaFiles): string
    {
        $date        = new Date('now');
        $folderName  = 'easystore_products_' . $date->format('Y-m-d');
        $filename    = 'easystore_products';
        $csvFilePath = $exportDir . '/' . $filename . '.csv';
        File::write($csvFilePath, $csvData);

        $zipFilePath = $exportDir . '/' . $folderName . '.zip';
        $zip         = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE) !== true) {
            throw new \Exception("Cannot create ZIP file");
        }

        $zip->addFile($csvFilePath, basename($csvFilePath));

        foreach ($mediaFiles as $mediaFile) {
            $zip->addFile($exportDir . '/' . $mediaFile, 'media/' . $mediaFile);
        }

        $zip->close();

        $this->cleanupFiles($csvFilePath, $mediaFiles, $exportDir);

        return $zipFilePath;
    }

    /**
     * Clean up temporary files after creating ZIP
     *
     * @param string $csvFilePath Path to the CSV file
     * @param array  $mediaFiles  Array of media file names
     * @param string $exportDir   Path to the export directory
     *
     * @return void
     */
    private function cleanupFiles(string $csvFilePath, array $mediaFiles, string $exportDir): void
    {
        if (is_file($csvFilePath)) {
            File::delete($csvFilePath);
        }
        foreach ($mediaFiles as $mediaFile) {
            File::delete($exportDir . '/' . $mediaFile);
        }
    }

    /**
     * Redirect to download method
     *
     * @param string $zipFilePath Path to the ZIP file
     *
     * @return void
     */
    private function redirectToDownload(string $zipFilePath): void
    {
        $this->setRedirect(
            Route::_('index.php?option=com_easystore&task=products.downloadExport&path=' . base64_encode($zipFilePath), false)
        );
    }

    /**
     * Download Zip file
     *
     * @return void
     */
    public function downloadExport()
    {
        $app   = Factory::getApplication();
        $input = $app->input;

        // Get the encoded file path from the URL
        $encodedPath = $input->getString('path', '');
        $filePath    = base64_decode($encodedPath);

        // Security check: ensure the file is within the allowed directory
        $allowedDir = JPATH_ROOT . '/tmp/com_easystore/exports/';
        $tempDir    = JPATH_ROOT . '/tmp/com_easystore/';

        if (strpos($filePath, $allowedDir) !== 0) {
            throw new \Exception('Access denied');
        }

        // Check if file exists
        if (!file_exists($filePath)) {
            throw new \Exception('File not found');
        }

        // Get the file name
        $fileName = basename($filePath);

        // Set headers for file download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Pragma: public');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        // Output file contents
        readfile($filePath);

        if (is_file($filePath)) {
            // Delete the ZIP file
            File::delete($filePath);
        }

        // Delete the parent directory
        $parentDir = dirname($filePath);
        if (is_dir($tempDir) && Folder::delete($tempDir) === false) {
            // Log error if directory deletion fails
            Log::add('Failed to delete export directory: ' . $parentDir, Log::WARNING, 'com_easystore');
        }

        // Exit to prevent any additional output
        $app->close();
    }

    /**
     * Function to generate the category string for export
     * i.e: Accessories > Watches
     *
     * @param int $categoryId
     * @return string
     */
    private function generateCategoryString($categoryId)
    {
        $orm               = new EasyStoreDatabaseOrm();
        $categories        = $orm->get("#__easystore_categories", "", "", ['id', 'title', 'parent_id'])->loadAssocList();

        $categoryString    = '';
        $currentCategoryId = $categoryId;

        $categoryModel = new CategoryModel();
        $rootId        = $categoryModel->getRootParentId();

        // Loop until we reach the root parent
        while ($currentCategoryId !== $rootId) {
            // Find the category with the current category ID
            $category = array_filter($categories, function ($cat) use ($currentCategoryId) {
                return $cat['id'] == $currentCategoryId;
            });

            // If category found, prepend its title to the string
            if (!empty($category)) {
                $category          = reset($category);
                $categoryString    = $category['title'] . ($categoryString ? ' > ' . $categoryString : '');
                $currentCategoryId = $category['parent_id'];
            } else {
                // Category not found, break the loop
                break;
            }
        }

        return $categoryString;
    }

    /**
     * Generate brand string for export.
     *
     * @param int $brandId
     * @return string
     */
    private function generateBrandString($brandId)
    {
        if (empty($brandId)) {
            return '';
        }

        $orm    = new EasyStoreDatabaseOrm();
        $brands = $orm->get("#__easystore_brands", null, null, ['id', 'title'])->loadAssocList();

        foreach ($brands as $brand) {
            if ((int) $brand['id'] === (int) $brandId) {
                return $brand['title'];
            }
        }

        return '';
    }

    /**
     * Retrieve tags for a given product.
     *
     * @param int $productId
     * @param EasyStoreDatabaseOrm $orm
     * @return array Array of tag titles
     */
    private function getProductTags($productId, $orm)
    {
        $tags = [];

        // Get tag IDs from the new pivot table
        $tagRelations = $orm->setColumns(['tag_id'])
            ->hasMany($productId, '#__easystore_product_tag_map', 'product_id')
            ->loadObjectList();

        if (!empty($tagRelations) && is_array($tagRelations)) {
            $tagIds = array_map(function ($relation) {
                return $relation->tag_id;
            }, $tagRelations);

            if (!empty($tagIds)) {
                // Get tag titles
                $tagObjects = $orm->updateQuery(function ($query) use ($orm, $tagIds) {
                    $query->select(['id', 'title'])
                        ->from("#__easystore_tags")
                        ->where($orm->quoteName('id') . ' IN (' . implode(',', array_map('intval', $tagIds)) . ')');
                })->loadObjectList();
                foreach ($tagObjects as $tag) {
                    $tags[] = $tag->title;
                }
            }
        }

        return $tags;
    }

    /**
     * Retrieve collections for a given product.
     *
     * @param int $productId
     * @param EasyStoreDatabaseOrm $orm
     * @return array Array of collection titles
     */
    private function getProductCollections($productId, $orm)
    {
        $collections = [];

        // Get collection IDs from the pivot table
        $collectionRelations = $orm->setColumns(['collection_id'])
            ->hasMany($productId, '#__easystore_collection_product_map', 'product_id')
            ->loadObjectList();

        if (!empty($collectionRelations) && is_array($collectionRelations)) {
            $collectionIds = array_map(function ($relation) {
                return $relation->collection_id;
            }, $collectionRelations);

            if (!empty($collectionIds)) {
                // Get collection titles
                $collectionObjects = $orm->updateQuery(function ($query) use ($orm, $collectionIds) {
                    $query->select(['id', 'title'])
                        ->from("#__easystore_collections")
                        ->where($orm->quoteName('id') . ' IN (' . implode(',', array_map('intval', $collectionIds)) . ')');
                })->loadObjectList();
                foreach ($collectionObjects as $collection) {
                    $collections[] = $collection->title;
                }
            }
        }

        return $collections;
    }

    /**
     * Get upsell product titles for a product.
     */
    private function getUpsellProducts($productId, $orm)
    {
        $upsellTitles = [];
        $relations = $orm->setColumns(['upsell_id'])
            ->hasMany($productId, '#__easystore_product_upsells', 'product_id')
            ->loadObjectList();

        if (!empty($relations) && is_array($relations)) {
            $ids = array_map(function ($r) {
                return $r->upsell_id;
            }, $relations);
            if (!empty($ids)) {
                $products = $orm->updateQuery(function ($query) use ($orm, $ids) {
                    $query->select(['id', 'title'])
                        ->from("#__easystore_products")
                        ->where($orm->quoteName('id') . ' IN (' . implode(',', array_map('intval', $ids)) . ')');
                })->loadObjectList();
                foreach ($products as $product) {
                    $upsellTitles[] = $product->title;
                }
            }
        }
        return $upsellTitles;
    }

    /**
     * Get cross-sell product titles for a product.
     */
    private function getCrosssellProducts($productId, $orm)
    {
        $crosssellTitles = [];
        $relations = $orm->setColumns(['crossell_id'])
            ->hasMany($productId, '#__easystore_product_crossells', 'product_id')
            ->loadObjectList();

        if (!empty($relations) && is_array($relations)) {
            $ids = array_map(function ($r) {
                return $r->crossell_id;
            }, $relations);
            if (!empty($ids)) {
                $products = $orm->updateQuery(function ($query) use ($orm, $ids) {
                    $query->select(['id', 'title'])
                        ->from("#__easystore_products")
                        ->where($orm->quoteName('id') . ' IN (' . implode(',', array_map('intval', $ids)) . ')');
                })->loadObjectList();
                foreach ($products as $product) {
                    $crosssellTitles[] = $product->title;
                }
            }
        }
        return $crosssellTitles;
    }

    /**
     * Retrieve products based on provided IDs.
     *
     * @param string               $ids comma-separated string of product IDs.
     * @param EasyStoreDatabaseOrm $orm An instance of EasyStore database ORM class.
     * @return array                    Array of product objects if IDs are provided, otherwise all products.
     * @since  1.1.0
     */
    private function getProductsForExport($products, $orm)
    {
        $finalProductArray = [];

        $exportDir                       = $this->createExportDirectory();
        list($mediaFiles, $productMedia) = $this->processProductMedia($products, $exportDir);

        foreach ($products as &$product) {
            $options = $orm->setColumns(['id', 'name', 'type'])
                ->hasMany($product->id, '#__easystore_product_options', 'product_id')
                ->updateQuery(function ($query) use ($orm) {
                    $query->order($orm->quoteName('ordering') . ' ASC');
                })
                ->loadObjectList();

            if (!empty($options)) {
                foreach ($options as &$option) {
                    $option->option_values = $orm->setColumns(['name', 'color'])
                        ->hasMany($option->id, '#__easystore_product_option_values', 'option_id')
                        ->updateQuery(function ($query) use ($orm) {
                            $query->order($orm->quoteName('ordering') . ' ASC');
                        })
                        ->loadObjectList();

                    unset($option->id);
                }

                unset($option);
            }

            $orm  = new EasyStoreDatabaseOrm();
            $skus = $orm->setColumns(['combination_name', 'price', 'inventory_status', 'inventory_amount', 'sku', 'weight', 'visibility', 'image_id'])
                ->hasMany($product->id, '#__easystore_product_skus', 'product_id')
                ->loadObjectList();

            $tags = $this->getProductTags($product->id, $orm);
            $collections = $this->getProductCollections($product->id, $orm);
            $upsellProducts = $this->getUpsellProducts($product->id, $orm);
            $crosssellProducts = $this->getCrosssellProducts($product->id, $orm);

            $newObject = new \stdClass();
            $newObject->title                     = $product->title;
            $newObject->alias                     = $product->alias;
            $newObject->description               = $product->description;
            $newObject->category                  = $this->generateCategoryString($product->catid);
            $newObject->brand                     = $this->generateBrandString($product->brand_id);
            $newObject->tags                      = implode(', ', $tags);
            $newObject->collections               = implode(', ', $collections);
            $newObject->weight                    = $product->weight;
            $newObject->unit                      = $product->unit;
            $newObject->dimension                 = $product->dimension;
            $newObject->regular_price             = $product->regular_price;
            $newObject->is_taxable                = $product->is_taxable;
            $newObject->has_sale                  = $product->has_sale;
            $newObject->featured                  = $product->featured;
            $newObject->is_tracking_inventory     = $product->is_tracking_inventory;
            $newObject->inventory_status          = $product->inventory_status;
            $newObject->enable_out_of_stock_sell  = $product->enable_out_of_stock_sell;
            $newObject->quantity                  = $product->quantity;
            $newObject->sku                       = $product->sku;
            $newObject->additional_data           = $product->additional_data;
            $newObject->discount_type             = $product->discount_type;
            $newObject->discount_value            = $product->discount_value;
            $newObject->published                 = $product->published;
            $newObject->upsell_products           = implode(', ', $upsellProducts);
            $newObject->crosssell_products        = implode(', ', $crosssellProducts);
            $newObject->options                   = $options ?? [];
            $newObject->skus                      = $skus ?? [];
            $newObject->media                     = $productMedia[$product->id] ?? [];

            $finalProductArray[] = $newObject;
        }

        unset($product);

        $finalProductArray = json_decode(json_encode($finalProductArray), true); // convert to array from object


        return [$finalProductArray, $mediaFiles, $exportDir] ;
    }

    /**
     * Retrieve products based on provided IDs.
     *
     * @param string               $ids comma-separated string of product IDs.
     * @param EasyStoreDatabaseOrm $orm An instance of EasyStore database ORM class.
     * @return array                    Array of product objects if IDs are provided, otherwise all products.
     * @since  1.1.0
     */
    private function getProducts($ids, $orm)
    {
        if (empty($ids)) {
            return $orm->get("#__easystore_products", "published", "1")->loadObjectList();
        }

        $pks = ArrayHelper::toInteger(explode(',', $ids));

        $products = $orm->updateQuery(function ($query) use ($orm, $pks) {
            $query->select('*')
                ->from("#__easystore_products")
                ->where($orm->quoteName('published') . ' = 1')
                ->where($orm->quoteName('id') . ' IN (' . implode(',', $pks) . ')');
        })->loadObjectList();

        return $products;
    }
}
