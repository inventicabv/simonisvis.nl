<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Model;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\Database\ParameterType;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\Registry\Registry;
use JoomShaper\Component\EasyStore\Administrator\Concerns\HasRelationship;
use JoomShaper\Component\EasyStore\Site\Traits\ProductMedia;
use JoomShaper\Component\EasyStore\Administrator\Constants\Status;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Traits\VariantManagement;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreProductHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper as SiteEasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Products Component Product Model
 *
 * @since  1.0.0
 */
class ProductModel extends AdminModel
{
    use ProductMedia;
    use VariantManagement;
    use HasRelationship;

    /**
     * @var    string  The prefix to use with controller messages.
     * @since  1.0.0
     */
    protected $text_prefix = 'COM_EASYSTORE';

    /**
     * @var    string  The type alias for this content type.
     * @since  1.0.0
     */
    public $typeAlias = 'com_easystore.product';

    /**
     * @var    string  The name of the pivot table for brands.
     * @since  1.5.0
     */
    private const BRANDS_TABLE = '#__easystore_brands';

    /**
     * @var    string  The name of the pivot table for collections.
     * @since  1.5.0
     */
    private const COLLECTIONS_TABLE = '#__easystore_collection_product_map';

    /**
     * @var    string  The name of the pivot table for upsells.
     * @since  1.5.0
     */
    private const UPSELLS_TABLE = '#__easystore_product_upsells';


    /**
     * @var    string  The name of the pivot table for crossells.
     * @since  1.5.0
     */
    private const CROSSELLS_TABLE = '#__easystore_product_crossells';

    /**
     * Method to test whether a record can be deleted.
     *
     * @param   object  $record  A record object.
     *
     * @return  bool  True if allowed to delete the record. Defaults to the permission set in the component.
     *
     * @since   1.0.0
     */
    protected function canDelete($record)
    {
        if (empty($record->id) || (int) $record->published !== Status::TRASHED) {
            return false;
        }

        return parent::canDelete($record);
    }

    /**
     * Auto-populate the model state.
     *
     * @note Calling getState in this method will result in recursion.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function populateState()
    {
        $app = Factory::getApplication();

        // Load the User state.
        $pk = $app->getInput()->getInt('id');
        $this->setState($this->getName() . '.id', $pk);

        // Load the parameters.
        $params = ComponentHelper::getParams('com_easystore');
        $this->setState('params', $params);
    }

    /**
     * Method to get a product.
     *
     * @param   int  $pk  An optional id of the object to get, otherwise the id from the model state is used.
     *
     * @return  mixed  Product data object on success, false on failure.
     *
     * @since   1.0.0
     */
    public function getItem($pk = null)
    {
        $orm = new EasyStoreDatabaseOrm();

        if ($item = parent::getItem($pk)) {
            // Convert the metadata field to an array.
            $registry         = new Registry($item->metadata);
            $item->metadata = $registry->toArray();

            // Convert the modified date to local user time for display in the form.
            $timezone = new \DateTimeZone(Factory::getApplication()->get('offset'));

            if ((int) $item->modified) {
                $date = new Date($item->modified);
                $date->setTimezone($timezone);
                $item->modified = $date->toSql(true);
            } else {
                $item->modified = null;
            }

            if (!empty($item->id)) {
                //Get Tag Id
                $tag_id       = $this->getData($item->id, '#__easystore_product_tag_map', 'tag_id');
                $item->tag_id = ($tag_id) ? array_column($tag_id, 'tag_id') : '';
            }

            $options = $orm->setColumns(['id', 'name', 'type'])
                ->hasMany($item->id, '#__easystore_product_options', 'product_id')
                ->updateQuery(function ($query) use ($orm) {
                    $query->order($orm->quoteName('ordering') . ' ASC');
                })
                ->loadObjectList();

            if (!empty($options)) {
                foreach ($options as &$option) {
                    $option->values = $orm->setColumns(['id', 'name', 'color'])
                        ->hasMany($option->id, '#__easystore_product_option_values', 'option_id')
                        ->updateQuery(function ($query) use ($orm) {
                            $query->order($orm->quoteName('ordering') . ' ASC');
                        })
                        ->loadObjectList();
                    $option->isCollapsed = true;
                }

                unset($option);
            }

            $variants = $orm->setColumns([
                'id',
                'combination_name',
                'combination_value',
                'image_id',
                'price',
                'visibility',
                'inventory_status',
                'inventory_amount',
                'is_taxable',
                'sku',
                'weight',
                'unit',
            ])
                ->hasMany($item->id, '#__easystore_product_skus', 'product_id')
                ->updateQuery(function ($query) use ($orm) {
                    $query->order($orm->quoteName('ordering') . ' ASC');
                })
                ->loadObjectList();

            if (!empty($variants)) {
                foreach ($variants as &$variant) {
                    $variant->image = $orm->setColumns(['id', 'src', 'type', 'name'])
                        ->hasOne($variant->image_id, '#__easystore_media', 'id')
                        ->loadObject();

                    if (!empty($variant->image)) {
                        $variant->image->src = !empty($variant->image->src)
                        ? Uri::root(true) . '/' . Path::clean($variant->image->src)
                        : '';
                        $variant->image->src = $variant->image->src;
                    }

                    $variant->combination = (object) [
                        'name'  => $variant->combination_name,
                        'value' => $variant->combination_value,
                    ];

                    $variant->visibility       = (bool) $variant->visibility;
                    $variant->is_taxable       = (bool) $variant->is_taxable;
                    $variant->price            = (float) $variant->price;
                    $variant->inventory_status = (bool) $variant->inventory_status;

                    unset($variant->combination_name, $variant->combination_value, $variant->image_id);
                }

                unset($variant);
            }

            $item->product_option_variants = [
                'options'        => $options ?? [],
                'variants'       => $variants ?? [],
                'specifications' => json_decode($item->additional_data ?? '') ?? [],
                'config'         => [
                    'id'                    => $item->id,
                    'title'                 => $item->title,
                    'has_variants'          => $item->has_variants ?? false,
                    'is_tracking_inventory' => $item->is_tracking_inventory,
                ],
                'productInfo' => [
                    'regular_price'            => $item->regular_price,
                    'has_sale'                 => $item->has_sale,
                    'discount_type'            => $item->discount_type,
                    'discount_value'           => $item->discount_value,
                    'quantity'                 => $item->quantity,
                    'inventory_status'         => $item->inventory_status,
                    'is_taxable'               => $item->is_taxable,
                    'enable_out_of_stock_sell' => $item->enable_out_of_stock_sell,
                    'sku'                      => $item->sku,
                    'weight'                   => $item->weight,
                    'unit'                     => $item->unit ?? SettingsHelper::getSettings()->get('standardUnits.weight'),
                ],
            ];

            $productId = $item->id ?? 0;
            $item->collections = $this->getProductCollections($productId);
            $item->upsells = $this->getProductUpsells($productId);
            $item->crossells = $this->getProductCrossells($productId);

            /** @var CMSApplication */
            $app      = Factory::getApplication();

            $data = $app->getUserState('com_easystore.edit.product.data', []);
            
            if (!empty($data) && isset($data['product_option_variants']) && is_null($pk)) {
                $item->product_option_variants = json_decode($data['product_option_variants']);
            }

            $document = $app->getDocument();
            $document->addScriptOptions('easystore_option_variants', $item->product_option_variants);

            $document->addScriptOptions('easystore_upsells_products', $item->upsells);
            $document->addScriptOptions('easystore_crossells_products', $item->crossells);
        }

        return $item;
    }

    /**
     * Method to get the row form.
     *
     * @param   array    $data      Data for the form.
     * @param   bool  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  bool|\Joomla\CMS\Form\Form  A Form object on success, false on failure
     *
     * @since   1.0.0
     */
    public function getForm($data = [], $loadData = true)
    {
        $input = Factory::getApplication()->getInput();
        $acl   = AccessControl::create();
        $form  = $this->loadForm('com_easystore.product', 'product', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        $asset = $this->typeAlias . '.' . $input->get('id');

        if (!$acl->setAsset($asset)->canEditState()) {
            // Disable fields for display.
            $form->setFieldAttribute('ordering', 'disabled', 'true');
            $form->setFieldAttribute('published', 'disabled', 'true');

            // Disable fields while saving.
            // The controller has already verified this is a record you can edit.
            $form->setFieldAttribute('ordering', 'filter', 'unset');
            $form->setFieldAttribute('published', 'filter', 'unset');
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   1.0.0
     */
    protected function loadFormData()
    {
        /**
         * @var CMSApplication
         */
        $app = Factory::getApplication();

        // Check the session for previously entered form data.
        $data = $app->getUserState('com_easystore.edit.product.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_easystore.product', $data);

        return $data;
    }

    /**
     * Method to save the form data.
     *
     * @param   array  $data  The form data.
     *
     * @return  bool  True on success.
     *
     * @since   1.0.0
     */
    public function save($data)
    {
        /** @var \JoomShaper\Component\EasyStore\Administrator\Table\ProductTable $table */
        $table   = $this->getTable();
        $input   = Factory::getApplication()->getInput();
        $pk      = (!empty($data['id'])) ? $data['id'] : (int) $this->getState($this->getName() . '.id');
        $isNew   = true;
        $context = $this->option . '.' . $this->name;

        // Store to variant data.
        $productOptionVariant = $data['product_option_variants'];

        if (!empty($productOptionVariant) && is_string($productOptionVariant)) {
            $productOptionVariant = json_decode($productOptionVariant);
        }

        $isProductTaxable = isset($productOptionVariant->productInfo->is_taxable)
            ? $productOptionVariant->productInfo->is_taxable
            : 1;
        $data['regular_price']            = $productOptionVariant->productInfo->regular_price;
        $data['has_sale']                 = $productOptionVariant->productInfo->has_sale ?? false;
        $data['discount_type']            = $productOptionVariant->productInfo->discount_type;
        $data['discount_value']           = $productOptionVariant->productInfo->discount_value;
        $data['quantity']                 = $productOptionVariant->productInfo->quantity;
        $data['inventory_status']         = $productOptionVariant->productInfo->inventory_status ?? false;
        $data['is_taxable']               = (int) $isProductTaxable;
        $data['sku']                      = $productOptionVariant->productInfo->sku;
        $data['weight']                   = $productOptionVariant->productInfo->weight;
        $data['unit']                     = $productOptionVariant->productInfo->unit ?? SettingsHelper::getSettings()->get('standardUnits.weight');
        $data['enable_out_of_stock_sell'] = $productOptionVariant->productInfo->enable_out_of_stock_sell ?? false;

        $data['additional_data'] = json_encode($productOptionVariant->specifications) ?? [];

        $config               = $productOptionVariant->config ?? new \stdClass();
        $data['has_variants'] = isset($config->has_variants) ? (int) $config->has_variants : 0;

        $data['is_tracking_inventory'] = (int) $config->is_tracking_inventory;

        // Include the plugins for the save events.
        PluginHelper::importPlugin($this->events_map['save']);

        if (empty($data['catid'])) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_EASYSTORE_ERROR_CATEGORY_EMPTY'), 'error');
            return false;
        }

        if (isset($data['catid']) && $data['catid']) {
            $data['catid'] = $this->createNewListID($data['catid'], 'Category', $data['language']);
        }

        if (isset($data['tag_id']) && is_array($data['tag_id'])) {
            $tagIds = [];

            foreach ($data['tag_id'] as $currentTagID) {
                $tagIds[] = $this->createNewListID($currentTagID, 'Tag', $data['language']);
            }

            $data['tag_id'] = $tagIds;
        } else {
            $data['tag_id'] = [];
        }

        if (empty($data['brand_id'])) {
            $data['brand_id'] = 0;
        }

        try {
            // Load the row if saving an existing product.
            if ($pk > 0) {
                $table->load($pk);
                $isNew = false;
            }

            // Alter the title for save as copy
            if ($input->get('task') === 'save2copy') {
                $origTable = $this->getTable();
                $origTable->load($input->getInt('id'));

                if ($data['title'] === $origTable->title) {
                    list($alias, $title) = $this->generateNewTitleLocally($data['alias'], $data['title']);
                    $data['title']       = $title;
                    $data['alias']       = $alias;
                } elseif ($data['alias'] === $origTable->alias) {
                    $data['alias'] = '';
                }

                $data['published'] = 0;
            }

            // Bind the data.
            if (!$table->bind($data)) {
                $this->setError($table->getError());

                return false;
            }

            // Prepare the row for saving
            $this->prepareTable($table);

            // Check the data.
            if (!$table->check()) {
                $this->setError($table->getError());

                return false;
            }

            // Trigger the before save event.
            $result = Factory::getApplication()->triggerEvent($this->event_before_save, [$context, $table, $isNew, $data]);

            if (in_array(false, $result, true)) {
                $this->setError($table->getError());

                return false;
            }

            // Store the data.
            if (!$table->store()) {
                $this->setError($table->getError());
                return false;
            }

            $imageIdMapping = [];

            if ($table->id) {
                if ($isNew) {
                    //Insert tag ids
                    if (!empty($data['tag_id'])) {
                        $this->insertData($table->id, 'tag_id', $data['tag_id'], '#__easystore_product_tag_map');
                    }

                    $clientId = $productOptionVariant->config->client_id;

                    $imageIdMapping = $this->moveTemporaryImages($table->id, $clientId);

                    if ($imageIdMapping === false) {
                        $imageIdMapping = [];
                        $this->setError(Text::_('COM_EASYSTORE_ERROR_MOVING_TEMPORARY_IMAGES'));

                        return false;
                    }
                } else {
                    // Update tag ids
                    $this->updateData($table->id, 'tag_id', $data['tag_id'], '#__easystore_product_tag_map');
                }

                // copy options & variants data for save as copy
                if ($input->get('task') === 'save2copy') {
                    $origTable = $this->getTable();
                    $origTable->load($input->getInt('id'));

                    $origTableId = $origTable->id;
                    $newTableId  = $table->id;

                    $this->copyOptionsAndVariants($origTableId, $newTableId);
                    $this->copyImagesToNewProduct($origTableId, $newTableId);
                } else {
                    $productOption   = $productOptionVariant->options ?? [];
                    $productVariants = $productOptionVariant->variants ?? [];

                    $this->manageProductOptions($productOption, $table->id);
                    $this->manageProductVariants($productVariants, $table->id, $imageIdMapping);
                }
            }

            // Store the collections to the collection_product_map pivot table
            if (!empty($table->id)) {
                $collections = $data['collections'] ?? [];

                if (empty($collections) || !is_array($collections)) {
                    $collections = [];
                }

                $this->storeProductCollections($table->id, $collections);

                // @todo exclude current product id from the upsells and crossells
                $upsells = $data['upsells'] ?? [];

                if (is_string($upsells)) {
                    $upsells = json_decode($upsells, true);
                }

                if (empty($upsells) || !is_array($upsells)) {
                    $upsells = [];
                }

                // Exclude current product id from the upsells
                $upsells = array_filter($upsells, function ($item) use ($table) {
                    return $item !== $table->id;
                });

                // Store the upsells to the product_upsells pivot table
                $this->storeProductUpsells($table->id, $upsells);


                $crossells = $data['crossells'] ?? [];

                if (is_string($crossells)) {
                    $crossells = json_decode($crossells, true);
                }

                if (empty($crossells) || !is_array($crossells)) {
                    $crossells = [];
                }

                // Exclude current product id from the crossells
                $crossells = array_filter($crossells, function ($item) use ($table) {
                    return $item !== $table->id;
                });

                // Store the crossells to the product_crossells pivot table
                $this->storeProductCrossells($table->id, $crossells);
            }

            // Trigger the after save event.
            Factory::getApplication()->triggerEvent($this->event_after_save, [$context, $table, $isNew]);
        } catch (\Exception $e) {
            $this->setError($e->getMessage());

            return false;
        }

        $this->setState($this->getName() . '.id', $table->id);
        $this->setState($this->getName() . '.new', $isNew);

        // Clear the cache
        $this->cleanCache();

        return true;
    }

    private function moveTemporaryImages($productId, $clientId)
    {
        $columns = ['id', 'name', 'type', 'is_featured', 'width', 'height', 'src', 'alt_text', 'ordering', 'created', 'created_by', 'modified_by'];
        $orm     = new EasyStoreDatabaseOrm();

        $temporaryImages = $orm->setColumns($columns)
            ->hasMany($clientId, '#__easystore_temp_media', 'client_id')
            ->loadObjectList();

        $columns[0] = 'product_id';

        $mediaParams = ComponentHelper::getParams('com_media');
        $path        = $mediaParams->get('file_path', 'images');
        $imagePath   = $path . '/easystore/product-' . $productId;
        $tempPath    = $path . '/easystore/tmp/' . $clientId;

        $idMapping = [];

        if (!empty($temporaryImages)) {
            foreach ($temporaryImages as $image) {
                $db    = Factory::getContainer()->get(DatabaseInterface::class);
                $query = $db->getQuery(true);
                $query->insert($db->quoteName('#__easystore_media'))->columns($db->quoteName($columns));

                $src = $imagePath . '/' . $image->name;

                $item = [
                    $productId,
                    $db->quote($image->name),
                    $db->quote($image->type),
                    $db->quote($image->is_featured),
                    $image->width,
                    $image->height,
                    $db->quote($src),
                    $db->quote($image->alt_text),
                    $image->ordering,
                    $db->quote(Factory::getDate('now')),
                    $image->created_by,
                    $image->modified_by,
                ];

                $query->values(implode(',', $item));
                $db->setQuery($query);

                try {
                    $db->execute();

                    $idMapping[$image->id] = $db->insertid();

                    if (file_exists(JPATH_ROOT . '/' . Path::clean($tempPath))) {
                        Folder::move(JPATH_ROOT . '/' . Path::clean($tempPath), JPATH_ROOT . '/' . Path::clean($imagePath));
                    }

                    $query->clear();
                    $query->delete($db->quoteName('#__easystore_temp_media'))
                        ->where($db->quoteName('client_id') . ' = ' . $db->quote($clientId));
                    $db->setQuery($query);

                    $db->execute();
                } catch (\Exception $e) {
                    return false;
                }
            }
        }

        return $idMapping;
    }

    /**
     * Function to copy target product images to new product
     *
     * @param int $origTableId
     * @param int $newTableId
     * @return void
     */
    private function copyImagesToNewProduct($origTableId, $newTableId)
    {
        $columns = ['name', 'type', 'is_featured', 'width', 'height', 'src', 'alt_text', 'ordering'];
        $orm     = new EasyStoreDatabaseOrm();

        $toCopyImages = $orm->setColumns($columns)
            ->hasMany($origTableId, '#__easystore_media', 'product_id')
            ->loadObjectList();

        $mediaParams = ComponentHelper::getParams('com_media');
        $path        = $mediaParams->get('file_path', 'images');

        if (!empty($toCopyImages)) {
            $db         = $this->getDatabase();
            $oldSrcPath = '';
            $newSrcPath = $path . '/easystore/product-' . $newTableId;

            foreach ($toCopyImages as &$image) {
                $date               = Factory::getDate();
                $image->product_id  = $newTableId;
                $srcChunks          = explode('/', $image->src);
                $generatedOldPath   = $path . '/easystore/' . $srcChunks[2];
                $oldSrcPath         = empty($oldSrcPath) ? $generatedOldPath : $oldSrcPath;
                $newSrc             = $path . '/easystore/product-' . $newTableId . '/' . $srcChunks[3];
                $image->src         = $newSrc;
                $image->created     = $date->toSql();
                $image->created_by  = Factory::getApplication()->getIdentity()->id;
                $image->modified_by = Factory::getApplication()->getIdentity()->id;

                $db->insertObject('#__easystore_media', $image);
            }

            if (file_exists(JPATH_ROOT . '/' . Path::clean($oldSrcPath))) {
                Folder::copy(JPATH_ROOT . '/' . Path::clean($oldSrcPath), JPATH_ROOT . '/' . Path::clean($newSrcPath));
            }

            unset($image);
        }
    }

    /**
     * Method to change the title & alias.
     *
     * @param   string   $alias     The alias.
     * @param   string   $title     The title.
     *
     * @return  array  Contains the modified title and alias.
     *
     * @since   1.0.0
     */
    private function generateNewTitleLocally($alias, $title)
    {
        // Alter the title & alias
        $table = $this->getTable();

        while ($table->load(['alias' => $alias])) {
            $title = StringHelper::increment($title);
            $alias = StringHelper::increment($alias, 'dash');
        }
        return [$alias, $title];
    }

    /**
     * Method to validate the form data.
     *
     * @param   Form    $form   The form to validate against.
     * @param   array   $data   The data to validate.
     * @param   string  $group  The name of the field group to validate.
     *
     * @return  array|bool  Array of filtered data if valid, false otherwise.
     *
     * @see     JFormRule
     * @see     JFilterInput
     * @since   1.0.0
     */
    public function validate($form, $data, $group = null)
    {
        $regularPrice  = $data['regular_price'] ?? null;
        $discountValue = $data['discount_value'] ?? null;

        // Validate regular_price field
        if (!empty($regularPrice) && $regularPrice) {
            list($regularPricePrecision, $regularPriceScale) = $this->getPrecisionScaleForDecimal($form->getField('regular_price')->getAttribute('validation'));

            if (!$this->validateDecimalFormat($regularPrice, $regularPricePrecision, $regularPriceScale)) {
                Factory::getApplication()->enqueueMessage(Text::sprintf('COM_EASYSTORE_PRODUCT_FIELD_REGULAR_PRICE_ERROR_MESSAGE', $regularPricePrecision, $regularPriceScale), 'error');

                return false;
            }
        }

        // Validate discount_value field
        if (!empty($discountValue) && $discountValue) {
            list($discountValuePrecision, $discountValueScale) = $this->getPrecisionScaleForDecimal($form->getField('discount_value')->getAttribute('validation'));

            if (!$this->validateDecimalFormat($discountValue, $discountValuePrecision, $discountValueScale)) {
                Factory::getApplication()->enqueueMessage(Text::sprintf('COM_EASYSTORE_PRODUCT_FIELD_DISCOUNT_VALUE_ERROR_MESSAGE', $discountValuePrecision, $discountValueScale), 'error');

                return false;
            }
        }

        return parent::validate($form, $data, $group);
    }

    /**
     * Validate decimal formate
     *
     * @param   string $dataField    Value of the data field
     * @param   int    $precision    an integer representing the total number of digits allowed in a column.
     * @param   int    $scale        an integer value that represents the number of decimal places.
     *
     * @return int|bool
     *
     * @since  1.0.0
     */
    public function validateDecimalFormat($dataField, $precision, $scale)
    {
        if ($precision && $scale) {
            $pattern = "/^-?\d{1,$precision}(\.\d{0,$scale})?$/";

            return preg_match($pattern, $dataField);
        }

        return false;
    }

    /**
     * get precision and scale from decimal format
     *
     * @param   string $value     data type of the data field
     * @return  array|bool
     * @since   1.0.0
     */
    public function getPrecisionScaleForDecimal($value)
    {
        $pattern = '/\((\d+),(\d+)\)/';

        preg_match($pattern, $value, $matches);

        if ($matches) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        return false;
    }

    /**
     * Insert data to database
     *
     * @param  int         $productId    Product ID
     * @param  string      $columnName   Column name of a database
     * @param  int|string  $ids          Category / Tag ID(s)
     * @param  string      $table        Table name
     * @return void
     * @since  1.0.0
     */
    public function insertData($productId, $columnName, $ids, $table)
    {
        $app = Factory::getApplication();
        $db  = $this->getDatabase();

        if (!empty($ids)) {
            $dataTypes = [
                ParameterType::INTEGER,
                ParameterType::INTEGER,
            ];

            $query = $db->getQuery(true)
                ->insert($db->quoteName($table))
                ->columns($db->quoteName([
                    'product_id',
                    $columnName,
                ]));

            if (is_array($ids)) {
                foreach ($ids as $value) {
                    $query->values(implode(',', $query->bindArray([
                        $productId,
                        $value,
                    ], $dataTypes)));
                }
            } elseif ($ids = (int) $ids) {
                $query->values(':proId,:insertedId')
                    ->bind(':proId', $productId, ParameterType::INTEGER)
                    ->bind(':insertedId', $ids, ParameterType::INTEGER);
            }

            $db->setQuery($query);

            try {
                $db->execute();
            } catch (\RuntimeException $e) {
                $app->enqueueMessage($e->getMessage(), 'error');
            }
        }
    }

    /**
     * Update data in database
     *
     * @param  int         $productId    Product ID
     * @param  string      $columnName   Column name of a database
     * @param  int|string  $ids          Category / Tag ID(s)
     * @param  string      $table        Table name
     * @return mixed
     * @since  1.0.0
     */
    public function updateData($productId, $columnName, $ids, $table)
    {
        $db  = $this->getDatabase();
        $app = Factory::getApplication();

        $query = $db->getQuery(true)
            ->delete($db->quoteName($table))
            ->where($db->quoteName('product_id') . ' = ' . $productId);
        $db->setQuery($query);
        try {
            $db->execute();
            if (!empty($ids)) {
                $this->insertData($productId, $columnName, $ids, $table);
            }
        } catch (\RuntimeException $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Get data from database
     * If the $column variable contains an alias, it will be represented using a dot (.) separator.
     * Ex: id.cat_id where id is the column name and cat_id is the alias.
     *
     * @param  int              $productId      Product ID
     * @param  string           $table          Table name
     * @param  string|array     $column         Column, may have single string or array of string.
     * @param  string           $conditionName  The column name used for checking the condition.
     * @param  string           $order          The Order value
     * @param  string           $orderDirection The Order direction ASC or DESC
     * @return array|bool
     * @since  1.0.0
     */
    public function getData($productId, $table, $column, $conditionName = 'product_id', $order = null, $orderDirection = 'ASC')
    {
        $db  = Factory::getContainer()->get(DatabaseInterface::class);
        $app = Factory::getApplication();

        if (is_array($column)) {
            foreach ($column as &$singleColumn) {
                // Checking if the column has an alias
                $hasAlias     = EasyStoreHelper::hasAlias($singleColumn);
                $singleColumn = ($hasAlias) ? $db->quoteName($hasAlias[0]) . " as $hasAlias[1]" : $db->quoteName($singleColumn);
            }
            $query = $db->getQuery(true)
                ->select($column)
                ->from($db->quoteName($table))
                ->where($db->quoteName($conditionName) . ' = ' . $productId);
        } else {
            $query = $db->getQuery(true)
                ->select($db->quoteName($column))
                ->from($db->quoteName($table))
                ->where($db->quoteName($conditionName) . ' = ' . $productId);
        }
        if (!is_null($order)) {
            $query->order($db->quoteName($order) . ' ' . $orderDirection);
        }

        $db->setQuery($query);
        try {
            $result = $db->loadObjectList();
            return $result;
        } catch (\RuntimeException $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Function to get Product data with single image
     *
     * @param int $id
     * @param int $orderId
     * @return object
     *
     * @since   1.0.0
     */
    public static function getProductDataWithImage(int $id, int $orderId = 0) //@todo we will remove this method
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $selectArray = [
            $db->quoteName('a.id'),
            $db->quoteName('a.title'),
            $db->quoteName('a.regular_price'),
            $db->quoteName('a.has_sale'),
            $db->quoteName('a.discount_type'),
            $db->quoteName('a.discount_value'),
            $db->quoteName('a.is_tracking_inventory'),
            $db->quoteName('a.inventory_status'),
            $db->quoteName('a.enable_out_of_stock_sell'),
            $db->quoteName('a.quantity'),
            $db->quoteName('a.is_taxable'),
        ];

        if (!empty($orderId)) {
            $selectArray[] = $db->quoteName('pm.quantity');
            $selectArray[] = $db->quoteName('pm.price');
        }

        $query->select($selectArray);
        $query->from($db->quoteName('#__easystore_products', 'a'));
        if (!empty($orderId)) {
            $query->join('RIGHT', $db->quoteName('#__easystore_order_product_map', 'pm'), $db->quoteName('a.id') . ' = ' . $db->quoteName('pm.product_id'))
                ->where($db->quoteName('pm.order_id') . ' = ' . $orderId);
        }
        $query->where($db->quoteName('a.id') . ' = ' . $id);

        // Get the options.
        $db->setQuery($query);

        try {
            $result    = $db->loadObject();
            $mediaData = self::getMedia($id);

            if (!empty($result)) {
                $result->image = $mediaData->thumbnail;
            }
        } catch (\RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }

        return $result;
    }

    /**
     * Get the Price of a product calculation its discount values
     *
     * @param int $id
     * @return float
     *
     * @since   1.0.0
     */
    public static function getProductCurrentPrice(int $id)
    {
        $db  = Factory::getContainer()->get(DatabaseInterface::class);
        $app = Factory::getApplication();

        $query = $db->getQuery(true)
            ->select([$db->quoteName('regular_price'), $db->quoteName('has_sale'), $db->quoteName('discount_type'), $db->quoteName('discount_value')])
            ->from($db->quoteName('#__easystore_products'))
            ->where($db->quoteName('id') . ' = ' . $id);

        $db->setQuery($query);
        try {
            $product = $db->loadObject();

            if ($product->has_sale) {
                if ($product->discount_type === 'percent') {
                    $discountedProductPrice = (float) $product->regular_price - ((float) $product->regular_price * (float) $product->discount_value) / 100;
                } else {
                    $discountedProductPrice = (float) $product->regular_price - (float) $product->discount_value;
                }

                $productPrice = $discountedProductPrice;
            } else {
                $productPrice = $product->regular_price;
            }

            return $productPrice;
        } catch (\RuntimeException $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Get product review data
     *
     * @param int $id
     * @return object
     *
     * @since   1.0.0
     */

    public static function getReviewData(int $id)
    {
        $db  = Factory::getContainer()->get(DatabaseInterface::class);
        $app = Factory::getApplication();

        $query = $db->getQuery(true)
            ->select(['COUNT(' . $db->quoteName('id') . ')', 'SUM(' . $db->quoteName('rating') . ')'])
            ->from($db->quoteName('#__easystore_reviews'))
            ->where($db->quoteName('product_id') . ' = ' . $id)
            ->where($db->quoteName('published') . ' = 1');

        $db->setQuery($query);
        try {
            $result        = $db->loadRow();
            $reviewCount   = $result[0];
            $totalRating   = $result[1];
            $averageRating = $reviewCount > 0 ? round($totalRating / $reviewCount, 1) : 0;

            return (object) [
                'count'  => $reviewCount,
                'rating' => $averageRating,
            ];
        } catch (\RuntimeException $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Allows preprocessing of the Form object.
     *
     * @param   Form    $form   The form object
     * @param   array   $data   The data to be merged into the form object
     *
     * @return  void
     *
     * @since    1.0.0
     */
    protected function preprocessForm(Form $form, $data, $group = '')
    {
        if ($this->canCreateListItems()) {
            $form->setFieldAttribute('catid', 'allowAdd', 'true');
            $form->setFieldAttribute('tag_id', 'allowAdd', 'true');

            // Add a prefix for categories and tags created on the fly.
            $form->setFieldAttribute('catid', 'customPrefix', '#new#');
            $form->setFieldAttribute('tag_id', 'customPrefix', '#new#');
        }

        parent::preprocessForm($form, $data, $group);
    }

    /**
     * Is the user allowed to create an category or tags on the fly?
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    private function canCreateListItems()
    {
        $acl = AccessControl::create();
        return $acl->canCreate();
    }

    /**
     *  Create new list item id if provided (category/tag)
     *
     * @param  int    $listID        ID of list item (category/tag).
     * @param  string $modelName     The name of the model
     * @param  string $language      Current Language
     * @return int
     * @since  1.0.0
     */
    public function createNewListID($listID, $modelName, $language)
    {
        $createListItems = true;
        // If category or tag ID is provided, check if it's valid.
        if (is_numeric($listID) && $listID) {
            $createListItems = !EasyStoreProductHelper::validateListId($listID, $modelName);
        }

        // Save New Category or Tag
        if ($createListItems && $this->canCreateListItems()) {
            $newItem = [
                // Remove #new# prefix, if exists.
                'title'     => strpos($listID, '#new#') === 0 ? substr($listID, 5) : $listID,
                'language'  => $language,
                'published' => 1,
            ];

            /**
             * @var CMSApplication
             */
            $app = Factory::getApplication();

            /**
             * @var ComponentInterface
             */
            $bootComponent = $app->bootComponent('com_easystore');

            $model = $bootComponent->getMVCFactory()
                ->createModel($modelName, 'Administrator', ['ignore_request' => true]);

            // Create new category/tag.
            if (!$model->save($newItem)) {
                $this->setError($model->getError());
                return false;
            }

            // Get the Category / Tag ID.
            $listID = $model->getState(strtolower($modelName . '.id'));
            return $listID;
        }

        return $listID;
    }

    /**
     * Function to get products with variants
     *
     * @param object $params
     * @return object
     */
    public function getProductsWithVariants(object $params)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $columns = ['id', 'title', 'sku', 'catid', 'is_tracking_inventory', 'quantity', 'regular_price', 'published', 'inventory_status', 'has_variants', 'is_taxable', 'weight', 'unit'];

        $query->select($db->quoteName($columns))
            ->select($db->quoteName('quantity','available_quantity'))
            ->from($db->quoteName('#__easystore_products'))
            ->where($db->quoteName('published') . " = 1");

        if (!empty($params->search)) {
            $search = preg_replace("@\s+@", ' ', $params->search);
            $search = explode(' ', $search);
            $search = array_filter($search, function ($word) {
                return !empty($word);
            });

            $search = implode('|', $search);
            $query->where($db->quoteName('title') . ' ' . $query->regexp($db->quote($search)));
        }

        $db->setQuery($query);

        $fullQuery = $db->getQuery(true);
        $fullQuery = $query->__toString();

        if (!empty($params->limit)) {
            $query->setLimit($params->limit, $params->offset);
        }

        $db->setQuery($query);

        $products = [];

        try {
            $products = $db->loadObjectList() ?? [];
        } catch (\Exception $e) {
            $products = [];
        }

        $types = [
            'is_tracking_inventory' => 'boolean',
            'regular_price'         => 'float',
            'inventory_status'      => 'boolean',
            'has_variants'          => 'boolean',
        ];

        $products = EasyStoreHelper::typeCorrection($products, $types);

        $orm = new EasyStoreDatabaseOrm();

        if (!empty($products)) {
            foreach ($products as &$product) {
                $product->image = $orm->setColumns(['src'])
                    ->hasOne($product->id, '#__easystore_media', 'product_id')
                    ->updateQuery(function ($query) use ($orm) {
                        $query->where($orm->quoteName('is_featured') . ' = 1');
                    })->loadResult();

                if (!empty($product->image)) {
                    $product->image = Uri::root(true) . '/' . Path::clean($product->image);
                }

                $product->is_gift_card = false;

                $options = $orm->setColumns(['id', 'name', 'type'])
                    ->hasMany($product->id, '#__easystore_product_options', 'product_id')
                    ->updateQuery(function ($query) use ($orm) {
                        $query->order($orm->quoteName('ordering') . ' ASC');
                    })
                    ->loadObjectList();

                if (!empty($options)) {
                    foreach ($options as &$option) {
                        $option->values = $orm->setColumns(['id', 'name', 'color'])
                            ->hasMany($option->id, '#__easystore_product_option_values', 'option_id')
                            ->loadObjectList();
                        $option->isCollapsed = true;
                    }

                    unset($option);
                }

                $variants = $orm->useRawColumns(true)->setColumns([
                    'id',
                    'combination_name',
                    'combination_value',
                    'image_id',
                    'price',
                    'visibility',
                    'inventory_status',
                    'inventory_amount',
                    'sku',
                    'weight',
                    'unit',
                    'is_taxable AS is_taxable_variant'
                ])
                    ->hasMany($product->id, '#__easystore_product_skus', 'product_id')
                    ->updateQuery(function ($query) use ($orm) {
                        $query->order($orm->quoteName('ordering') . ' ASC');
                    })
                    ->loadObjectList();

                if (!empty($variants)) {
                    foreach ($variants as &$variant) {
                        $variant->image = $orm->setColumns(['id', 'src'])
                            ->hasOne($variant->image_id, '#__easystore_media', 'id')
                            ->loadObject();

                        if (!empty($variant->image)) {
                            $variant->image->src = Uri::root(true) . '/' . Path::clean($variant->image->src);
                        }

                        $variant->combination = (object) [
                            'name'  => $variant->combination_name,
                            'value' => $variant->combination_value,
                        ];

                        $variant->visibility       = (bool) $variant->visibility;
                        $variant->price            = (float) $variant->price;
                        $variant->inventory_status = (bool) $variant->inventory_status;
                        $variant->options          = SiteEasyStoreHelper::detectProductOptionFromCombination(
                            SiteEasyStoreHelper::getProductOptionsById($product->id),
                            $variant->combination_value
                        );

                        unset($variant->combination_name, $variant->combination_value, $variant->image_id);
                    }

                    unset($variant);
                }

                $variants = EasyStoreHelper::typeCorrection($variants, ['inventory_amount' => 'integer', 'weight' => 'float']);

                $product->product_variants = $variants ?? [];
                $product->product_option   = $options ?? [];
            }

            unset($product);
        }

        $db->setQuery($fullQuery);
        $db->execute();
        $allItems = $db->getNumRows();

        $response             = new \stdClass();
        $response->totalItems = $allItems;
        $response->totalPages = ceil($allItems / $params->limit);
        $response->results    = $products;

        return $response;
    }

    /**
     * Method to toggle the featured setting of products.
     *
     * @param   array        $pks           The ids of the items to toggle.
     * @param   int      $value         The value to toggle to.
     *
     * @return  bool  True on success.
     */
    public function featured($pks, $value = 0)
    {
        // Sanitize the ids.
        $pks   = (array) $pks;
        $pks   = ArrayHelper::toInteger($pks);
        $value = (int) $value;

        if (empty($pks)) {
            $this->setError(Text::_('COM_EASYSTORE_NO_ITEM_SELECTED'));

            return false;
        }

        try {
            $db    = $this->getDatabase();
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__easystore_products'))
                ->set($db->quoteName('featured') . ' = :featured')
                ->whereIn($db->quoteName('id'), $pks)
                ->bind(':featured', $value, ParameterType::INTEGER);
            $db->setQuery($query);
            $db->execute();
        } catch (Exception $error) {
            $this->setError($error->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Function to check if a product exists on any order by id
     * @param int $productId
     * @return bool
     */
    public static function isProductExistsInOrder(int $productId)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select($db->quoteName('order_id'))
            ->from($db->quoteName('#__easystore_order_product_map'))
            ->where($db->quoteName('product_id') . ' = ' . $productId);

        $db->setQuery($query);
        $db->execute();
        $rowCount = $db->getNumRows();

        return $rowCount > 0 ? true : false;
    }

    /**
     * Function for saving the imported products
     * @param object $product
     * @return int|bool
     */
    public function saveProductForImport($product)
    {
        /** @var \JoomShaper\Component\EasyStore\Administrator\Table\ProductTable $table */
        $table = $this->getTable();
        $isNew = true;

        list($title, $alias)  = $this->generateNewTitleAlias($product->title, $product->alias);

        $data['title']                    = $title;
        $data['alias']                    = $alias;
        $data['description']              = $product->description;
        $data['catid']                    = $product->catid;
        $data['brand_id']                 = $product->brand_id;
        $data['weight']                   = $product->weight;
        $data['unit']                     = $product->unit;
        $data['dimension']                = $product->dimension;
        $data['regular_price']            = (float) $product->regular_price;
        $data['is_taxable']               = (int) $product->is_taxable;
        $data['has_sale']                 = (int) $product->has_sale;
        $data['featured']                 = (int) $product->featured;
        $data['is_tracking_inventory']    = (int) $product->is_tracking_inventory;
        $data['inventory_status']         = (int) $product->inventory_status;
        $data['enable_out_of_stock_sell'] = (int) $product->enable_out_of_stock_sell;
        $data['quantity']                 = (int) $product->quantity;
        $data['sku']                      = $product->sku;
        $data['discount_type']            = $product->discount_type;
        $data['discount_value']           = (float) $product->discount_value;
        $data['published']                = (int) $product->published;
        $data['additional_data']          = json_encode($product->specifications) ?? [];
        $data['has_variants']             = (int) $product->has_variants;

        if (empty($data['catid'])) {
            $this->setError(Text::_('COM_EASYSTORE_ERROR_CATEGORY_EMPTY'));
            return false;
        }

        $data['tag_id'] = [];

        try {
            // Bind the data.
            if (!$table->bind($data)) {
                $this->setError($table->getError());

                return false;
            }

            // Prepare the row for saving
            $this->prepareTable($table);

            // Check the data.
            if (!$table->check()) {
                $this->setError($table->getError());

                return false;
            }

            // Store the data.
            if (!$table->store()) {
                $this->setError($table->getError());
                return false;
            }
        } catch (\Exception $e) {
            $this->setError($e->getMessage());

            return false;
        }

        $this->setState($this->getName() . '.id', $table->id);
        $this->setState($this->getName() . '.new', $isNew);

        // Clear the cache
        $this->cleanCache();

        return $table->id;
    }

    /**
     * Method to change the title & alias.
     *
     * @param   string   $title       The product title.
     * @param   string   $alias       The product alias.
     *
     * @return  array  Contains the modified title and alias.
     *
     * @since   1.2.4
     */
    protected function generateNewTitleAlias($title, $alias)
    {
        // Alter the title & alias
        $table      = $this->getTable();
        $aliasField = $table->getColumnAlias('alias');
        $titleField = $table->getColumnAlias('title');

        while ($table->load([$aliasField => $alias])) {
            if ($title === $table->$titleField) {
                $title = StringHelper::increment($title);
            }

            $alias = StringHelper::increment($alias, 'dash');
        }

        return [$title, $alias];
    }

    /**
     * Get collection IDs associated with a product.
     *
     * @param int $productId The ID of the product.
     *
     * @return array An array of collection IDs.
     *
     * @since 1.5.0
     */
    public function getProductCollections(int $productId)
    {
        return $this->getRelatedRecords($productId, 'product', 'collection', self::COLLECTIONS_TABLE);
    }


    /**
     * Get up sells IDs associated with a product.
     *
     * @param int $productId The ID of the product.
     *
     * @return array An array of up sells product IDs.
     *
     * @since 1.5.0
     */
    public function getProductUpsells(int $productId)
    {
        $products = $this->getRelatedRecords($productId, 'product', 'upsell', self::UPSELLS_TABLE);

        return $this->getProductDetails($products);
    }

    /**
     * Get cross sells IDs associated with a product.
     *
     * @param int $productId The ID of the product.
     *
     * @return array An array of cross sells product IDs.
     *
     * @since 1.5.0
     */
    public function getProductCrossells(int $productId)
    {
        $products = $this->getRelatedRecords($productId, 'product', 'crossell', self::CROSSELLS_TABLE);

        return $this->getProductDetails($products);
    }

    /**
     * Store product-collection relationships.
     *
     * @param int $productId The ID of the product.
     * @param array $collectionIds An array of collection IDs.
     *
     * @return bool True if the operation was successful, false otherwise.
     *
     * @since 1.5.0
     */
    public function storeProductCollections(int $productId, array $collectionIds)
    {
        return $this->storeRelationships(
            $productId,
            $collectionIds,
            'product',
            'collection',
            self::COLLECTIONS_TABLE,
            false // collection_id comes first in the table
        );
    }

    /**
     * Store product-upsells relationships.
     *
     * @param int $productId The ID of the product.
     * @param array $upsellsIds An array of product IDs.
     *
     * @return bool True if the operation was successful, false otherwise.
     *
     * @since 1.5.0
     */
    public function storeProductUpsells(int $productId, array $upsellsIds)
    {
        return $this->storeRelationships(
            $productId,
            $upsellsIds,
            'product',
            'upsell',
            self::UPSELLS_TABLE,
            false // upsell_id comes first in the table
        );
    }

    /**
     * Store product-crossells relationships.
     *
     * @param int $productId The ID of the product.
     * @param array $crossellsIds An array of product IDs.
     *
     * @return bool True if the operation was successful, false otherwise.
     *
     * @since 1.5.0
     */
    public function storeProductCrossells(int $productId, array $crossellsIds)
    {
        return $this->storeRelationships(
            $productId,
            $crossellsIds,
            'product',
            'crossell',
            self::CROSSELLS_TABLE,
            false // crossell_id comes first in the table
        );
    }
}