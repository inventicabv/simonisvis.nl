<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Helper;

use Throwable;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;
use Joomla\Database\DatabaseInterface;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper as EasyStoreAdminHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * EasyStore component helper.
 *
 * @since  1.0.0
 */
class FilterHelper
{
    public static function getCategories()
    {
        $input      = Factory::getApplication()->input;
        $ordering   = $input->get('filter_ordering_categories', 'ASC', 'STRING');
        $catid      = $input->get('catid', 0, 'INT');
        $categories = [];

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('id, title AS name, alias AS value, parent_id, level, path')
            ->from($db->quoteName('#__easystore_categories'))
            ->where($db->quoteName('published') . ' = 1');

        $parentId = empty($catid) ? self::getRootCategoryId() : $catid;

        $query->where($db->quoteName('parent_id') . ' = ' . $parentId);
        $query->order($db->quoteName('name') . ' ' . strtoupper($ordering));

        if (Multilanguage::isEnabled()) {
            $query->whereIn($db->quoteName('language'), [Factory::getApplication()->getLanguage()->getTag(), '*'], ParameterType::STRING);
        }

        try {
            $db->setQuery($query);
            $categories = $db->loadObjectList() ?? [];
        } catch (Throwable $error) {
            throw $error;
        }

        if (empty($categories)) {
            return [];
        }

        $allCategories = static::getAllCategories();

        foreach ($categories as &$category) {
            $categoryIds     = static::getCategoryDescendants($allCategories, $category->id);
            $category->count = static::getProductCount('category', ['categories' => $categoryIds], $allCategories);
        }

        unset($category);

        return $categories;
    }

    public static function getRootCategoryId()
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('id')
            ->from($db->quoteName('#__easystore_categories'))
            ->where($db->quoteName('alias') . ' = '  . $db->quote('root'))
            ->where($db->quoteName('published') . ' = 1');
            
        $db->setQuery($query);

        $id = $db->loadResult();

        return $id;
    }

    public static function getCategoryDescendants($allCategories, $catid)
    {
        $ids = [$catid];

        foreach ($allCategories as $categories) {
            if ((int) $categories->parent_id === (int) $catid) {
                $ids = array_merge($ids, static::getCategoryDescendants($allCategories, $categories->id));
            }
        }

        return $ids;
    }

    public static function getCategoriesDescendants($allCategories, $categoryIds)
    {
        if (empty($categoryIds) || !is_array($categoryIds)) {
            return [];
        }

        $descendants = [];

        foreach ($categoryIds as $categoryId) {
            $descendants = array_merge($descendants, static::getCategoryDescendants($allCategories, $categoryId));
        }

        return $descendants;
    }

    public static function getProductCount(string $key, array $options, array $allCategories)
    {
        $input = Factory::getApplication()->getInput();

        $view = $input->get('view', '', 'STRING');
        $collectionId = $input->get('id', 0, 'INT');

        $easystorePks = [];
        if ($view === 'collection' && !empty($collectionId)) {
            $easystorePks = CollectionHelper::getCollectionProducts($collectionId);
        }


        $filters = [
            'category'     => StringHelper::stringToArray($input->get('filter_categories', '', 'STRING'), ','),
            'tag'          => StringHelper::stringToArray($input->get('filter_tags', '', 'STRING'), ','),
            'brand'          => StringHelper::stringToArray($input->get('filter_brands', '', 'STRING'), ','),
            'availability' => $input->get('filter_inventory_status', '', 'STRING'),
            'price_range'  => (object) [
                'min' => $input->get('filter_min_price', null, 'FLOAT'),
                'max' => $input->get('filter_max_price', null, 'FLOAT'),
            ],
            'variants' => StringHelper::stringToArray($input->get('filter_variants', '', 'STRING')),
        ];

        $menuCategoryId = max(self::getRootCategoryId(), $input->get('catid', 0, 'INT'));

        unset($filters[$key]);

        $categoryIds = [];

        if ($key !== 'category') {
            $categoryIds = !empty($filters['category'])
                ? static::getCategoriesDescendants($allCategories, static::getIdsByAlias('#__easystore_categories', $filters['category']))
                : static::getCategoryDescendants($allCategories, $menuCategoryId);
        } else {
            $categoryIds = $options['categories'] ?? [];
        }

        if ($key !== 'variants') {
            $variants = !empty($filters['variants']) ? $filters['variants'] : [];
        } else {
            $variants = $options['variants'];
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('COUNT(DISTINCT products.id)')
            ->from(static::fromStatement($db, $variants))
            ->where($db->quoteName('products.published') . ' = 1');
        
        if (!empty($easystorePks)) {
            $query->whereIn($db->quoteName('products.id'), $easystorePks);
        }

        if (!empty($categoryIds)) {
            $query->whereIn($db->quoteName('catid'), $categoryIds);
        }

        if ($key !== 'tag') {
            $tagIds = !empty($filters['tag'])
                ? static::getIdsByAlias('#__easystore_tags', $filters['tag'])
                : [];
        } else {
            $tagIds = $options['tags'] ?? [];
        }

        if (!empty($tagIds)) {
            $query->join('LEFT', $db->quoteName('#__easystore_product_tag_map', 'product_tag') . ' ON (' . $db->quoteName('products.id') . ' = ' . $db->quoteName('product_tag.product_id') . ')')
                ->join('LEFT', $db->quoteName('#__easystore_tags', 'tags') . ' ON (' . $db->quoteName('tags.id') . ' = ' . $db->quoteName('product_tag.tag_id') . ')')
                ->whereIn($db->quoteName('product_tag.tag_id'), $tagIds);
        }

        if ($key !== 'brand') {
            $brandIds = !empty($filters['brand'])
                ? static::getIdsByAlias('#__easystore_brands', $filters['brand'])
                : [];
        } else {
            $brandIds = $options['brands'] ?? [];
        }

        if (!empty($brandIds)) {
            $query->whereIn($db->quoteName('brand_id'), $brandIds);
        }

        $availabilityMap = [
            'in-stock'     => 1,
            'out-of-stock' => 0,
        ];

        if ($key !== 'availability') {
            $status = !empty($filters['availability']) ? $availabilityMap[$filters['availability']] : null;
        } else {
            $status = $options['availability'] ?? null;
        }

        if (!is_null($status)) {
            $query = static::getAvailabilityCount($db, $query, $status);
        }

        if (!is_null($filters['price_range']->min) && !is_null($filters['price_range']->max)) {
            $query = static::getPriceRangeCount($db, $query, $filters['price_range']);
        }

        if (!empty($variants)) {
            $query->where($db->quoteName('products.has_combination') . ' > 0');
        }

        if (Multilanguage::isEnabled()) {
            $query->whereIn($db->quoteName('products.language'), [Factory::getApplication()->getLanguage()->getTag(), '*'], ParameterType::STRING);
        }

        try {
            $db->setQuery($query);

            return $db->loadResult() ?? 0;
        } catch (Throwable $error) {
            throw $error;
        }
    }

    protected static function fromStatement($db, $variants = '')
    {
        $fromQuery = $db->getQuery(true);

        $variantSql = '0 as has_combination';

        if (!empty($variants)) {
            $combination = is_array($variants) ? implode('|', $variants) : $variants;
            $variantSql  = 'SUM(CASE WHEN ps.combination_value REGEXP ' . $db->quote($combination) . ' THEN 1 ELSE 0 END) as has_combination';
        }

        $fromQuery->select(
            'p.*, ' .
            'SUM(CASE WHEN p.has_variants = 0 AND p.is_tracking_inventory = 0 AND p.inventory_status = 0 THEN 0
                WHEN p.has_variants = 0 AND p.is_tracking_inventory = 1 AND p.quantity <= 0 THEN 0
                WHEN p.has_variants = 1 AND p.is_tracking_inventory = 0 AND ps.inventory_status = 0 THEN 0
                WHEN p.has_variants = 1 AND p.is_tracking_inventory = 1 AND ps.inventory_amount <= 0 THEN 0
                ELSE 1
            END) AS out_of_stock_count, ' .
            'MIN(CASE WHEN p.has_variants = 0 THEN p.regular_price ELSE ps.price END) AS min_price, ' .
            'MAX(CASE WHEN p.has_variants = 0 THEN p.regular_price ELSE ps.price END) AS max_price, ' .
            $variantSql
        )
            ->from($db->quoteName('#__easystore_products', 'p'))
            ->join('LEFT', $db->quoteName('#__easystore_product_skus', 'ps') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('ps.product_id'))
            ->group($db->quoteName('p.id'));

        return '(' . $fromQuery->__toString() . ') AS products';
    }

    protected static function getPriceRangeCount($db, $query, $priceRange)
    {
        $min = $priceRange->min;
        $max = $priceRange->max;

        $query->where('(' . $db->quoteName('products.min_price') . ' BETWEEN ' . $min . ' AND ' . $max . ' OR ' . $db->quoteName('products.max_price') . ' BETWEEN ' . $min . ' AND ' . $max . ')');

        return $query;
    }

    protected static function getAvailabilityCount($db, $query, $status)
    {
        if (!$status) {
            $query->where($db->quoteName('products.out_of_stock_count') . ' = 0');
        } else {
            $query->where($db->quoteName('products.out_of_stock_count') . ' > 0');
        }

        return $query;
    }

    public static function getAllCategories()
    {
        static $categories = null;

        if (!is_null($categories)) {
            return $categories;
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('id, parent_id')
            ->from($db->quoteName('#__easystore_categories'))
            ->where($db->quoteName('published') . ' = 1');
            
        if (Multilanguage::isEnabled()) {
            $query->whereIn($db->quoteName('language'), [Factory::getApplication()->getLanguage()->getTag(), '*'], ParameterType::STRING);
        }

        $db->setQuery($query);

        $categories = $db->loadObjectList();

        return $categories;
    }

    public static function getVariants()
    {
        $input      = Factory::getApplication()->input;
        $ordering   = $input->get('filter_ordering_variants', 'ASC', 'STRING');

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        
        $query->select('options.name, options.type, values.name as value, values.color as hex_code')
            ->from($db->quoteName('#__easystore_product_options', 'options'))
            ->join('LEFT', $db->quoteName('#__easystore_product_option_values', 'values') . ' ON ' . $db->quoteName('values.option_id') . ' = ' . $db->quoteName('options.id'))
            ->join('LEFT', $db->quoteName('#__easystore_products', 'product') . ' ON ' . $db->quoteName('product.id') . ' = ' . $db->quoteName('options.product_id'))
            ->where($db->quoteName('product.published') . '=1')
            ->order($db->quoteName('options.name') . ' ' . strtoupper($ordering))
            ->group('values.name');

        $db->setQuery($query);

        try {
            $options       = $db->loadObjectList() ?? [];
            $allCategories = static::getAllCategories();

            return array_reduce($options, function ($result, $current) use ($allCategories) {
                if (!isset($result[$current->name])) {
                    $result[$current->name] = [];
                }

                $current->count           = static::getProductCount('variants', ['variants' => $current->value], $allCategories);
                $result[$current->name][] = $current;
                return $result;
            }, []);
        } catch (Throwable $error) {
            throw $error;
        }
    }

    protected static function getIdsByAlias($table, $aliasArray)
    {
        if (empty($aliasArray) || !is_array($aliasArray)) {
            return [];
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('id')
            ->from($db->quoteName($table))
            ->whereIn($db->quoteName('alias'), $aliasArray, ParameterType::STRING);

        try {
            $db->setQuery($query);
            return $db->loadColumn() ?? [];
        } catch (Throwable $error) {
            throw $error;
        }
    }

    public static function getTags()
    {
        $tags  = [];
        $input      = Factory::getApplication()->input;
        $ordering   = $input->get('filter_ordering_tags', 'ASC', 'STRING');
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('id, title AS name, alias AS value')
            ->from($db->quoteName('#__easystore_tags'))
            ->where($db->quoteName('published') . ' = 1');
        $query->order($db->quoteName('name') . ' ' . strtoupper($ordering));

        if (Multilanguage::isEnabled()) {
            $query->whereIn($db->quoteName('language'), [Factory::getApplication()->getLanguage()->getTag(), '*'], ParameterType::STRING);
        }
            
        $db->setQuery($query);

        try {
            $tags = $db->loadObjectList() ?? [];
        } catch (Throwable $error) {
            throw $error;
        }

        $allCategories = static::getAllCategories();

        foreach ($tags as &$tag) {
            $tag->count = static::getProductCount('tag', ['tags' => [$tag->id]], $allCategories);
        }

        unset($tag);

        return $tags;
    }


    /**
     * Filter by brands
     *
     * @return object
     * 
     * @since 1.5.0
     */
    public static function getBrands()
    {
        $brands  = [];
        $input      = Factory::getApplication()->input;
        $ordering   = $input->get('filter_ordering_brands', 'ASC', 'STRING');
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('id, title AS name, alias AS value')
            ->from($db->quoteName('#__easystore_brands'))
            ->where($db->quoteName('published') . ' = 1');
        $query->order($db->quoteName('name') . ' ' . strtoupper($ordering));

        if (Multilanguage::isEnabled()) {
            $query->whereIn($db->quoteName('language'), [Factory::getApplication()->getLanguage()->getTag(), '*'], ParameterType::STRING);
        }
            
        $db->setQuery($query);

        try {
            $brands = $db->loadObjectList() ?? [];
        } catch (Throwable $error) {
            throw $error;
        }

        $allCategories = static::getAllCategories();

        foreach ($brands as &$brand) {
            $brand->count = static::getProductCount('brand', ['brands' => [$brand->id]], $allCategories);
        }

        unset($brand);

        return $brands;
    }

    public static function getAvailability()
    {
        $allCategories = static::getAllCategories();

        return (object) [
            'in-stock' => (object) [
                'name'  => Text::_('COM_EASYSTORE_AVAILABILITY_IN_STOCK'),
                'value' => 'in-stock',
                'count' => static::getProductCount('availability', ['availability' => 1], $allCategories),
            ],

            'out-of-stock' => (object) [
                'name'  => Text::_('COM_EASYSTORE_AVAILABILITY_OUT_OF_STOCK'),
                'value' => 'out-of-stock',
                'count' => static::getProductCount('availability', ['availability' => 0], $allCategories),
            ],
        ];
    }

    public static function getPriceRange()
    {
        list($min, $max)           = EasyStoreHelper::getPrice();
        $settings                  = SettingsHelper::getSettings();
        $currency                  = $settings->get('general.currency', EasyStoreAdminHelper::getDefaultCurrency());
        $currencyFormat            = $settings->get('general.currencyFormat', 'short');
        [$full, $symbol]           = explode(':', $currency);

        return ['min' => $min ?? 0, 'max' => $max ?? 100, 'step' => 0.1, 'currency' => $currencyFormat === 'short' ? $symbol : $full];
    }

    public static function getSorting()
    {
        return (object) [

            'auto' => (object) [
                'name'  => Text::_('COM_EASYSTORE_SORT_BY_AUTO'),
                'value' => 'ordering',
            ],

            'featured' => (object) [
                'name'  => Text::_('COM_EASYSTORE_SORT_BY_FEATURED'),
                'value' => 'featured',
            ],

            'best_selling' => (object) [
                'name'  => Text::_('COM_EASYSTORE_SORT_BY_BEST_SELLING'),
                'value' => 'best_selling',
            ],

            'title-asc' => (object) [
                'name'  => Text::_('COM_EASYSTORE_SORT_BY_ALPHABETICALLY_A_Z'),
                'value' => 'title-asc',
            ],

            'title-desc' => (object) [
                'name'  => Text::_('COM_EASYSTORE_SORT_BY_ALPHABETICALLY_Z_A'),
                'value' => 'title-desc',
            ],

            'price-asc' => (object) [
                'name'  => Text::_('COM_EASYSTORE_SORT_BY_PRICE_LOW_TO_HIGH'),
                'value' => 'price-asc',
            ],

            'price-desc' => (object) [
                'name'  => Text::_('COM_EASYSTORE_SORT_BY_PRICE_HIGH_TO_LOW'),
                'value' => 'price-desc',
            ],

            'created-asc' => (object) [
                'name'  => Text::_('COM_EASYSTORE_SORT_BY_DATE_OLD_TO_NEW'),
                'value' => 'created-asc',
            ],

            'created-desc' => (object) [
                'name'  => Text::_('COM_EASYSTORE_SORT_BY_DATE_NEW_TO_OLD'),
                'value' => 'created-desc',
            ],
        ];
    }

    public static function getOrder($order)
    {
        $defaultOrder = ['best_selling', 'DESC'];

        if (empty($order)) {
            return $defaultOrder;
        }

        $orderArray = explode('-', $order, 2);

        if (empty($orderArray)) {
            return $defaultOrder;
        }

        $directionMap = [
            'featured'     => 'DESC',
            'best_selling' => 'DESC',
        ];

        switch (count($orderArray)) {
            case 1:
                return [$orderArray[0], $directionMap[$orderArray[0]] ?? 'ASC'];
            case 2:
                return [$orderArray[0], strtoupper($orderArray[1])];
        }

        return $defaultOrder;
    }
}
