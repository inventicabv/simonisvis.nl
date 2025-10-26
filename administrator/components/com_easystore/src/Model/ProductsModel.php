<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Model;

use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;
use Joomla\CMS\MVC\Model\ListModel;
use JoomShaper\Component\EasyStore\Administrator\Concerns\Taxable;
use JoomShaper\Component\EasyStore\Site\Traits\ProductMedia;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreProductHelper;
use JoomShaper\Component\EasyStore\Administrator\Supports\Shop;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * This models supports retrieving a list of products.
 *
 * @since  1.0.0
 */
class ProductsModel extends ListModel
{
    use ProductMedia;
    use Taxable;

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
                'catid',
                'a.catid',
                'brand_id',
                'a.brand_id',
                'title',
                'a.title',
                'published',
                'a.published',
                'featured',
                'a.featured',
                'access',
                'a.access',
                'language',
                'a.language',
                'checked_out',
                'a.checked_out',
                'checked_out_time',
                'a.checked_out_time',
                'created',
                'a.created',
                'created_by',
                'a.created_by',
                'regular_price',
                'a.regular_price',
                'inventory_status',
                'a.inventory_status',
                'ordering',
                'a.ordering',
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
    protected function populateState($ordering = 'a.id', $direction = 'desc')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);

        $featured = $this->getUserStateFromRequest($this->context . '.filter.featured', 'filter_featured', '');
        $this->setState('filter.featured', $featured);

        $brand = $this->getUserStateFromRequest($this->context . '.filter.brand', 'filter_brand', '');
        $this->setState('filter.brand', $brand);

        $access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
        $this->setState('filter.access', $access);

        $language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
        $this->setState('filter.language', $language);

        $category = $this->getUserStateFromRequest($this->context . '.filter.categories', 'filter_categories', '');
        $this->setState('filter.categories', $category);

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
        $id .= ':' . $this->getState('filter.access');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.language');
        $id .= ':' . $this->getState('filter.featured');
        $id .= ':' . $this->getState('filter.brand');
        $id .= ':' . $this->getState('filter.categories');

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

        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                'a.id, a.catid, a.brand_id, a.title, a.alias, a.published, a.access, a.description' .
                ', a.featured' .
                ', a.checked_out, a.checked_out_time, a.created_by' .
                ', a.language, a.ordering, a.has_sale, a.discount_type' .
                ', a.discount_value, a.inventory_status, a.regular_price, a.quantity, a.is_tracking_inventory, a.enable_out_of_stock_sell, a.is_taxable,
                a.weight, a.dimension, a.sku'
            )
        );
        $query->from($db->quoteName('#__easystore_products', 'a'));

        // Join over the language
        $query->select(
            [
                $db->quoteName('a.has_variants', 'has_variants'),
                $db->quoteName('l.title', 'language_title'),
                $db->quoteName('l.image', 'language_image'),
                $db->quoteName('c.title', 'cat_title'),
                $db->quoteName('b.title', 'brand_title'),
                $db->quoteName('b.alias', 'brand_alias'),
                $db->quoteName('b.image', 'brand_image'),
            ]
        )
            ->join('LEFT', $db->quoteName('#__languages', 'l'), $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('a.language'));

        // Join over the users for the checked out user.
        $query->select($db->quoteName('uc.name', 'editor'))
        ->join('LEFT', $db->quoteName('#__users', 'uc'), $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));

        $query->select($db->quoteName('ug.title', 'access_title'))
        ->join('LEFT', $db->quoteName('#__viewlevels', 'ug'), $db->quoteName('ug.id') . ' = ' . $db->quoteName('a.access'));

        // Get Category
        $query->join('LEFT', $db->quoteName('#__easystore_categories', 'c'), 'c.id = a.catid');

        // Brand
        $query->join('LEFT', $db->quoteName('#__easystore_brands', 'b'), 'b.id = a.brand_id');

        // Filter by access level.
        if ($access = (int) $this->getState('filter.access')) {
            $query->where($db->quoteName('a.access') . ' = :access')
            ->bind(':access', $access, ParameterType::INTEGER);
        }

        // Filter by published state
        $published = (string) $this->getState('filter.published');

        if (is_numeric($published)) {
            $published = (int) $published;
            $query->where($db->quoteName('a.published') . ' = :published')
            ->bind(':published', $published, ParameterType::INTEGER);
        } elseif ($published === '') {
            $query->whereIn($db->quoteName('a.published'), [0, 1]);
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
                    ],
                    'OR'
                );
                $query->bind(':title', $search)
                    ->bind(':alias', $search);
            }
        }

        // Filter on the language.
        if ($language = $this->getState('filter.language')) {
            $query->where($db->quoteName('a.language') . ' = :language')
                ->bind(':language', $language);
        }

        // Filter by feature
        if ($featured = $this->getState('filter.featured')) {
            $query->where($db->quoteName('a.featured') . ' = :featured')
                ->bind(':featured', $featured);
        }

        // Filter by category
        if ($category = $this->getState('filter.categories')) {
            $query->where($db->quoteName('a.catid') . ' = :categoryID')
                ->bind(':categoryID', $category);
        }

        // Filter by brand
        if ($brand = $this->getState('filter.brand')) {
            $query->where($db->quoteName('a.brand_id') . ' = :brandId')
                ->bind(':brandId', $brand);
        }

        // Add the list ordering clause
        $listOrdering = $this->getState('list.ordering', 'a.id');
        $listDirn     = $db->escape($this->getState('list.direction', 'DESC'));

        if ($listOrdering == 'a.access') {
            $query->order('a.access ' . $listDirn . ', a.id ' . $listDirn);
        } else {
            $query->order($db->escape($listOrdering) . ' ' . $listDirn);
        }

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

        // Get Discounted Price and Inventory Status.
        foreach ($items as &$item) {
            $item->tax_rate = $item->is_taxable && Shop::isTaxEnabled() ? $this->getProductTaxRate($item) : 0;

            $item->regular_price = Shop::isTaxEnabled() && Shop::isPriceDisplayedWithTax() && $item->is_taxable
                ? $item->regular_price + $this->getTaxableAmount($item->regular_price, $item->tax_rate)
                : $item->regular_price;

            $item->discounted_price = ($item->has_sale && $item->discount_value) ? EasyStoreHelper::calculateDiscountedPrice($item->discount_type, $item->discount_value, $item->regular_price) : 0;

            if ($item->has_variants) {
                $orm             = new EasyStoreDatabaseOrm();
                $variantMinPrice    = $orm->setColumns([
                    $orm->aggregateQuoteName('MIN', 'price', 'min_price'),
                ])
                    ->useRawColumns(true)
                    ->hasMany($item->id, '#__easystore_product_skus', 'product_id')
                    ->loadObject();

                $varient = $orm->setColumns([
                    'is_taxable',
                ])
                    ->hasMany($item->id, '#__easystore_product_skus', 'product_id')
                    ->updateQuery(function ($query) use ($orm) {
                        $query->order($orm->quoteName('ordering') . ' ASC');
                    })
                    ->loadObject();

                $variantMinPrice->min_price = Shop::isTaxEnabled() && Shop::isPriceDisplayedWithTax() && $varient->is_taxable
                    ? $variantMinPrice->min_price + $this->getTaxableAmount($variantMinPrice->min_price, $item->tax_rate)
                    : $variantMinPrice->min_price;

                $item->discounted_price          = ($item->has_sale && $item->discount_value) ? EasyStoreHelper::calculateDiscountedPrice($item->discount_type, $item->discount_value, (float) $variantMinPrice->min_price) : 0;
                $item->regular_price             = $variantMinPrice->min_price ?? 0;
            }

            if ($item->enable_out_of_stock_sell) {
                $item->inventory_status = Text::_('COM_EASYSTORE_PRODUCT_FIELD_INVENTORY_STATUS_IN_STOCK');
            } else {
                if (!$item->has_variants) {
                    $item->inventory_status = EasyStoreProductHelper::getStockStatus($item) ? Text::_('COM_EASYSTORE_PRODUCT_FIELD_INVENTORY_STATUS_IN_STOCK') : Text::_('COM_EASYSTORE_PRODUCT_FIELD_INVENTORY_STATUS_OUT_OF_STOCK');
                } else {
                    $item->inventory_status = (EasyStoreProductHelper::getVariantsStockStatus($item->id, $item->is_tracking_inventory)) ? Text::_('COM_EASYSTORE_PRODUCT_FIELD_INVENTORY_STATUS_IN_STOCK') : Text::_('COM_EASYSTORE_PRODUCT_FIELD_INVENTORY_STATUS_OUT_OF_STOCK');
                }
            }

            $item->thumbnail        = '';
            $media                  = $this->getMedia($item->id);

            if (!empty($media) && !empty($media->thumbnail)) {
                $item->thumbnail = $media->thumbnail->src;
            }
        }
        unset($item);

        return $items;
    }
}
