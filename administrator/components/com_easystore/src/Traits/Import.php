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
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use JoomShaper\Component\EasyStore\Administrator\Model\ProductModel;
use JoomShaper\Component\EasyStore\Administrator\Model\CategoryModel;
use JoomShaper\Component\EasyStore\Administrator\Model\BrandModel;
use JoomShaper\Component\EasyStore\Administrator\Model\TagModel;
use JoomShaper\Component\EasyStore\Administrator\Model\CollectionModel;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Product Import trait
 *
 * @since 1.1.0
 */
trait Import
{
    public $extractPath;
    /**
     * Function for Import products with csv file
     * @return void
     *
     * @since 1.1.0
     */
    public function import()
    {
        // Set the maximum execution time to 300 seconds (5 minutes)
        ini_set('max_execution_time', 300);

        // Process Import file
        $input = Factory::getApplication()->input;
        $file  = $input->files->get('import_file', null, 'RAW');

        $response          = new \stdClass();
        $response->status  = true;
        $response->message = Text::_('COM_EASYSTORE_PRODUCT_IMPORT_SUCCESS');

        if ($file && $file['error'] != 0) {
            $response->status  = false;
            $response->message = Text::_('COM_EASYSTORE_PRODUCT_IMPORT_ERROR_UPLOAD');
        }

        if ($file && $file['error'] == 0) {
            // Check if the file is a valid ZIP file (you may want to add additional checks)
            $allowedExtensions = ['zip'];
            $fileInfo          = pathinfo($file['name']);
            $fileExtension     = strtolower($fileInfo['extension']);

            if (in_array($fileExtension, $allowedExtensions)) {
                $csvFile = $this->getCSVPath($file['tmp_name']);
                // Get the file contents
                $csvData = trim(file_get_contents($csvFile));

                // Convert CSV to array
                $lines   = preg_split("/,_END_.*\n/", $csvData);
                $headers = str_getcsv(array_shift($lines));

                $checkHeaders = $this->isHeadersValid($headers);

                if (!$checkHeaders) {
                    $response->status  = false;
                    $response->message = Text::_('COM_EASYSTORE_PRODUCT_IMPORT_ERROR_CSV_FILE');

                    echo json_encode($response);
                    exit;
                }

                // Initialize arrays to store final product and options values
                $productsArray = [];
                $optionKeys    = [
                    1 => 'name',
                    2 => 'type',
                ];
                $optionValueKeys = [
                    1 => 'name',
                    2 => 'color',
                ];
                $skuKeys = [
                    1 => 'combination_name',
                    2 => 'price',
                    3 => 'inventory_status',
                    4 => 'inventory_amount',
                    5 => 'sku',
                    6 => 'weight',
                    7 => 'visibility',
                    8 => 'image_id',
                ];
                $invalidFileStructure = false;

                // Iterate through lines array
                foreach ($lines as $line) {
                    $columns               = str_getcsv($line);
                    $newProductArray       = [];
                    $newOptions            = [];
                    $newOptionValues       = [];
                    $newSkus               = [];
                    $images                = [];
                    $isMediaSection        = false;
                    $isOptionsSection      = false;
                    $isOptionValuesSection = false;
                    $isSkuSection          = false;
                    $optionIndex           = 0;
                    $optionValueIndex      = 0;
                    $skuIndex              = 0;

                    foreach ($columns as $index => $value) {
                        if ($value === "_OPTIONS_") {
                            // Switch to options section
                            $isOptionsSection      = true;
                            $isOptionValuesSection = false;
                            $optionIndex           = 1;
                            $optionValueIndex      = 1;
                            $skuIndex              = 1;

                            if (!empty($newOptions)) {
                                if (!empty($newOptionValues)) {
                                    $newOptions['option_values'][] = $newOptionValues;
                                }

                                $newProductArray['options'][] = $newOptions;
                                $newOptions                   = [];
                                $newOptionValues              = [];
                            }

                            // Skip storing '_OPTIONS_' in the newOptions
                            continue;
                        }

                        if ($value === '_OPTION_VALUES_') {
                            $isOptionValuesSection = true;
                            $optionValueIndex      = 1;

                            if (!empty($newOptionValues)) {
                                $newOptions['option_values'][] = $newOptionValues;
                                $newOptionValues               = [];
                            }

                            // Skip storing '_OPTION_VALUES_' in the newOptionValuess
                            continue;
                        }

                        if ($value === "_SKU_") {
                            $isOptionsSection      = false;
                            $isOptionValuesSection = false;
                            $isSkuSection          = true;
                            $skuIndex              = 1;

                            if (!empty($newSkus)) {
                                $newProductArray['skus'][] = $newSkus;
                            }

                            // Skip storing '_SKU_' in the array
                            continue;
                        }

                        if ($value === "_END_MEDIA_") {
                            $isMediaSection = false;
                            // End line '_END_MEDIA_'
                            continue;
                        }

                        if ($isMediaSection && !empty($value)) {
                            $images[] = $value;
                        }

                        if ($value == "_START_MEDIA_") {
                            $isMediaSection = true;
                        }

                        if ($value === "_END_") {
                            // End line '_END_'
                            continue;
                        }

                        if (!empty($images)) {
                            $newProductArray['media'] = $images;
                        }

                        if ($isOptionsSection && $value !== '' && !$isMediaSection) {
                            if ($isOptionValuesSection) {
                                if (!$this->processValue($value, $newOptionValues, $optionValueKeys, $optionValueIndex, $invalidFileStructure)) {
                                    break;
                                }
                            } else {
                                if (!$this->processValue($value, $newOptions, $optionKeys, $optionIndex, $invalidFileStructure)) {
                                    break;
                                }
                            }
                        } elseif ($isSkuSection && $value !== '' && !$isMediaSection) {
                            if (!$this->processValue($value, $newSkus, $skuKeys, $skuIndex, $invalidFileStructure)) {
                                break;
                            }
                        } elseif (!empty($headers[$index])) {
                            $headerName                   = strtolower(str_replace(' ', '_', $headers[$index]));
                            $newProductArray[$headerName] = $value;
                        }
                    }

                    if (!empty($newProductArray['options'])) {
                        if (!empty($newOptionValues)) {
                            $newOptions['option_values'][] = $newOptionValues;
                        }
                        $newProductArray['options'][] = $newOptions;
                    } else {
                        if (!empty($newOptions)) {
                            if (!empty($newOptionValues)) {
                                $newOptions['option_values'][] = $newOptionValues;
                            }

                            $newProductArray['options'][] = $newOptions;
                        } else {
                            $newProductArray['options'] = [];
                        }
                    }

                    if (!empty($newSkus)) {
                        $newProductArray['skus'][] = $newSkus;
                    } else {
                        $newProductArray['skus'] = [];
                    }

                    $productsArray[] = $newProductArray;
                }

                if ($invalidFileStructure) {
                    $response->status  = false;
                    $response->message = Text::_('COM_EASYSTORE_PRODUCT_IMPORT_ERROR_CSV_FILE');

                    echo json_encode($response);
                    exit;
                }

                $db = Factory::getContainer()->get(DatabaseInterface::class);
                $db->transactionStart();

                foreach ($productsArray as $product) {
                    // Convert array to JSON string
                    $jsonString = json_encode($product);

                    // Decode JSON string into an object
                    $product  = json_decode($jsonString);
                    $response = $this->importSingleProduct($product);

                    if ($response->status === false) {
                        $db->transactionRollback();

                        $response->status = false;
                        $response->message;
                        break;
                    }
                }

                $db->transactionCommit();
            } else {
                $response->status  = false;
                $response->message = Text::_('COM_EASYSTORE_PRODUCT_IMPORT_SELECT_VALID_FILE');
            }
        }

        if (is_dir($this->extractPath)) {
            Folder::delete($this->extractPath);
        }
        echo json_encode($response);
        exit;
    }

    /**
     * Process Product Variant values
     *
     * @param  mixed $value Variant value
     * @param  array $array processed array
     * @param  mixed $keys  Array key
     * @param  mixed $index Array index
     * @param  bool $invalidFileStructure check if file structure is valid.
     *
     * @return bool
     *
     * @since 1.2.3
     */
    private function processValue($value, &$array, $keys, &$index, &$invalidFileStructure)
    {
        if (!isset($keys[$index])) {
            $invalidFileStructure = true;
            return false;
        }

        $array[$keys[$index]] = $value;
        $index++;

        return true;
    }

    /**
     * Function to add a product for the Import feature
     * @param object $product
     * @return object
     *
     * @since 1.1.0
     */
    private function importSingleProduct($product)
    {
        $db       = Factory::getContainer()->get(DatabaseInterface::class);
        $user     = Factory::getApplication()->getIdentity();
        $response = (object) [
            'status'  => true,
            'message' => Text::_('COM_EASYSTORE_PRODUCT_IMPORT_SUCCESS'),
        ];

        try {
            $categoryId = $this->setCategoryForImport($product->category);
            $brandId    = $this->setBrandForImport($product->brand);
        } catch (\Exception $e) {
            $response->status  = false;
            $response->message = $e->getMessage();

            return $response;
        }

        $product->catid          = $categoryId;
        $product->brand_id       = $brandId;
        $product->has_variants   = !empty($product->options) ? 1 : 0;
        $product->specifications = json_decode($product->additional_data);

        $productModel = new ProductModel();

        try {
            $productId = $productModel->saveProductForImport($product);
        } catch (\Exception $e) {
            $response->status  = false;
            $response->message = $e->getMessage();

            return $response;
        }

        // Process tags
        if (!empty($product->tags)) {
            $this->setTagsForImport($productId, $product->tags);
        }

        // Process Collection
        if (!empty($product->collections)) {
            $this->setCollectionsForImport($productId, $product->collections);
        }

        // Process upsell products
        if (!empty($product->upsell_products)) {
            $this->setUpsellProductsForImport($productId, $product->upsell_products);
        }
        // Process cross-sell products
        if (!empty($product->crosssell_products)) {
            $this->setCrossSellProductsForImport($productId, $product->crosssell_products);
        }

        // Process options and option values
        foreach ($product->options as $key => $option) {
            $productOption             = new \stdClass();
            $productOption->product_id = $productId;
            $productOption->name       = $option->name;
            $productOption->type       = $option->type;
            $productOption->ordering   = $key + 1;

            try {
                $db->insertObject('#__easystore_product_options', $productOption);
            } catch (\Exception $e) {
                $response->status  = false;
                $response->message = $e->getMessage();

                return $response;
            }

            $optionId = $db->insertid();

            if (!empty($option->option_values)) {
                foreach ($option->option_values as $key => $optionValue) {
                    $optionValue->product_id = $productId;
                    $optionValue->option_id  = $optionId;
                    $optionValue->ordering   = $key + 1;
                    try {
                        $db->insertObject('#__easystore_product_option_values', $optionValue);
                    } catch (\Exception $e) {
                        $response->status  = false;
                        $response->message = $e->getMessage();

                        return $response;
                    }
                }
            }
        }


        if (!empty($product->media)) {
            $mediaParams = ComponentHelper::getParams('com_media');
            $path        = $mediaParams->get('file_path', 'images');

            $imagePath   =  JPATH_ROOT . '/' .  Path::clean($path . '/easystore/product-' . $productId);
            $dbPath      = Path::clean($path . '/easystore/product-' . $productId);

            if (!is_dir($imagePath)) {
                Folder::create($imagePath);
            }

            foreach ($product->media as $key => $media) {
                $src      = $this->extractPath . '/' . $media;
                $featured = 0;
                if ($key == 0) {
                    $featured = 1;
                }

                $originalExtension = pathinfo($src, PATHINFO_EXTENSION);
                $newImageName      = 'product_' . $productId . '_' . uniqid() . '.' . $originalExtension;

                $newMediaType = $this->getMediaType($src);

                $dest = $imagePath . '/' . $newImageName;

                if (File::move($src, $dest)) {
                    $columns = ['product_id', 'name', 'type', 'is_featured', 'src', 'alt_text', 'created'];
                    $db      = Factory::getContainer()->get(DatabaseInterface::class);
                    $query   = $db->getQuery(true);
                    $query->insert($db->quoteName('#__easystore_media'))->columns($db->quoteName($columns));

                    $item = [
                        $db->quote($productId),
                        $db->quote($newImageName),
                        $db->quote($newMediaType),
                        $db->quote($featured),
                        $db->quote($dbPath . '/' . $newImageName),
                        $db->quote($newImageName),
                        $db->quote(Factory::getDate('now')),
                    ];

                    $query->values(implode(',', $item));
                    $db->setQuery($query);
                    try {
                        $db->execute();
                    } catch (\Throwable $th) {
                        throw $th;
                    }
                }
            }
        }

        if (!empty($product->skus)) {
            $date = Factory::getDate();

            foreach ($product->skus as $key => $sku) {
                $skuData                    = new \stdClass();
                $skuData->product_id        = $productId;
                $skuData->combination_name  = $sku->combination_name;
                $skuData->combination_value = str_replace(' | ', ';', $sku->combination_name);
                $skuData->price             = $sku->price;
                $skuData->inventory_status  = $sku->inventory_status;
                $skuData->inventory_amount  = $sku->inventory_amount;

                if (!empty($sku->sku) && $sku->sku !== 'NULL') {
                    $skuData->sku = $sku->sku;
                }

                if (!empty($sku->weight) && $sku->weight !== 'NULL') {
                    $skuData->weight = $sku->weight;
                }

                if (!empty($sku->visibility) && $sku->visibility !== 'NULL') {
                    $skuData->visibility = $sku->visibility;
                }

                if (!empty($sku->image_id) && $sku->image_id !== 'NULL') {
                    $skuData->image_id = $sku->image_id;
                }

                $skuData->ordering   = $key + 1;
                $skuData->created    = $date->toSql();
                $skuData->created_by = $user->get('id');

                try {
                    $db->insertObject('#__easystore_product_skus', $skuData);
                } catch (\Exception $e) {
                    $response->status  = false;
                    $response->message = $e->getMessage();

                    return $response;
                }
            }
        }

        return $response;
    }

    /**
     * Assign tags to a product during import.
     *
     * @param int $productId
     * @param string $tagsString Comma-separated tag names
     * @return void
     */
    private function setTagsForImport($productId, $tagsString)
    {
        if (empty($tagsString) || !$productId) {
            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $tagModel = new TagModel();

        // Remove existing tag mappings for this product
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__easystore_product_tag_map'))
            ->where($db->quoteName('product_id') . ' = ' . (int) $productId);
        $db->setQuery($query)->execute();

        $tags = array_map('trim', explode(',', $tagsString));
        foreach ($tags as $tagName) {
            if (empty($tagName)) {
                continue;
            }

            // Check if tag exists
            $tagId = EasyStoreDatabaseOrm::get('#__easystore_tags', 'title', $tagName, 'id')->loadResult();
            if (!$tagId) {
                // Create tag if not exists
                $tag = [
                    "title"       => $tagName,
                    "alias"       => EasyStoreHelper::makeAliasUnique(ApplicationHelper::stringURLSafe($tagName), '#__easystore_tags'),
                    "id"          => 0,
                    "published"   => 1,
                    "access"      => 1,
                    "created_by"  => '',
                    "created"     => '',
                    "modified_by" => '',
                    "modified"    => '',
                ];
                $tagId = $tagModel->save($tag, true);
            }

            // Insert mapping
            $mapping = (object) [
                'product_id' => $productId,
                'tag_id'     => $tagId,
            ];
            try {
                $db->insertObject('#__easystore_product_tag_map', $mapping);
            } catch (\Exception $e) {
                // Ignore duplicate or error
            }
        }
    }

    /**
     * Assign collections to a product during import.
     *
     * @param int $productId
     * @param string $collectionsString Comma-separated collection names
     * @return void
     */
    private function setCollectionsForImport($productId, $collectionsString)
    {
        if (empty($collectionsString) || !$productId) {
            return;
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $collectionModel = new CollectionModel();

        // Remove existing collection mappings for this product
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__easystore_collection_product_map'))
            ->where($db->quoteName('product_id') . ' = ' . (int) $productId);
        $db->setQuery($query)->execute();
        $collections = array_map('trim', explode(',', $collectionsString));

        foreach ($collections as $collectionName) {
            if ($collectionName === '') {
                continue;
            }

            // Check if collection exists
            $collectionId = EasyStoreDatabaseOrm::get('#__easystore_collections', 'title', $collectionName, 'id')->loadResult();
            if (!$collectionId) {
                // Create collection if not exists
                $collection = [
                    "title"       => $collectionName,
                    "alias"       => EasyStoreHelper::makeAliasUnique(ApplicationHelper::stringURLSafe($collectionName), '#__easystore_collections'),
                    "id"          => 0,
                    "published"   => 1,
                    "access"      => 1,
                    "created_by"  => '',
                    "created"     => '',
                    "modified_by" => '',
                    "modified"    => '',
                ];
                $collectionId = $collectionModel->save($collection, true);
            }
            // Insert mapping
            $mapping = (object) [
                'product_id'      => $productId,
                'collection_id'   => $collectionId,
            ];
            try {
                $db->insertObject('#__easystore_collection_product_map', $mapping);
            } catch (\Exception $e) {
                // Ignore duplicate or error
            }
        }
    }

    /**
     * Function to find category_id from string, if not fount it will create new and its child if needed ad return the id
     * @param mixed $categoryString     This is the category string with nested info which is separated by " > "
     * @return int
     * @since 1.1.0
     */
    private function setCategoryForImport($categoryString)
    {
        if (empty($categoryString)) {
            return 0;
        }

        $categories       = explode(" > ", $categoryString);
        $returnCategoryId = 0;
        $newCreated       = false;
        $categoryModel    = new CategoryModel();

        foreach ($categories as $categoryName) {
            $whereConditions = [
                (object) [
                    'key'      => 'parent_id',
                    'operator' => '=',
                    'value'    => empty($returnCategoryId) ? $categoryModel->getRootParentId() : $returnCategoryId,
                ],
            ];

            $categoryId = EasyStoreDatabaseOrm::get('#__easystore_categories', 'title', $categoryName, '*', $whereConditions)->loadObject()->id ?? false;

            if ($categoryId && !$newCreated) {
                $returnCategoryId = $categoryId;
            } else {
                // create category
                $category = [
                    "title"       => $categoryName,
                    "alias"       => EasyStoreHelper::makeAliasUnique(ApplicationHelper::stringURLSafe($categoryName), '#__easystore_categories'),
                    "image"       => '',
                    "id"          => 0,
                    "published"   => 1,
                    "parent_id"   => $returnCategoryId,
                    "access"      => 1,
                    "created_by"  => '',
                    "created"     => '',
                    "modified_by" => '',
                    "modified"    => '',
                ];

                $newId = $categoryModel->save($category, true);

                $returnCategoryId = $newId;
                $newCreated       = true;
            }
        }

        return $returnCategoryId;
    }

    /**
     * Function to find brand_id from string, if not found it will create new and return the id
     * @param mixed $brandString Brand name as string
     * @return int
     * @since 1.3.0
     */
    private function setBrandForImport($brandString)
    {
        if (empty($brandString)) {
            return 0;
        }

        $brandName = trim($brandString);
        $brandModel = new BrandModel();

        // Find brand by title only, since there is no parent_id
        $brandId = EasyStoreDatabaseOrm::get('#__easystore_brands', 'title', $brandName, '*')->loadObject()->id ?? false;

        if ($brandId) {
            return $brandId;
        } else {
            // create brand
            $brand = [
                "title"       => $brandName,
                "alias"       => EasyStoreHelper::makeAliasUnique(ApplicationHelper::stringURLSafe($brandName), '#__easystore_brands'),
                "id"          => 0,
                "published"   => 1,
                "image"       => '',
                "access"      => 1,
                "created_by"  => '',
                "created"     => '',
                "modified_by" => '',
                "modified"    => '',
            ];

            $newId = $brandModel->save($brand, true);

            return $newId;
        }
    }

    private function setUpsellProductsForImport($productId, $upsellString)
    {
        if (empty($upsellString) || empty($productId)) {
            return false;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $productModel = new ProductModel();

        // Clear existing upsells
        $query = $db->getQuery(true)
            ->delete('#__easystore_product_upsells')
            ->where('product_id = ' . (int)$productId);
        $db->setQuery($query)->execute();

        // Process upsell products
        $upsellTitles = array_map('trim', explode(',', $upsellString));

        // Check if upsell products exist, if not then store in product table
        foreach ($upsellTitles as $title) {
            if (empty($title)) {
                continue;
            }

            $upsellId = EasyStoreDatabaseOrm::get('#__easystore_products', 'title', $title, 'id')
                ->loadResult();

            if (!$upsellId && !empty($title)) {
                // Create product if not exists
                $productData = [
                    "title"       => $title,
                    "alias"       => EasyStoreHelper::makeAliasUnique(ApplicationHelper::stringURLSafe($title), '#__easystore_products'),
                    "id"          => 0,
                    "published"   => 1,
                    "access"      => 1,
                    "created_by"  => '',
                    "created"     => '',
                    "modified_by" => '',
                    "modified"    => '',
                ];
                $upsellId = $productModel->save($productData, true);
            }

            //insert upsell product
            $mapping = (object) [
                'product_id' => $productId,
                'upsell_id'  => $upsellId
            ];  
            try {
                $db->insertObject('#__easystore_product_upsells', $mapping);
            } catch (\Exception $e) {
                // Ignore duplicate or error
            }
        }

    }


    private function setCrossSellProductsForImport($productId, $crossSellString)
    {
        if (empty($crossSellString) || empty($productId)) {
            return false;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $productModel = new ProductModel();

        // Clear existing cross-sells
        $query = $db->getQuery(true)
            ->delete('#__easystore_product_crossells')
            ->where('product_id = ' . (int)$productId);
        $db->setQuery($query)->execute();

        // Process cross-sell products
        $crossSellTitles = array_map('trim', explode(',', $crossSellString));
       
        foreach ($crossSellTitles as $title) {
            if (empty($title)) {
                continue;
            }

            $crossSellId = EasyStoreDatabaseOrm::get('#__easystore_products', 'title', $title, 'id')
                ->loadResult();

            if (!$crossSellId && !empty($title)) {
                // Create product if not exists
                $productData = [
                    "title"       => $title,
                    "alias"       => EasyStoreHelper::makeAliasUnique(ApplicationHelper::stringURLSafe($title), '#__easystore_products'),
                    "id"          => 0,
                    "published"   => 1,
                    "access"      => 1,
                    "created_by"  => '',
                    "created"     => '',
                    "modified_by" => '',
                    "modified"    => '',
                ];
                $crossSellId = $productModel->save($productData, true);
            }

            // Insert cross-sell product
            $mapping = (object) [
                'product_id' => $productId,
                'crossell_id' => $crossSellId
            ];

            try {
                $db->insertObject('#__easystore_product_crossells', $mapping);
            } catch (\Exception $e) {
            }
        
        }
    }

    /**
     * Function to check if the uploaded csv headers match the structure
     * @param array $headers
     * @return bool
     *
     * @since 1.1.0
     */
    private function isHeadersValid($headers)
    {
        $response = true;
        $standard = [
            0  => "Title",
            1  => "Alias",
            2  => "Description",
            3  => "Category",
            4  => "Brand",
            5  => "Tags",
            6  => "Collections",
            7  => "Weight",
            8  => "Unit",
            9  => "Dimension",
            10 => "Regular Price",
            11 => "Is Taxable",
            12 => "Has Sale",
            13 => "Featured",
            14 => "Is Tracking Inventory",
            15 => "Inventory Status",
            16 => "Enable Out of Stock Sell",
            17 => "Quantity",
            18 => "SKU",
            19 => "Additional Data",
            20 => "Discount Type",
            21 => "Discount Value",
            22 => "Published",
            23 => "UpSell Products",
            24 => "CrossSell Products",
        ];

        // Check if header count matches
        if (count($headers) !== count($standard)) {
            return false;
        }

        foreach ($standard as $key => $value) {
            if ($standard[$key] != $headers[$key]) {
                $response = false;
                break;
            }
        }

        return $response;
    }

    /**
     * Function for download the sample file
     * @return void
     *
     * @since 1.1.0
     */
    public function downloadSampleFile()
    {
        $file = JPATH_ROOT . '/administrator/components/com_easystore/assets/import/easystore_products_sample.zip';

        if (file_exists($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        } else {
            // Handle the case where the file does not exist
            echo "File not found.";
        }
    }

    /**
     * Process product media files
     *
     * @param  int $productId Product Id
     * @return bool
     */
    private function processProductMediaEntires($productId)
    {
        $image              = new \stdClass();
        $db                 = Factory::getContainer()->get(DatabaseInterface::class);
        $date               = Factory::getDate();

        $mediaParams = ComponentHelper::getParams('com_media');
        $path        = $mediaParams->get('file_path', 'images');
        $imagePath   = $path . '/easystore/product-' . $productId;

        $image->product_id  = $productId;
        $image->src         = $imagePath;
        $image->created     = $date->toSql();
        $image->created_by  = Factory::getApplication()->getIdentity()->id;
        $image->modified_by = Factory::getApplication()->getIdentity()->id;

        try {
            return $db->insertObject('#__easystore_media', $image);
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Get the csv file path
     *
     * @param  string $zipFilePath ZIP file path
     *
     * @return string
     *
     * @since 1.3.0
     */
    private function getCSVPath($zipFilePath)
    {
        $app = Factory::getApplication();

        $tmpPath           = $app->get('tmp_path');
        $this->extractPath = $tmpPath . '/extracted_' . time();

        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath) === true) {
            $zip->extractTo($this->extractPath);
            $zip->close();
            $app->enqueueMessage('File unzipped successfully.', 'message');
        } else {
            $app->enqueueMessage('Failed to unzip file.', 'error');
            return false;
        }

        // Step 2: Read CSV file and import into database
        $csvFilePath = $this->extractPath . '/easystore_products.csv';

        return $csvFilePath;
    }

    /**
     * Get the media type
     *
     * @param  string $fileName Media file name
     *
     * @return string
     * @since 1.2.3
     */
    private function getMediaType($fileName)
    {
        $mediaParams     = ComponentHelper::getParams('com_media');
        $imageExtensions = $mediaParams->get('image_extensions', '');
        $videoExtensions = $mediaParams->get('video_extensions', '');
        $imageExtensions = explode(',', $imageExtensions);
        $videoExtensions = explode(',', $videoExtensions);

        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        if (in_array($extension, $imageExtensions, true)) {
            return 'image';
        }

        if (in_array($extension, $videoExtensions, true)) {
            return 'video';
        }

        return 'unsupported';
    }
}