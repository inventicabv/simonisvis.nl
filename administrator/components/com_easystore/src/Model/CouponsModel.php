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

use stdClass;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\Filesystem\Path;
use JoomShaper\Component\EasyStore\Administrator\Constants\Status;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseHelper;

/**
 * This models supports retrieving a list of coupons.
 *
 * @since  1.0.0
 */
class CouponsModel extends ListModel
{
    /**
     * Constructor.
     *
     *
     * @param   array   $config   An optional associative array of configuration settings.
     *
     * @since   1.0.0
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id',
                'a.id',
                'title',
                'a.title',
                'discount_type',
                'a.discount_type',
                'discount_value',
                'a.discount_value',
                'code',
                'a.code',
                'start_date',
                'a.start_date',
                'end_date',
                'a.end_date',
                'published',
                'a.published',
                'checked_out',
                'a.checked_out',
                'checked_out_time',
                'a.checked_out_time',
                'created',
                'a.created',
                'created_by',
                'a.created_by',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function populateState($ordering = 'a.id', $direction = 'asc')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);

        // List state information.
        parent::populateState($ordering, $direction);
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string  $id  A prefix for the store id.
     *
     *
     * @since   1.0.0
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');

        return parent::getStoreId($id);
    }

    /**
     * Method to create a query for a list of items.
     *
     * @return  DatabaseQuery
     *
     * @since  1.0.0
     */
    protected function getListQuery()
    {
        // Create a new query object.
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);
        $user  = $this->getCurrentUser();

        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                'a.id, a.title, a.alias, a.code, a.discount_type, a.discount_value, a.start_date, a.end_date, a.has_date' .
                ', a.published, a.checked_out, a.checked_out_time, a.created_by'
            )
        );
        $query->from($db->quoteName('#__easystore_coupons', 'a'));

        // Join over the users for the checked out user.
        $query->select($db->quoteName('uc.name', 'editor'))
            ->join('LEFT', $db->quoteName('#__users', 'uc'), $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));

        // Join over the users for the author.
        $query->select($db->quoteName('ua.name', 'author_name'))
            ->join('LEFT', $db->quoteName('#__users', 'ua'), $db->quoteName('ua.id') . ' = ' . $db->quoteName('a.created_by'));

        // Filter by published state
        $published = (string) $this->getState('filter.published');

        if (is_numeric($published)) {
            $published = (int) $published;
            $query->where($db->quoteName('a.published') . ' = :published')
                ->bind(':published', $published, ParameterType::INTEGER);
        } elseif ($published === '') {
            $query->whereIn($db->quoteName('a.published'), [Status::UNPUBLISHED, Status::PUBLISHED]);
        }

        // Filter by search in title
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $ids = (int) substr($search, 3);
                $query->where($db->quoteName('a.id') . ' = :id')
                    ->bind(':id', $ids, ParameterType::INTEGER);
            } else {
                $search = '%' . str_replace(' ', '%', trim($search)) . '%';
                $query->extendWhere(
                    'AND',
                    [
                        $db->quoteName('a.title') . ' LIKE :title',
                        $db->quoteName('a.alias') . ' LIKE :alias',
                        $db->quoteName('a.code') . ' LIKE :code',
                    ],
                    'OR'
                );
                $query->bind(':title', $search)
                    ->bind(':alias', $search)
                    ->bind(':code', $search);
            }
        }

        // Add the list ordering clause
        $listOrdering = $this->getState('list.ordering', 'a.id');
        $listDirn     = $db->escape($this->getState('list.direction', 'ASC'));

        $query->order($db->escape($listOrdering) . ' ' . $listDirn);

        return $query;
    }

    /**
     * Method to get an array of data items.
     *
     * @return  mixed  An array of data items on success, false on failure.
     *
     * @since   1.0.0
     */
    public function getItems()
    {
        $items = parent::getItems();

        foreach ($items as &$item) {
            if ($item->discount_type === 'amount') {
                $item->discount_amount = EasyStoreHelper::formatCurrency($item->discount_value);
            } else {
                $item->discount_amount = $item->discount_value . '%';
            }
        }

        unset($item);

        return $items;
    }

    /**
     * Function to get Coupons
     *
     * @param object $params
     * @return object
     */
    public function getCoupons(object $params)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select(
            [
                'id',
                'title AS name',
                'alias',
                'coupon_category',
                'code',
                'discount_type',
                'discount_value',
                'applies_to',
                'country_type',
                'buy_x',
                'get_y',
                'coupon_limit_status',
                'coupon_limit_value',
                'usage_limit_status',
                'usage_limit_value',
                'purchase_requirements',
                'purchase_requirements_value',
                'start_date',
                'has_date',
                'end_date',
                'published AS coupon_status',
            ]
        );
        $query->from($db->quoteName('#__easystore_coupons'));

        if (!empty($params->search)) {
            $search = preg_replace("@\s+@", ' ', $params->search);
            $search = explode(' ', $search);
            $search = array_filter($search, function ($word) {
                return !empty($word);
            });
            $search = implode('|', $search);
            $query->where('(' . $db->quoteName('title') . ' ' . $query->regexp($db->quote($search)) . ' OR ' . $db->quoteName('code') . ' ' . $query->regexp($db->quote($search)) . ')');
        }

        if (!empty($params->status)) {
            $query->where($db->quoteName('published') . " = " . $this->getStatusId($params->status));
        } else {
            $query->where($db->quoteName('published') . ' <> ' . Status::SOFT_DELETED);
        }

        if (!empty($params->sortBy)) {
            $ordering = EasyStoreHelper::sortBy($params->sortBy);
            $query->order($db->quoteName($ordering->field) . ' ' . $ordering->direction);
        } else {
            $query->order($db->quoteName('created') . ' DESC');
        }

        $types = [
            'discount_value'              => 'float',
            'coupon_limit_status'         => 'boolean',
            'usage_limit_status'          => 'boolean',
            'purchase_requirements_value' => 'float',
        ];

        if ($params->all) {
            $db->setQuery($query);
            $items = $db->loadObjectList();

            $items = $this->processItems($items);
            $items = EasyStoreHelper::typeCorrection($items, $types);

            return $items;
        } else {
            // Separate query for counting all data without limit
            $countQuery = $db->getQuery(true);
            $countQuery = $query->__toString();

            if (!empty($params->limit)) {
                $query->setLimit($params->limit, $params->offset);
            }

            $db->setQuery($query);
            $items = $db->loadObjectList();

            $items = $this->processItems($items);
            $items = EasyStoreHelper::typeCorrection($items, $types);

            // Getting all rows without limit
            $db->setQuery($countQuery);
            $db->execute();
            $allItems = $db->getNumRows();

            $response             = new stdClass();
            $response->totalItems = $allItems;
            $response->totalPages = ceil($allItems / $params->limit);
            $response->results    = $items;

            return $response;
        }
    }

    /**
     * Process Items get from database query
     *
     * @param array $items
     * @return array
     */
    private function processItems(array $items)
    {
        foreach ($items as &$item) {
            $item->start_time    = !is_null($item->start_date) ? date('h:i A', strtotime($item->start_date)) : '';
            $item->start_date    = !is_null($item->start_date) ? date('Y-m-d', strtotime($item->start_date)) : '';
            $item->end_time      = !is_null($item->end_date) ? date('h:i A', strtotime($item->end_date)) : '';
            $item->end_date      = !is_null($item->end_date) ? date('Y-m-d', strtotime($item->end_date)) : '';
            $item->has_date      = (bool) $item->has_date;
            $item->coupon_status = $this->getStatusById($item->coupon_status);
        }

        unset($item);

        return $items;
    }

    /**
     * Function to get Coupon by Id
     *
     * @param int $id
     * @return object
     */
    public function getCouponById(int $id)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select(
            [
                'id',
                'title AS name',
                'alias',
                'coupon_category',
                'code',
                'discount_type',
                'discount_value',
                'sale_value',
                'applies_to',
                'country_type',
                'selected_countries',
                'coupon_limit_status',
                'coupon_limit_value',
                'usage_limit_status',
                'usage_limit_value',
                'purchase_requirements',
                'purchase_requirements_value',
                'start_date',
                'has_date',
                'end_date',
                'published AS coupon_status',
                'applies_to_x',
                'buy_x',
                'applies_to_y',
                'get_y',
            ]
        );
        $query->from($db->quoteName('#__easystore_coupons'));
        $query->where($db->quoteName('id') . " = " . $id);

        $db->setQuery($query);
        $item = $db->loadObject();

        $types = [
            'discount_value'              => 'float',
            'coupon_limit_status'         => 'boolean',
            'usage_limit_status'          => 'boolean',
            'purchase_requirements_value' => 'float',
        ];

        $item         = EasyStoreHelper::typeCorrection($item, $types);
        $processItems = $this->processItems([$item]);
        $item         = EasyStoreHelper::first((array) $processItems);
        $orm          = new EasyStoreDatabaseOrm();

        if ($item->coupon_category !== 'buy_get_free') {
            if ($item->applies_to === 'specific_categories') {
                $item->categories = $orm->setColumns(['id', 'title'])
                    ->belongsToMany($id, '#__easystore_categories', '#__easystore_coupon_category_map', 'coupon_id', 'category_id')
                    ->loadObjectList();
                $item->categories = array_filter($item->categories, function ($category) {
                    $category->name = $category->title;
                    unset($category->title);
                    return $category->name;
                });
            }

            if ($item->applies_to === 'specific_products') {
                $products = $orm->setColumns(['id', 'title'])
                    ->belongsToMany($id, '#__easystore_products', '#__easystore_coupon_product_map', 'coupon_id', 'product_id')
                    ->loadObjectList();

                if (!empty($products)) {
                    foreach ($products as $product) {
                        $item->products[] = $this->getProductInfo($product, $id);
                    }
                }
            }
        } else {
            unset($item->applies_to);

            if ($item->applies_to_x === 'specific_categories') {
                $item->categories_x = $orm->setColumns(['id', 'title'])
                    ->belongsToMany($id, '#__easystore_categories', '#__easystore_coupon_category_map', 'coupon_id', 'category_id')
                    ->updateQuery(function ($query) use ($orm) {
                        $query->where($orm->quoteName('buy_get_offer') . ' = ' . $orm->quote('x'));
                    })
                    ->loadObjectList();
                $item->categories_x = array_filter($item->categories_x, function ($category) {
                    $category->name = $category->title;
                    unset($category->title);
                    return $category->name;
                });
            }

            if ($item->applies_to_x === 'specific_products') {
                $products = $orm->setColumns(['id', 'title'])
                    ->belongsToMany($id, '#__easystore_products', '#__easystore_coupon_product_map', 'coupon_id', 'product_id')
                    ->updateQuery(function ($query) use ($orm) {
                        $query->where($orm->quoteName('buy_get_offer') . ' = ' . $orm->quote('x'));
                    })
                    ->loadObjectList();

                if (!empty($products)) {
                    foreach ($products as $product) {
                        $item->products_x[] = $this->getProductInfo($product, $id);
                    }
                }
            }

            if ($item->applies_to_y === 'specific_products') {
                $products = $orm->setColumns(['id', 'title'])
                    ->belongsToMany($id, '#__easystore_products', '#__easystore_coupon_product_map', 'coupon_id', 'product_id')
                    ->updateQuery(function ($query) use ($orm) {
                        $query->where($orm->quoteName('buy_get_offer') . ' = ' . $orm->quote('y'));
                    })
                    ->loadObjectList();

                if (!empty($products)) {
                    foreach ($products as $product) {
                        $item->products_y[] = $this->getProductInfo($product, $id);
                    }
                }
            }
        }

        /** @var CMSApplication $app */
        $app = Factory::getApplication();

        /** @var CouponModel $couponModel */
        $couponModel                  = $app->bootComponent('com_easystore')->getMVCFactory()->createModel('Coupon', 'Administrator');
        $item->redeemed_coupons_count = $couponModel->getCouponRedeemedAmount($item->id);

        return $item;
    }

    /**
     * Function to get product information with object
     *
     * @param object $product
     * @return array
     */
    public function getProductInfo(object $product, int $coupon_id)
    {
        $productId = $product->id;
        $orm       = new EasyStoreDatabaseOrm();

        // Get Product feature image
        $image = $orm->setColumns(['src'])
            ->hasOne($productId, '#__easystore_media', 'product_id')
            ->updateQuery(function ($query) use ($orm) {
                $query->where($orm->quoteName('is_featured') . ' = 1');
            })->loadResult();

        // Get Product variants ids
        $orm->setColumns(['sku_id'])
            ->hasMany($productId, '#__easystore_coupon_product_sku_map', 'product_id')
            ->updateQuery(function ($query) use ($orm, $coupon_id) {
                $query->where($orm->quoteName('coupon_id') . ' = ' . $coupon_id);
            });

        $variants = $orm->loadColumn();

        // Get Product total variants
        $totalVariants = $orm->setColumns([$orm->aggregateQuoteName('COUNT', 'id', 'total_variants')])
            ->useRawColumns(true)
            ->hasMany($productId, '#__easystore_product_skus', 'product_id')
            ->loadResult();

        $productArray = [
            'id'             => $productId,
            'title'          => $product->title,
            'image'          => is_null($image) ? '' : Uri::root(true) . '/' . Path::clean($image),
            'variants'       => $variants,
            'total_variants' => $totalVariants,
        ];

        return $productArray;
    }

    /**
     * Function to store coupon
     *
     * @param object $couponInfo
     * @return boolean/integer
     */
    public function store(object $couponInfo)
    {
        $data = [
            'title'                       => !empty($couponInfo->title) ? $couponInfo->title : $couponInfo->name,
            'alias'                       => ApplicationHelper::stringURLSafe(!empty($couponInfo->title) ? $couponInfo->title : $couponInfo->name),
            'coupon_category'             => $couponInfo->coupon_category,
            'code'                        => $couponInfo->code,
            'discount_type'               => $couponInfo->discount_type,
            'discount_value'              => $couponInfo->discount_value,
            'sale_value'                  => $couponInfo->sale_value,
            'applies_to'                  => $couponInfo->applies_to,
            'country_type'                => $couponInfo->country_type,
            'selected_countries'          => $couponInfo->selected_countries,
            'applies_to_x'                => $couponInfo->applies_to_x,
            'buy_x'                       => $couponInfo->buy_x,
            'applies_to_y'                => $couponInfo->applies_to_y,
            'get_y'                       => $couponInfo->get_y,
            'coupon_limit_status'         => $couponInfo->coupon_limit_status,
            'coupon_limit_value'          => $couponInfo->coupon_limit_value,
            'usage_limit_status'          => $couponInfo->usage_limit_status,
            'usage_limit_value'           => $couponInfo->usage_limit_value,
            'purchase_requirements'       => $couponInfo->purchase_requirements,
            'purchase_requirements_value' => $couponInfo->purchase_requirements_value,
            'start_date'                  => $couponInfo->start_date,
            'has_date'                    => $couponInfo->has_date,
            'published'                   => $this->getStatusId(!empty($couponInfo->published) ? $couponInfo->published : $couponInfo->coupon_status),
            'created'                     => Factory::getDate('now'),
            'created_by'                  => Factory::getApplication()->getIdentity()->id,
        ];

        if (!is_null($couponInfo->end_date)) {
            $data['end_date'] = $couponInfo->end_date;
        }

        $data = (object) $data;

        $result = EasyStoreDatabaseOrm::updateOrCreate('#__easystore_coupons', $data);

        return !empty($result->id) ? $result->id : false;
    }

    /**
     * Function to update Coupon
     *
     * @param object $couponInfo
     * @return bool
     */
    public function update(object $couponInfo)
    {
        $data = [
            'id'                          => $couponInfo->id,
            'title'                       => $couponInfo->name,
            'alias'                       => ApplicationHelper::stringURLSafe($couponInfo->name),
            'coupon_category'             => $couponInfo->coupon_category,
            'code'                        => $couponInfo->code,
            'discount_type'               => $couponInfo->discount_type,
            'discount_value'              => $couponInfo->discount_value,
            'sale_value'                  => $couponInfo->sale_value,
            'applies_to'                  => $couponInfo->applies_to,
            'country_type'                => $couponInfo->country_type,
            'selected_countries'          => $couponInfo->selected_countries,
            'applies_to_x'                => $couponInfo->applies_to_x,
            'buy_x'                       => $couponInfo->buy_x,
            'applies_to_y'                => $couponInfo->applies_to_y,
            'get_y'                       => $couponInfo->get_y,
            'coupon_limit_status'         => $couponInfo->coupon_limit_status,
            'coupon_limit_value'          => $couponInfo->coupon_limit_value,
            'usage_limit_status'          => $couponInfo->usage_limit_status,
            'usage_limit_value'           => $couponInfo->usage_limit_value,
            'purchase_requirements'       => $couponInfo->purchase_requirements,
            'purchase_requirements_value' => $couponInfo->purchase_requirements_value,
            'start_date'                  => $couponInfo->start_date,
            'has_date'                    => $couponInfo->has_date,
            'published'                   => $this->getStatusId($couponInfo->coupon_status),
            'created'                     => Factory::getDate('now'),
            'created_by'                  => Factory::getApplication()->getIdentity()->id,
        ];

        if (!is_null($couponInfo->end_date)) {
            $data['end_date'] = $couponInfo->end_date;
        }

        $data = (object) $data;

        $result = EasyStoreDatabaseOrm::updateOrCreate('#__easystore_coupons', $data);

        return !empty($result->id);
    }

    /**
     * Function to update Coupon Status
     *
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $id, string $status)
    {
        $data = (object) [
            'id'        => $id,
            'published' => $this->getStatusId($status),
        ];

        $result = EasyStoreDatabaseOrm::updateOrCreate('#__easystore_coupons', $data);

        return !empty($result->id);
    }

    /**
     * Function to Delete Coupon by changing coupon_status
     *
     * @param array $ids
     * @return bool
     */
    public function delete(array $ids)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true);

        $fields = [
            $db->quoteName('published') . ' = ' . Status::SOFT_DELETED,
        ];

        $conditions = [$db->quoteName('id') . ' IN (' . implode(',', $ids) . ')'];
        $query->update($db->quoteName('#__easystore_coupons'))->set($fields)->where($conditions);
        $db->setQuery($query);

        try {
            $db->execute();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Function to validate if Coupon code exists
     *
     * @param string $code
     * @return bool
     */
    public function couponValidation(string $code, $id = null)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true);
        $query->select(['id']);
        $query->from($db->quoteName('#__easystore_coupons'));
        $query->where($db->quoteName('code') . " = " . $db->quote($code));

        if ($id) {
            $query->where($db->quoteName('id') . " != " . $db->quote($id));
        }

        $db->setQuery($query);

        $result = $db->loadObject();

        if (!$result) {
            return true;
        }

        return false;
    }

    /**
     * Function to duplicate a Coupon by Id
     *
     * @param int $id
     * @param string $code
     * @return bool
     */
    public function duplicateCoupon(int $id, string $code)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('*');
        $query->from($db->quoteName('#__easystore_coupons'));
        $query->where($db->quoteName('id') . " = " . $id);

        $db->setQuery($query);
        $coupon = $db->loadObject();

        $coupon->code       = $code;
        $coupon->created    = Factory::getDate('now');
        $coupon->created_by = Factory::getApplication()->getIdentity()->id;

        unset($coupon->checked_out, $coupon->checked_out_time, $coupon->modified, $coupon->modified_by);

        if ($this->store($coupon)) {
            return true;
        }

        return false;
    }

    /**
     * Function for update coupon by object
     *
     * @param object $data
     * @return bool
     */
    public function editByObject(object $data)
    {
        $db     = Factory::getContainer()->get(DatabaseInterface::class);
        $result = $db->updateObject('#__easystore_coupons', $data, 'id');

        return $result;
    }

    /**
     * Function to store multiple categories in #__easystore_coupon_category_map table
     *
     * @param array $categories
     * @param int $couponId
     * @param string $isOffer
     * @return bool
     */
    public function storeMultipleCategories(array $categories, int $couponId, ?string $isOffer = null)
    {
        $extraCondition = [];

        if (!is_null($isOffer)) {
            $extraCondition = [
                [
                    'key'      => 'buy_get_offer',
                    'operator' => '=',
                    'value'    => $isOffer,
                ],
            ];
        }

        $changes = EasyStoreDatabaseHelper::detectPivotTableChanges($categories, $couponId, '#__easystore_coupon_category_map', 'coupon_id', 'category_id', $extraCondition);

        if (!empty($changes->removable)) {
            EasyStoreDatabaseOrm::removeByIds('#__easystore_coupon_category_map', $changes->removable, 'category_id', $extraCondition);
        }

        if (!empty($changes->newEntries)) {
            $data = array_map(function ($item) use ($couponId, $isOffer) {
                $returnArray = [
                    'coupon_id'   => $couponId,
                    'category_id' => $item,
                ];

                if (!is_null($isOffer)) {
                    $returnArray['buy_get_offer'] = $isOffer;
                }

                return $returnArray;
            }, $changes->newEntries);

            EasyStoreDatabaseOrm::insertMultipleRecords('#__easystore_coupon_category_map', array_values($data));
        }
    }

    /**
     * Function to store multiple products in #__easystore_coupon_product_map table
     *
     * @param array $products
     * @param int $couponId
     * @param string $isOffer
     * @return bool
     */
    public function storeMultipleProducts(array $products, int $couponId, ?string $isOffer = null)
    {
        $extraCondition = [];

        if (!is_null($isOffer)) {
            $extraCondition = [
                [
                    'key'      => 'buy_get_offer',
                    'operator' => '=',
                    'value'    => $isOffer,
                ],
            ];
        }

        $changes = EasyStoreDatabaseHelper::detectPivotTableChanges($products, $couponId, '#__easystore_coupon_product_map', 'coupon_id', 'product_id', $extraCondition);

        if (!empty($changes->removable)) {
            EasyStoreDatabaseOrm::removeByIds('#__easystore_coupon_product_map', $changes->removable, 'product_id', $extraCondition);
        }

        if (!empty($changes->newEntries)) {
            $data = array_map(function ($item) use ($couponId, $isOffer) {
                $returnArray = [
                    'coupon_id'  => $couponId,
                    'product_id' => $item,
                ];

                if (!is_null($isOffer)) {
                    $returnArray['buy_get_offer'] = $isOffer;
                }

                return $returnArray;
            }, $changes->newEntries);

            EasyStoreDatabaseOrm::insertMultipleRecords('#__easystore_coupon_product_map', array_values($data));
        }
    }

    /**
     * Function to store multiple skus in #__easystore_coupon_product_sku_map table
     *
     * @param array $skus
     * @param int $productId
     * @param int $couponId
     * @return bool
     */
    public function storeMultipleProductSkus(array $skus, int $productId, int $couponId)
    {
        $extraCondition = [
            [
                'key'      => 'coupon_id',
                'operator' => '=',
                'value'    => $couponId,
            ],
        ];

        $changes = EasyStoreDatabaseHelper::detectPivotTableChanges($skus, $productId, '#__easystore_coupon_product_sku_map', 'product_id', 'sku_id', $extraCondition);

        if (!empty($changes->removable)) {
            EasyStoreDatabaseOrm::removeByIds('#__easystore_coupon_product_sku_map', $changes->removable, 'sku_id', $extraCondition);
        }

        if (!empty($changes->newEntries)) {
            $data = array_map(function ($item) use ($productId, $couponId) {
                return [
                    'coupon_id'  => $couponId,
                    'product_id' => $productId,
                    'sku_id'     => $item,
                ];
            }, $changes->newEntries);

            EasyStoreDatabaseOrm::insertMultipleRecords('#__easystore_coupon_product_sku_map', array_values($data));
        }
    }

    /**
     * Function to get Status string to id base on Joomla
     *
     * @param string $status
     * @return int
     */
    private function getStatusId(string $status)
    {
        switch ($status) {
            case 'active':
                return Status::PUBLISHED;
            default:
                return Status::UNPUBLISHED;
        }
    }

    /**
     * Function to get Status name by id base on Joomla
     *
     * @param int $statusId
     * @return string
     */
    private function getStatusById(string $statusId)
    {
        switch ($statusId) {
            case Status::PUBLISHED:
                return 'active';
            default:
                return 'inactive';
        }
    }
}
