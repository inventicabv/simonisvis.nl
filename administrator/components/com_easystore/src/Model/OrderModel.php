<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\Filesystem\Path;
use JoomShaper\Component\EasyStore\Administrator\Checkout\OrderManager;
use JoomShaper\Component\EasyStore\Administrator\Concerns\Taxable;
use JoomShaper\Component\EasyStore\Administrator\Constants\Status;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Administrator\Traits\Notifiable;
use JoomShaper\Component\EasyStore\Site\Model\CouponModel as SiteCouponModel;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper as SiteEasyStoreHelper;


// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * EasyStore Component Order Model
 *
 * @since  1.0.0
 */
class OrderModel extends AdminModel
{
    use Taxable;
    use Notifiable;

    /**
     * @var    string  The prefix to use with controller messages.
     * @since  1.0.0
     */
    protected $text_prefix = 'COM_EASYSTORE';

    /**
     * The type alias for the order model
     *
     * @var string
     * @since 1.3.0
     */
    public $typeAlias = 'com_easystore.order';

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
     * Method to get a order.
     *
     * @param   int  $pk  An optional id of the object to get, otherwise the id from the model state is used.
     *
     * @return  mixed  Order data object on success, false on failure.
     *
     * @since   1.0.0
     */
    public function getItem($pk = null)
    {
        if ($result = parent::getItem($pk)) {
            if (!empty($result->shipping_type)) {
                $result->shipping_type = (float) $result->shipping_value . ':' . $result->shipping_type;
            }

            // Convert the modified date to local user time for display in the form.
            $tz = new \DateTimeZone(Factory::getApplication()->get('offset'));

            if ((int) $result->modified) {
                $date = new Date($result->modified);
                $date->setTimezone($tz);
                $result->modified = $date->toSql(true);
            } else {
                $result->modified = null;
            }
        }

        return $result;
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

        // Get the form.
        $form = $this->loadForm('com_easystore.order', 'order', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        $asset = $this->typeAlias . '.' . $input->get('id');

        if (!$acl->setAsset($asset)->canEditState()) {
            // Disable fields for display.
            $form->setFieldAttribute('ordering', 'disabled', 'true');

            // Disable fields while saving.
            // The controller has already verified this is a record you can edit.
            $form->setFieldAttribute('ordering', 'filter', 'unset');
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
        $application = Factory::getApplication();

        // Check the session for previously entered form data.
        $data = $application->getUserState('com_easystore.edit.order.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_easystore.order', $data);

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
        /** @var \JoomShaper\Component\EasyStore\Administrator\Table\OrderTable $table */
        $table   = $this->getTable();
        $input   = Factory::getApplication()->getInput();
        $pk      = (!empty($data['id'])) ? $data['id'] : (int) $this->getState($this->getName() . '.id');
        $isNew   = true;
        $context = $this->option . '.' . $this->name;

        $productIds        = $input->get('product_id');
        $productQuantities = $input->get('product_quantity');

        // Include the plugins for the save events.
        PluginHelper::importPlugin($this->events_map['save']);

        try {
            // Load the row if saving an existing order.
            if ($pk > 0) {
                $table->load($pk);
                $isNew = false;
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

            if ($table->id) {
                $this->deletePreviousOrderData($table->id);

                foreach ($productIds as $productId) {
                    $productPrice             = ProductModel::getProductCurrentPrice($productId);
                    $productObject            = new \stdClass();
                    $productObject->productId = $productId;
                    $productObject->quantity  = $productQuantities[$productId][0];
                    $productObject->price     = $productPrice;

                    $this->storeOrderProduct($table->id, $productObject);
                }
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

    /**
     * Function to delete products on an order by orderId
     *
     * @param int $orderId
     * @return void
     *
     * @since   1.0.0
     */
    public function deletePreviousOrderData(int $orderId)
    {
        $db  = Factory::getContainer()->get(DatabaseInterface::class);
        $app = Factory::getApplication();

        // Delete previous data
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__easystore_order_product_map'));
        $query->where($db->quoteName('order_id') . ' = ' . $orderId);

        $db->setQuery($query);

        try {
            $db->execute();
        } catch (\RuntimeException $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Function to store products of an order
     *
     * @param int $orderId
     * @param object $product
     * @return void
     *
     * @since   1.0.0
     */
    public function storeOrderProduct(int $orderId, object $product)
    {
        $db  = Factory::getContainer()->get(DatabaseInterface::class);
        $app = Factory::getApplication();

        $query = $db->getQuery(true);
        // Insert columns.
        $columns = [
            'order_id',
            'product_id',
            'quantity',
            'price',
        ];

        // Insert values.
        $values = [
            $orderId,
            $db->quote($product->productId),
            $db->quote($product->quantity),
            $db->quote($product->price),
        ];

        // Prepare the insert query.
        $query
            ->insert($db->quoteName('#__easystore_order_product_map'))
            ->columns($db->quoteName($columns))
            ->values(implode(',', $values));

        // Set the query using our newly populated query object and execute it.
        $db->setQuery($query);

        try {
            $db->execute();
        } catch (\RuntimeException $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Function to get listed productIds by orderId
     *
     * @param int $orderId
     * @return object
     *
     * @since   1.0.0
     */
    public static function getProductsByOrderId(int $orderId)
    {
        $db  = Factory::getContainer()->get(DatabaseInterface::class);
        $app = Factory::getApplication();

        $query = $db->getQuery(true);

        $query->select($db->quoteName('product_id'))
            ->from($db->quoteName('#__easystore_order_product_map'))
            ->where($db->quoteName('order_id') . ' = ' . $orderId);

        $db->setQuery($query);

        try {
            $productIds = $db->loadObjectList();

            return $productIds;
        } catch (\RuntimeException $e) {
            $app->enqueueMessage($e->getMessage(), 'error');

            return false;
        }
    }

    /**
     * Function to check if Tracking Id is unique
     *
     * @param string $trackingId
     * @return bool
     */
    public static function isTrackingIdUnique($trackingId)
    {
        $db  = Factory::getContainer()->get(DatabaseInterface::class);
        $app = Factory::getApplication();

        $query = $db->getQuery(true);

        $query->select($db->quoteName('id'))
            ->from($db->quoteName('#__easystore_orders'));

        $db->setQuery($query);

        try {
            $result = $db->loadObject();

            if ($result != null) {
                return false;
            }

            return true;
        } catch (\RuntimeException $e) {
            $app->enqueueMessage($e->getMessage(), 'error');

            return false;
        }
    }

    /**
     * Create Initial Order
     *
     * @return mixed
     */
    public function createNewOrder()
    {
        $user = Factory::getApplication()->getIdentity();

        $data = (object) [
            'payment_method' => 'cod',
            'created'        => Factory::getDate('now')->toSql(true),
            'modified'       => Factory::getDate('now')->toSql(true),
            'created_by'     => $user->id,
        ];

        $result = EasyStoreDatabaseOrm::updateOrCreate('#__easystore_orders', $data);

        return !empty($result->id) ? $result->id : false;
    }

    /**
     * Function to get Order by Id
     *
     * @param int $id
     * @return object
     */
    public function getOrderById($id)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('*');
        $query->from($db->quoteName('#__easystore_orders'));
        $query->where($db->quoteName('id') . " = :order_id")
            ->bind(':order_id', $id, ParameterType::INTEGER);

        $db->setQuery($query);
        $item = $db->loadObject();

        // add support for v1.0.0 payment method value: manual_payment
        if ($item->payment_method === 'manual_payment') {
            $item->payment_method = 'cod';
        }

        $types = ['is_send_shipping_confirmation_email' => 'boolean'];
        $item  = EasyStoreHelper::typeCorrection($item, $types);

        $processedItems = $this->processGetItems([$item], null, true);
        $item           = EasyStoreHelper::first($processedItems);

        $orm              = new EasyStoreDatabaseOrm();
        $activities = $orm->setColumns(['id', 'activity_type', 'created', 'activity_value'])
            ->hasMany($id, '#__easystore_order_activities', 'order_id')
            ->updateQuery(function ($query) use ($orm) {
                $query->order($orm->quoteName('created') . ' DESC');
            })
            ->loadObjectList() ?? [];

        $item->activities = $activities;

        $item->refunds = $orm->setColumns(['id', 'order_id', 'refund_value', 'refund_reason'])
            ->hasMany($id, '#__easystore_order_refunds', 'order_id')
            ->loadObjectList() ?? [];

        $item->total_refunded_amount = $orm->setColumns([
            $orm->aggregateQuoteName('SUM', 'refund_value'),
        ])->useRawColumns(true)
            ->hasMany($id, '#__easystore_order_refunds', 'order_id')
            ->loadResult() ?? 0;

        $item->tax_rate = 0;

        if (!empty($item->shipping_address)) {
            $shippingAddress = json_decode($item->shipping_address);
            $country         = $shippingAddress->country;
            $state           = $shippingAddress->state;

            $taxRate        = $this->getTaxRate($country, $state);
            $item->tax_rate = $taxRate->product_tax_rate;
        }

        unset($item->coupon_type);

        return $item;
    }

    /**
     * Function to get orders from a date range
     *
     * @param string $from
     * @param string $to
     * @return object
     */
    public function getOrdersByDate($from, $to)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('*');
        $query->from($db->quoteName('#__easystore_orders'));
        $query->where('DATE(' . $db->quoteName('creation_date') . ") >= " . $db->quote($from));
        $query->where('DATE(' . $db->quoteName('creation_date') . ") <= " . $db->quote($to));
        $query->where($db->quoteName('order_status') . " = " . $db->quote('active'));
        $query->where($db->quoteName('payment_status') . " IN (" . $db->quote('paid') . "," . $db->quote('refunded') . ")");

        $db->setQuery($query);
        $items = $db->loadObjectList();
        $items = $this->processGetItems($items, null, true);

        return $items;
    }

    /**
     * Function to get Orders
     *
     * @param object $params
     * @return object
     */
    public function getOrders(object $params)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select(
            [
                'id',
                'creation_date',
                'customer_id',
                'payment_status',
                'fulfilment',
                'discount_type',
                'discount_value',
                'order_status',
                'shipping',
            ]
        );
        $query->from($db->quoteName('#__easystore_orders'));

        if (!empty($params->search)) {
            $search = preg_replace("@\s+@", ' ', $params->search);
            $search = explode(' ', $search);
            $search = array_filter($search, function ($word) {
                return !empty($word);
            });
            $search = implode('|', $search);
            $query->where($db->quoteName('id') . ' ' . $query->regexp($db->quote($search)));
        }

        if (!empty($params->orderStatus)) {
            $query->where($db->quoteName('order_status') . " = " . $db->quote($params->orderStatus));
        } else {
            $query->where('(' . $db->quoteName('order_status') . " = " . $db->quote('draft') . ' OR ' . $db->quoteName('order_status') . " = " . $db->quote('active') . ')');
        }

        if ($params->orderStatus !== "delete") {
            $query->where($db->quoteName('order_status') . " != " . $db->quote('delete'));
        }

        if (!empty($params->fulfilment)) {
            $query->where($db->quoteName('fulfilment') . " = " . $db->quote($params->fulfilment));
        }

        if (!empty($params->paymentStatus)) {
            $query->where($db->quoteName('payment_status') . " = " . $db->quote($params->paymentStatus));
        }

        if (!empty($params->customerId)) {
            $query->where($db->quoteName('customer_id') . " = " . $db->quote($params->customerId));
        }

        $ordering = null;

        if (!empty($params->sortBy)) {
            $ordering = EasyStoreHelper::sortBy($params->sortBy);

            if ($ordering->field !== 'total') {
                $query->order($db->quoteName($ordering->field) . ' ' . $ordering->direction);
            }
        }

        if ($params->all) {
            $db->setQuery($query);

            try {
                $items = $db->loadObjectList();
                $items = $this->processGetItems($items, $ordering);
            } catch (\Exception $e) {
                $items = [];
            }

            return $items;
        } else {
            // Separate query for counting all data without limit
            $countQuery = $db->getQuery(true);
            $countQuery = $query->__toString();

            if (!empty($params->limit)) {
                $query->setLimit($params->limit, $params->offset);
            }

            $db->setQuery($query);

            try {
                $items = $db->loadObjectList();
                $items = $this->processGetItems($items, $ordering);

                // Getting all rows without limit
                $db->setQuery($countQuery);
                $db->execute();
                $allItems = $db->getNumRows();

                $response = (object) [
                    'totalItems' => $allItems,
                    'totalPages' => ceil($allItems / $params->limit),
                    'results'    => $items,
                ];
            } catch (\Exception $e) {
                $response = (object) [
                    'totalItems' => 0,
                    'totalPages' => 0,
                    'results'    => [],
                ];
            }

            return $response;
        }
    }

    /**
     * Process Items get from database query
     *
     * @param array     $items          Product list
     * @param object    $ordering       Ordering field object
     * @param bool      $isDetail       return details information for a product
     * @return array
     */
    public function processGetItems(array $items, ?object $ordering = null, bool $isDetail = false)
    {
        $orm = new EasyStoreDatabaseOrm();

        foreach ($items as &$item) {
            if (is_null($item->creation_date)) {
                $item->creation_date = '';
            }

            if (!empty($item->customer_id)) {
                $easystoreUser = EasyStoreDatabaseOrm::get('#__easystore_users', 'id', $item->customer_id)->loadObject();

                if ($easystoreUser) {
                    $customerInformation = $orm->setColumns(['name', 'email'])
                    ->hasOne($easystoreUser->user_id, '#__users', 'id')
                    ->loadObject();
                    $item->customer = (object) [
                    'name' => $customerInformation->name ?? '',
                ];
                }
                
            }

            // Get Order Product map data
            $products = $orm->setColumns(['product_id', 'variant_id', 'discount_type', 'discount_value', 'discount_reason', 'quantity', 'price'])
                ->hasMany($item->id, '#__easystore_order_product_map', 'order_id')
                ->loadObjectList();

            $item->products  = [];
            $item->sub_total = 0.00;

            if (!empty($products)) {
                foreach ($products as $product) {
                    $discountedPrice = 0.00;
                    if ($product->discount_type === 'percent') {
                        $discountedPrice = (float) $product->price - ((float) $product->price * (float) $product->discount_value / 100);
                    } else {
                        $discountedPrice = (float) $product->price - (float) $product->discount_value;
                    }

                    $item->sub_total += $discountedPrice * $product->quantity;

                    if ($isDetail) {
                        $product->discount_value   = (float) $product->discount_value;
                        $product->price            = (float) $product->price;
                        $product->discounted_price = $discountedPrice;
                        $product->total            = !empty($discountedPrice) ? $discountedPrice * $product->quantity : $product->price * $product->quantity;

                        $product->image = $orm->setColumns(['src'])
                            ->hasOne($product->product_id, '#__easystore_media', 'product_id')
                            ->whereInReference($orm->quoteName('is_featured') . ' = 1')
                            ->loadResult();

                        if (!empty($product->image)) {
                            $product->image = Uri::root(true) . '/' . Path::clean($product->image);
                        }

                        $mainProduct = $orm->setColumns(['title', 'catid', 'weight', 'unit', 'sku'])
                            ->hasOne($product->product_id, '#__easystore_products', 'id')
                            ->loadObject();

                        $product->title        = $mainProduct->title;
                        $product->catid        = $mainProduct->catid;
                        $product->weight       = $mainProduct->weight;
                        $product->unit         = $mainProduct->unit;
                        $product->sku          = $mainProduct->sku;
                        $product->variant_name = '';
                        $product->options      = [];

                        if (!is_null($product->variant_id)) {
                            // Get variant details using Joomla database
                            $db = Factory::getContainer()->get(DatabaseInterface::class);
                            $query = $db->getQuery(true)
                                ->select(['combination_name', 'combination_value', 'image_id', 'sku', 'weight', 'unit'])
                                ->from($db->quoteName('#__easystore_product_skus'))
                                ->where($db->quoteName('id') . ' = ' . $db->quote($product->variant_id));
                            
                            $db->setQuery($query);
                            $variant = $db->loadObject();

                            if (!is_null($variant) && !is_null($variant->image_id)) {
                                // Get variant image using Joomla database 
                                $query = $db->getQuery(true)
                                    ->select($db->quoteName('src'))
                                    ->from($db->quoteName('#__easystore_media'))
                                    ->where($db->quoteName('id') . ' = ' . $db->quote($variant->image_id));
                                
                                $db->setQuery($query);
                                $product->image = $db->loadResult();

                                if (!empty($product->image)) {
                                    $product->image = Uri::root(true) . '/' . Path::clean($product->image);
                                }
                            }

                            $product->weight = $variant->weight ?? '';
                            $product->sku = $variant->sku ?? '';
                            $product->variant_name = $variant->combination_name ?? '';

                            $product->options = SiteEasyStoreHelper::detectProductOptionFromCombination(
                                SiteEasyStoreHelper::getProductOptionsById($product->product_id),
                                $variant->combination_value
                            );
                        }

                        $unit = SettingsHelper::getSettings()->get('products.standardUnits.weight', 'kg');

                        if (!empty($product->weight)) {
                            $product->unit = $variant->unit ?? $unit;
                        } else {
                            $product->unit = $unit;
                        }

                        $product->weight_with_unit = !empty($product->weight) ? SettingsHelper::getWeightWithUnit($product->weight, $product->unit) : '';

                        $item->products[] = $product;
                    }
                }
            }

            $orderSummary = EasyStoreHelper::getOrderCalculatedAmounts($item->id);

            $item->shipping = !empty($item->shipping) ? \json_decode($item->shipping) : null;
            $shipping_cost  = !empty($item->shipping->rate) ? (float) $item->shipping->rate : 0;

            $item->sub_total     = $orderSummary->sub_total;
            $item->shipping_cost = $shipping_cost;

            $item->total = $orderSummary->net_amount;

            $item->discount = (object) [
                'type'   => $item->discount_type,
                'amount' => (float) $item->discount_value ?? 0,
            ];

            $item->order_discount = $orderSummary->order_discount;

            /** @var CMSApplication $app */
            $app = Factory::getApplication();

            /** @var ComponentInterface */
            $component = $app->bootComponent('com_easystore');

            if (!empty($item->coupon_id)) {
                /** @var CouponModel $couponAdminModel */
                $couponAdminModel = $component->getMVCFactory()->createModel('Coupon', 'Administrator');
                $couponData       = $couponAdminModel->getItem($item->coupon_id);

                /** @var SiteCouponModel $couponSiteModel */
                $couponSiteModel              = $component->getMVCFactory()->createModel('Coupon', 'Site');
                $availableProductsForCoupon   = $couponSiteModel->getProductCouponData($item->coupon_id);
                $availableCategoriesForCoupon = $couponSiteModel->getCategoryCouponData($item->coupon_id);

                $item->coupon = (object) [
                    'id'                          => $item->coupon_id,
                    'code'                        => $item->coupon_code,
                    'category'                    => $item->coupon_category,
                    'discount_type'               => $item->coupon_type,
                    'discount_amount'             => floatval($item->coupon_amount) ?? 0,
                    'calculated_amount'           => $item->coupon_amount,
                    'sale_value'                  => !empty($couponData->sale_value) ? (float) $couponData->sale_value : 0,
                    'applies_to'                  => !empty($couponData->applies_to) ? $couponData->applies_to : 'all_products',
                    'selected_countries'          => !empty($couponData->selected_countries) ? explode(',', $couponData->selected_countries) : [],
                    'purchase_requirements'       => !empty($couponData->purchase_requirements) ? $couponData->purchase_requirements : 'no_minimum',
                    'purchase_requirements_value' => !empty($couponData->purchase_requirements_value) ? (float) $couponData->purchase_requirements_value : 0,
                    'available_products'          => $availableProductsForCoupon,
                    'available_categories'        => $availableCategoriesForCoupon,

                ];
            }
            
            if ($isDetail) {
                $db = Factory::getContainer()->get(DatabaseInterface::class);

                // Get activities
                $query = $db->getQuery(true)
                    ->select(['activity_type', 'activity_value', 'created'])
                    ->from($db->quoteName('#__easystore_order_activities'))
                    ->where($db->quoteName('order_id') . ' = ' . $db->quote($item->id))
                    ->order($db->quoteName('created') . ' DESC');
                $db->setQuery($query);
                $item->activities = $db->loadObjectList();

                if (empty($item->customer_id)) {
                    $item->user = (object) [
                        'id'       => null, 
                        'is_guest' => true,
                        'email'    => $item->customer_email,
                    ];
                } else {
                    // Get user details
                    $query = $db->getQuery(true)
                        ->select(['id', 'user_id', 'image', 'user_type', 'shipping_address', 
                                'is_billing_and_shipping_address_same', 'billing_address'])
                        ->from($db->quoteName('#__easystore_users'))
                        ->where($db->quoteName('id') . ' = ' . $db->quote($item->customer_id));
                    $db->setQuery($query);
                    $item->user = $db->loadObject();
                    $item->user->is_guest = false;
                }

                $item->user->shipping_address = !empty($item->shipping_address) ? \json_decode($item->shipping_address) : [];
                $item->user->billing_address  = !empty($item->billing_address) ? \json_decode($item->billing_address) : [];

                if (!$item->user->is_guest) {
                    $item->user->name  = $customerInformation->name;
                    $item->user->email = $customerInformation->email;
                } else {
                    $guestUserText    = Text::_('COM_EASYSTORE_ORDER_CUSTOMER_GUEST');
                    $item->user->name = !empty($item->user->shipping_address->name) ? $item->user->shipping_address->name . ' (' . $guestUserText . ')' : $guestUserText;
                }
            }

            unset($item->customer_id);
            unset($item->discount_type);
            unset($item->discount_value);
            unset($item->coupon_id);
            unset($item->coupon_code);
            unset($item->coupon_amount);
            unset($item->coupon_category);

            if (!$isDetail) {
                unset($item->products);
                unset($item->sub_total);
                unset($item->discount);
                unset($item->shipping);
                unset($item->coupon);
                unset($item->shipping_cost);
            } else {
                unset($item->customer);
            }
        }

        if (!is_null($ordering) && $ordering->field === 'total') {
            $direction = $ordering->direction;

            usort($items, function ($first, $second) use ($direction) {
                return $direction === 'ASC' ? $first->total - $second->total : $second->total - $first->total;
            });
        }

        return $items;
    }

    /**
     * Function to check if creation_date exists
     *
     * @param int $orderId
     * @return bool
     */
    public function checkCreationDateExists(int $orderId)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true);
        $query->select(['creation_date']);
        $query->from($db->quoteName('#__easystore_orders'));
        $query->where($db->quoteName('id') . " = " . $db->quote($orderId));

        $db->setQuery($query);

        $result = $db->loadObject();

        return !\is_null($result->creation_date);
    }

    /**
     * Function to update Order
     *
     * @param object $orderInformation
     * @return bool
     */
    public function update(object $orderInformation)
    {
        $data = (object) [
            'id'               => $orderInformation->id,
            'customer_id'      => $orderInformation->customer_id,
            'customer_note'    => $orderInformation->customer_note,
            'payment_status'   => $orderInformation->payment_status,
            'fulfilment'       => $orderInformation->fulfilment,
            'order_status'     => $orderInformation->order_status,
            'discount_type'    => $orderInformation->discount_type,
            'discount_value'   => $orderInformation->discount_value,
            'discount_reason'  => $orderInformation->discount_reason,
            'shipping'         => $orderInformation->shipping,
            'shipping_address' => $orderInformation->shipping_address,
            'billing_address'  => $orderInformation->billing_address,
            'access'           => $orderInformation->access,
            'ordering'         => $orderInformation->ordering,
            'created'          => Factory::getDate('now'),
            'created_by'       => Factory::getApplication()->getIdentity()->id,
        ];

        if (!is_null($orderInformation->customer_email)) {
            $data->customer_email = $orderInformation->customer_email;
        }

        if (!is_null($orderInformation->creation_date)) {
            $data->creation_date = $orderInformation->creation_date;
        }

        if (!is_null($orderInformation->customer_note)) {
            $data->customer_note = $orderInformation->customer_note;
        }

        if (!empty($orderInformation->discount_reason)) {
            $data->discount_reason = $orderInformation->discount_reason;
        }

        if (!empty($orderInformation->shipping)) {
            $data->shipping = $orderInformation->shipping;
        }

        if (!empty($orderInformation->sale_tax)) {
            $data->sale_tax = $orderInformation->sale_tax;
        }

        $result = EasyStoreDatabaseOrm::updateOrCreate('#__easystore_orders', $data);

        return !empty($result->id);
    }

    /**
     * Function to store multiple products against an order in #__easystore_order_product_map table
     *
     * @param array $products
     * @param int $orderId
     * @return bool
     */
    public function storeMultipleOrderedProducts(array $products, int $orderId)
    {
        EasyStoreDatabaseOrm::removeByIds('#__easystore_order_product_map', [$orderId], 'order_id');
        $items = array_map(function ($item) use ($orderId) {
            return (object) [
                'order_id' => $orderId,
                'product_id' => $item['id'],
                'variant_id' => $item['variant_id'],
                'discount_type' => $item['discount_type'] ?? 'percent',
                'discount_value' => $item['discount_value'] ?? 0,
                'discount_reason' => $item['discount_reason'] ?? '',
                'quantity' => $item['quantity'] ?? 1,
                'price' => $item["price"] ?? 0,
                'cart_item' => !empty($item['cart_item']) && !is_string($item['cart_item']) ? json_encode($item['cart_item']) : ($item['cart_item'] ?? null),
            ];
        }, $products);

        foreach ($items as $item) {
            $result = EasyStoreDatabaseOrm::updateOrCreate('#__easystore_order_product_map', $item);
        }

        if (!$result) {
            return false;
        }

        return true;
    }

    /**
     * Function for patch Orders request
     *
     * @param int $id
     * @param string $type
     * @param string $value
     * @return bool
     */
    public function patchOrders(int $id, string $type, string $value)
    {
        $data = (object) [
            'id'  => $id,
            $type => $value,
        ];

        $result = EasyStoreDatabaseOrm::updateOrCreate('#__easystore_orders', $data);

        return !empty($result->id);
    }

    /**
     * Function to add Order activities
     *
     * @param int $id   Order Id
     * @param string $type
     * @param string|null $value
     * @return bool
     */
    public function addOrderActivity(int $id, string $type, ?string $value = null)
    {
        $data = (object) [
            'order_id'      => $id,
            'activity_type' => $type,
            'created'       => Factory::getDate('now')->toSql(true),
            'created_by'    => Factory::getApplication()->getIdentity()->id,
            'modified'      => Factory::getDate('now')->toSql(true),
            'modified_by'   => Factory::getApplication()->getIdentity()->id,
        ];

        if (!is_null($value)) {
            $data->activity_value = $value;
        }

        $result = EasyStoreDatabaseOrm::updateOrCreate('#__easystore_order_activities', $data);

        return !empty($result->id);
    }

    /**
     * Function to Delete Order by changing order_status
     *
     * @param array $ids
     * @return bool
     */
    public function deleteOrder(array $ids)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true);

        $fields = [
            $db->quoteName('order_status') . ' = ' . $db->quote('delete'),
            $db->quoteName('published') . ' = ' . $db->quote('-2'),
        ];

        $conditions = [$db->quoteName('id') . ' IN (' . implode(',', $ids) . ')'];
        $query->update($db->quoteName('#__easystore_orders'))->set($fields)->where($conditions);
        $db->setQuery($query);

        try {
            $db->execute();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Function for add/update order tracking
     *
     * @param object $params
     * @return bool
     */
    public function addUpdateOrderTracking($params)
    {
        $result = EasyStoreDatabaseOrm::updateOrCreate('#__easystore_orders', $params);

        if ($params->is_send_shipping_confirmation_email) {
            $order = $this->getOrderById($params->id);

            if (!empty($order)) {
                $customerEmail = $order->user->email ?? null;

                if (!is_null($customerEmail)) {
                    $this->sendTrackingEmail($order, [
                        'shipping_carrier' => $order->shipping_carrier,
                        'tracking_url' => $order->tracking_url
                    ]);
                }
            }
        }

        if (!empty($result)) {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);

            $query->select('id');
            $query->from($db->quoteName('#__easystore_order_activities'));
            $query->where($db->quoteName('order_id') . " = " . $params->id);
            $query->where($db->quoteName('activity_type') . " = " . $db->quote('tracking_number_added'));

            $db->setQuery($query);
            $activityResult = $db->loadResult();

            $isNew = empty($activityResult);

            $activityType = $isNew ? 'tracking_number_added' : 'tracking_number_edited';

            $this->addOrderActivity($params->id, $activityType);
        }

        return !empty($result->id);
    }

    /**
     * Function to duplicate a Order by Id
     *
     * @param int $id
     * @return bool
     */
    public function duplicateOrder(int $id)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName('#__easystore_orders'));
        $query->where($db->quoteName('id') . " = " . $id);

        $db->setQuery($query);
        $order = $db->loadObject();

        $order->created    = Factory::getDate('now')->toSql(true);
        $order->created_by = Factory::getApplication()->getIdentity()->id;

        unset($order->checked_out, $order->checked_out_time, $order->modified, $order->modified_by);

        $orm = new EasyStoreDatabaseOrm();

        $orderProductMap = $orm->setColumns([])
            ->hasMany($id, '#__easystore_order_product_map', 'order_id')
            ->loadObjectList();

        foreach ($orderProductMap as &$orderProduct) {
            if (is_null($orderProduct->variant_id)) {
                $table          = '#__easystore_products';
                $columnName     = 'regular_price';
                $primaryId      = $orderProduct->product_id;
                $referenceKey   = 'has_variants';
                $referenceValue = 0;
            } else {
                $table          = '#__easystore_product_skus';
                $columnName     = 'price';
                $primaryId      = $orderProduct->variant_id;
                $referenceKey   = 'product_id';
                $referenceValue = $orderProduct->product_id;
            }

            $orderProduct->price = $orm->setColumns([$columnName])
                ->hasOne($primaryId, $table, 'id')
                ->whereInReference($orm->quoteName($referenceKey) . ' = ' . $referenceValue)
                ->loadResult();
        }

        unset($orderProduct);

        $orderId = $this->createNewOrder($order);

        if (empty($orderId)) {
            return false;
        }

        $order->id = $orderId;

        if ($this->update($order)) {
            if (!empty($orderProductMap)) {
                foreach ($orderProductMap as $orderProduct) {
                    $orderProduct->order_id = $orderId;
                    EasyStoreDatabaseOrm::updateOrCreate('#__easystore_order_product_map', $orderProduct);
                }
            }

            return true;
        }
    }

    /**
     * Function for refunding an Order
     *
     * @param int $id   Order ID
     * @param string $value Refund amount
     * @param string $reason Refund reason
     * @return object
     * 
     * @since 1.4.4
     */
    public function makeRefund(int $id, string $value, string $reason)
    {
        $value    = (float) $value;
        $response = new \stdClass();

        $orderManager = OrderManager::createWith($id);
        $order = $orderManager->getOrderItem();

        $isValid = !empty($order) && ($order->payment_status === 'paid' || $order->payment_status === 'partially_refunded');

        if (!$isValid) {
            $response->status  = false;
            $response->message = 'COM_EASYSTORE_APP_ORDER_REFUND_IS_NOT_VALID';

            return $response;
        }

        $refundableAmount = $orderManager->calculateRefundableAmount();

        if ($value > $refundableAmount) {
            $response->status  = false;
            $response->message = 'COM_EASYSTORE_APP_ORDER_REFUND_AMOUNT_IS_GREATER';

            return $response;
        }

        $data = (object) [
            'order_id'      => $id,
            'refund_value'  => $value,
            'refund_reason' => $reason,
            'created_by'    => Factory::getApplication()->getIdentity()->id,
            'created'       => Factory::getDate('now')->toSql(true),
            'modified_by'   => Factory::getApplication()->getIdentity()->id,
            'modified'      => Factory::getDate('now')->toSql(true),
        ];

        $refunded = EasyStoreDatabaseOrm::updateOrCreate('#__easystore_order_refunds', $data);

        $paymentStatus = 'partially_refunded';
        $reamingAfterRefund = $refundableAmount - $value;
        $isFullyRefunded = $reamingAfterRefund === 0.0;

        if ($isFullyRefunded) {
            $paymentStatus = 'refunded';
        }

        $orderData = (object) [
            'id'             => $id,
            'payment_status' => $paymentStatus,
        ];

        EasyStoreDatabaseOrm::updateOrCreate('#__easystore_orders', $orderData);

        $activityType = $paymentStatus === 'refunded' ? 'marked_as_refunded' : 'marked_as_partially_refunded';
        $this->addOrderActivity($id, $activityType);

        $response->status = !empty($refunded->id);

        if (!empty($refunded->id)) {

            $this->sendOrderRefundEmail($order, [
                'refund_amount' => $value,
                'refund_reason' => $reason,
            ]);
        }

        return $response;
    }

    /**
     * Update reviews for specified products which was unpublished.
     *
     * @param array  $products - Array of products.
     * @param object $user     - Joomla user object.
     *
     * @throws \Exception If there is an error in the database operation.
     * @since  1.0.0
     */
    public function updateProductsReviews($products, $user)
    {
        $uniqueProduct = [];

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        foreach ($products as $product) {
            // check same product with variants
            if (!isset($uniqueProduct[$product->product_id])) {
                $uniqueProduct[$product->product_id] = true;

                $query->update($db->quoteName('#__easystore_reviews'))
                    ->set($db->quoteName('published') . ' = ' . Status::PUBLISHED)
                    ->where($db->quoteName('product_id') . ' = ' . $db->quote($product->product_id))
                    ->where($db->quoteName('created_by') . ' = ' . $db->quote($user->user_id));

                try {
                    $db->setQuery($query);
                    $db->execute();
                } catch (\Exception $e) {
                    throw new \Exception($e->getMessage());
                }
            }
        }
    }
}
