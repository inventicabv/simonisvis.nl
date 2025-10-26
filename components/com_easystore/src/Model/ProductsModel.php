<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Database\ParameterType;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use JoomShaper\Component\EasyStore\Administrator\Concerns\Taxable;
use JoomShaper\Component\EasyStore\Administrator\Constants\ProductListSource;
use JoomShaper\Component\EasyStore\Site\Traits\WishList;
use JoomShaper\Component\EasyStore\Site\Traits\ProductItem;
use JoomShaper\Component\EasyStore\Site\Helper\FilterHelper;
use JoomShaper\Component\EasyStore\Site\Helper\ProductStock;
use JoomShaper\Component\EasyStore\Site\Helper\StringHelper;
use JoomShaper\Component\EasyStore\Site\Traits\Availability;
use JoomShaper\Component\EasyStore\Site\Traits\ProductMedia;
use JoomShaper\Component\EasyStore\Site\Traits\ProductOption;
use JoomShaper\Component\EasyStore\Site\Traits\ProductVariant;
use JoomShaper\Component\EasyStore\Administrator\Model\ProductModel;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper as SiteEasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper as AdminEasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Supports\Shop;
use JoomShaper\Component\EasyStore\Site\Helper\CollectionHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class ProductsModel extends ListModel
{
    use Taxable;
    use ProductMedia;
    use ProductOption;
    use ProductVariant;
    use Availability;
    use WishList;
    use ProductItem;

    public const AVAILABLE_FILTERS = [
        'filter_categories',
        'filter_tags',
        'filter_brands',
        'filter_variants',
        'filter_inventory_status',
        'filter_min_price',
        'filter_max_price',
        'filter_query',
        'filter_sortby'
    ];

    /**
     * Model context string.
     *
     * @var    string
     * @since  1.0.0
     */
    public $_context = 'com_easystore.products';

    /**
     * Store the products query locally for using to the addons
     *
     * @var string
     */
    protected $productsQuery;

    protected $tagIdsCache = [];

    protected $categoryIdsCache = [];

    public function __construct($config = [], ?MVCFactoryInterface $factory = null)
    {
        parent::__construct($config, $factory);
    }

    protected function populateState($ordering = 'ordering', $direction = 'ASC')
    {
        $app   = Factory::getApplication();
        $input = $app->getInput();

        // List state information
        $value = $input->get('limit', $app->get('list_limit', 0), 'uint');
        $this->setState('list.limit', $value);

        $value = $input->get('limitstart', 0, 'uint');
        $this->setState('list.start', $value);

        parent::populateState($ordering, $direction);
    }

    /**
     * Method to build an SQL query to load the list data.
     *
     * @return  DatabaseQuery  An SQL query
     *
     * @since   1.0.0
     */
    protected function getListQuery()
    {
        $input = Factory::getApplication()->input;

        $catid                = null;
        $catIds               = $this->getCategoryIds($input->get('filter_categories', '', 'STRING'));
        $tagIds               = $this->getTagIds($input->get('filter_tags', '', 'STRING'));
        $brandIds             = $this->getBrandIds($input->get('filter_brands', '', 'STRING'));
        $filterVariants       = $this->getVariantNames($input->get('filter_variants', '', 'STRING'));
        $inventoryStatusValue = $this->getInventoryStatusValue($input->get('filter_inventory_status', '', 'STRING'));
        $min                  = $input->get('filter_min_price', 0, 'FLOAT');
        $max                  = $input->get('filter_max_price', 100, 'FLOAT');
        $search               = $input->get('filter_query', '', 'STRING');
        $sortBy               = 'ordering';

        $attributes   = $this->getState('attr');
        $source       = $attributes['source'] ?? null;
        $easystorePks = $this->getState('easystore.pks', []);

        // Check if the view is collection then update the attributes by the collections metadata
        $view = $input->get('view', '', 'STRING');
        $collectionId = $input->get('id', 0, 'INT');

        /**
         * If we are requesting the products from the collection view then set the source to collection
         * and update the attributes by the collection id, that is, the collection id from the menu item has more priority than the one from the addon.
         *
         * @since 1.4.0
         */
        if ($view === 'collection') {
            $attributes['source'] = ProductListSource::COLLECTION;
            $source = ProductListSource::COLLECTION;
            $attributes['collection_id'] = $collectionId;
        }

        // For the collection type source we need to get the products associated with the collection
        if ($source === ProductListSource::COLLECTION && !empty($attributes['collection_id'])) {
            $easystorePks = CollectionHelper::getCollectionProducts($attributes['collection_id']);

            // If there are no products associated with the collection then return an empty query
            if (empty($easystorePks)) {
                return '';
            }
        }

        if (isset($attributes['start']) && isset($attributes['limit'])) {
            $this->setState('list.start', $attributes['start']);
            $this->setState('list.limit', $attributes['limit']);
        }

        if (!empty($attributes['catid'])) {
            $catAlias = $this->getAliasByCatId($attributes['catid']);
            $catid    = $this->getCategoryIds($catAlias);
        }

        $menuCatId = $input->get('catid', 0, 'INT');

        if (!empty($menuCatId)) {
            $catAlias = $this->getAliasByCatId($menuCatId);
            $catid    = $this->getCategoryIds($catAlias);
        }

        // Create the base query
        $query = $this->createBaseQuery($easystorePks);

        $skipCategoryFilter = !empty($easystorePks);

        // Apply filters
        if (!$skipCategoryFilter) {
            $query = $this->filterByCategory($query, $catid);
            $query = $this->filterByCategory($query, $catIds);
            $query = $this->filterByTags($query, $tagIds);
            $query = $this->filterByBrands($query, $brandIds);
            $query = $this->filterByVariants($query, $filterVariants);
            $query = $this->filterByInventoryStatus($query, $inventoryStatusValue);
            $query = $this->filterByPriceRange($query, $min, $max);
            $query = $this->filterBySearchQuery($query, $search);
        }

        if (!is_null($source)) {
            switch ($source) {
                case ProductListSource::LATEST:
                    $sortBy = 'created-desc';
                    break;
                case ProductListSource::OLDEST:
                    $sortBy = 'created-asc';
                    break;
                case ProductListSource::ON_SALE:
                    $query = $this->filterByOnSale($query);
                    break;
                case ProductListSource::BEST_SELLING:
                    $sortBy = 'best_selling';
                    break;
                case ProductListSource::FEATURED:
                    $query = $this->filterByFeatured($query);
                    break;
                case ProductListSource::WISHLIST:
                    $query = $this->filterByWishlist($query);
                    break;
                case ProductListSource::COLLECTION:
                default:
                    break;
            }
        }

        $sortBy = $input->get('filter_sortby', $sortBy, 'STRING');
        $query  = $this->orderBy($query, $sortBy);

        $query = $this->filterByLanguage($query);

        if (!empty($attributes['pagination'])) {
            $params = (object) [
                'query' => clone $query,
                'limit' => $this->getState('list.limit', 20),
                'start' => $this->getState('list.start', 0),
            ];

            $this->loadPaginationStatusData($params);
        }

        return $query;
    }

    public function loadPaginationStatusData($data)
    {
        /** @var CMSApplication */
        $app      = Factory::getApplication();
        $document = $app->getDocument();
        $query    = $data->query;
        $limit    = $data->limit;
        $start    = $data->start;

        $query->clear('select')->clear('group')->clear('order')->select('count(distinct a.id) as total');
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery($query);
        $total = $db->loadResult() ?? 0;

        $pagination = (object) [
            'total'       => $total,
            'page'        => floor(($start + 1) / $limit) + 1,
            'total_pages' => ceil($total / $limit),
            'limit'       => $limit,
            'start'       => $start,
            'range_start' => $start + 1,
            'range_end'   => min($total, $start + $limit),
            'range'       => ($start + 1) . '-' . min($total, $start + $limit),
            'loaded'      => true,
        ];

        $document->addScriptOptions('easystore.pagination', $pagination);
    }

    /**
     * Get Category Alias form id
     *
     * @param int $catid
     * @return string|null
     */
    private function getAliasByCatId($catid)
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select('alias')
            ->from($db->quoteName('#__easystore_categories'))
            ->where($db->quoteName('id') . ' = ' . $catid);

        $db->setQuery($query);

        return $db->loadObject()->alias ?? null;
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
        $items = new \stdClass();
        $orm = new EasyStoreDatabaseOrm();
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $input = $app->input;

        $items->products = parent::getItems();
        $toRemoveProducts = [];

        $inventoryStatusValue = $this->getInventoryStatusValue($input->get('filter_inventory_status', '', 'STRING'));

        if (isset($items->products) && is_array($items->products)) {
            foreach ($items->products as &$item) {
                $this->processItemLinks($item);
                $this->processItemPrices($item);
                $this->processItemVariants($item, $orm);
                $this->processItemTags($item);
                $this->processItemReview($item);
                $this->processItemWishlist($item, $user);
                $this->processItemStockAndAvailability($item);

                if (!$inventoryStatusValue) {
                    if ($this->shouldRemoveProduct($item)) {
                        $toRemoveProducts[] = $item->id;
                    }
                }
            }

            // Remove products if inventory status filtering is applied
            if (!$inventoryStatusValue) {
                $items->products = array_filter($items->products, function ($item) use ($toRemoveProducts) {
                    return !in_array($item->id, $toRemoveProducts);
                });
            }
        }

        return $items;
    }

    /**
     * Processes the links for a given item by generating product and category URLs.
     *
     * This method modifies the passed `$item` object by adding a `link` property
     * for the product URL and a `category_link` property for the category URL.
     * The links are created using Joomla's `Route::_()` function to ensure proper routing.
     *
     * @param object $item The item object passed by reference. The method appends two properties:
     * - `$item->link`: URL for the individual product page.
     * - `$item->category_link`: URL for the product's category page.
     *
     * @return void
     *
     * @since 1.3.0
     */
    private function processItemLinks(&$item)
    {
        $item->link = Route::_('index.php?option=com_easystore&view=product&id=' . $item->id . '&catid=' . $item->catid);
        $item->category_link = Route::_('index.php?option=com_easystore&view=products&catid=' . $item->catid);
    }


    /**
     * Processes the pricing details for a given item by calculating tax, discounts, and formatting the prices.
     *
     * This method updates the passed `$item` object with various price-related fields, including tax rate,
     * regular price with tax, discounted price, and formatted price strings with currency and segment support.
     *
     * The following properties are added or updated in the `$item` object:
     * - `$item->tax_rate`: The applicable tax rate (set to 0 if not taxable).
     * - `$item->regular_price`: The regular price with tax included if the item is taxable.
     * - `$item->discounted_price`: The discounted price if applicable; otherwise 0.
     * - `$item->regular_price_with_currency`: Formatted regular price with the currency symbol.
     * - `$item->discounted_price_with_currency`: Formatted discounted price with the currency symbol.
     * - `$item->regular_price_with_segments`: Regular price formatted with currency and segmenting.
     * - `$item->discounted_price_with_segments`: Discounted price formatted with currency and segmenting.
     *
     * @param object $item The item object passed by reference. The method updates price-related properties within the object.
     *
     * @return void
     *
     * @since 1.3.0
     */
    private function processItemPrices(&$item)
    {
        $isTaxIncluded = Shop::isTaxEnabled();
        $item->tax_rate = $item->is_taxable
            ? $this->getProductTaxRate($item)
            : 0;

        $item->regular_price = $item->is_taxable && !$isTaxIncluded
            ? $item->regular_price + $this->getTaxableAmount($item->regular_price, $item->tax_rate)
            : $item->regular_price;

        $item->discounted_price = ($item->has_sale && $item->discount_value)
            ? AdminEasyStoreHelper::calculateDiscountedPrice($item->discount_type, $item->discount_value, $item->regular_price)
            : 0;

        $item->regular_price_with_currency = AdminEasyStoreHelper::formatCurrency($item->regular_price);
        $item->discounted_price_with_currency = AdminEasyStoreHelper::formatCurrency($item->discounted_price);
        $item->regular_price_with_segments = AdminEasyStoreHelper::formatCurrency($item->regular_price, true);
        $item->discounted_price_with_segments = AdminEasyStoreHelper::formatCurrency($item->discounted_price, true);
    }


    /**
     * Processes the variants, media, and pricing details for a given item, including calculating minimum price and discounts.
     *
     * This method updates the `$item` object with information about its variants, media, options, and price calculations,
     * including the minimum price for the item or its variants, tax rates, and formatted price outputs.
     *
     * The following properties are added or updated in the `$item` object:
     * - `$item->media`: Media associated with the item.
     * - `$item->options`: Product options for the item.
     * - `$item->variants`: Variants of the product.
     * - `$item->min_price`: Minimum price of the product or its variants, including tax if applicable.
     * - `$item->tax_rate`: Tax rate based on whether the product or variant is taxable.
     * - `$item->min_price_with_currency`: Formatted minimum price with the currency symbol.
     * - `$item->min_price_with_segments`: Formatted minimum price with currency and segmenting.
     * - `$item->discounted_min_price`: Discounted minimum price if applicable.
     * - `$item->discounted_min_price_with_currency`: Discounted minimum price formatted with the currency symbol.
     * - `$item->discounted_min_price_with_segments`: Discounted minimum price formatted with currency and segmenting.
     *
     * @param object $item The item object passed by reference. The method updates variant and price-related properties within the object.
     * @param object $orm The ORM object used to query the product variants and prices.
     *
     * @return void
     *
     * @since 1.3.0
     */
    private function processItemVariants(&$item, $orm)
    {
        $item->media = $this->getMedia($item->id);
        $item->options = $this->getOptions($item->id);
        $item->variants = $this->getVariants($item->id, $item);

        if ($item->has_variants) {
            $variantMinPriceData = $orm->setColumns([
                    'price',
                    'is_taxable',
                    'id',
                ])
                ->useRawColumns(true)
                ->hasMany($item->id, '#__easystore_product_skus', 'product_id')
                ->loadObjectList();

            $filteredData = min($variantMinPriceData);
            $item->min_price = $this->calculateMinPrice($filteredData, $item);
            $item->tax_rate = $filteredData->is_taxable ? $this->getProductTaxRate($item) : 0;
        } else {
            $item->min_price = Shop::applyTaxToPrice($item->is_taxable)
                ? $item->min_price + $this->getTaxableAmount($item->min_price, $item->tax_rate)
                : $item->min_price;
        }

        $item->min_price_with_currency = AdminEasyStoreHelper::formatCurrency($item->min_price);
        $item->min_price_with_segments = AdminEasyStoreHelper::formatCurrency($item->min_price, true);

        $item->discounted_min_price = ($item->has_sale && $item->discount_value)
            ? AdminEasyStoreHelper::calculateDiscountedPrice($item->discount_type, $item->discount_value, $item->min_price)
            : 0;

        $item->discounted_min_price_with_currency = AdminEasyStoreHelper::formatCurrency($item->discounted_min_price);
        $item->discounted_min_price_with_segments = AdminEasyStoreHelper::formatCurrency($item->discounted_min_price, true);
    }


    /**
     * Calculates the minimum price for a product variant, including tax if applicable.
     *
     * This method determines the minimum price based on whether the variant is taxable. If the variant is taxable,
     * it adds the applicable tax amount to the minimum price. Otherwise, it returns the minimum price as is.
     *
     * @param object $variantMinPriceData An object containing the variant's minimum price and taxability status.
     * - `$variantMinPriceData->price`: The minimum price of the variant.
     * - `$variantMinPriceData->is_taxable`: Indicates whether the variant is subject to tax.
     * @param object $item The item object, used to retrieve the tax rate.
     * - `$item->tax_rate`: The tax rate applicable to the item.
     *
     * @return float The calculated minimum price, including tax if the variant is taxable.
     *
     * @since 1.3.0
     */
    private function calculateMinPrice($variantMinPriceData, $item)
    {
        $isTaxable = Shop::applyTaxToPrice($variantMinPriceData->is_taxable);
        $minPrice = $variantMinPriceData->price;

        return $isTaxable
            ? $minPrice + $this->getTaxableAmount($minPrice, $item->tax_rate)
            : $minPrice;
    }

    /**
     * Processes and assigns tags to a given item, creating links for each tag.
     *
     * This method retrieves tags associated with the specified item and adds a `tags` property to the
     * `$item` object. Each tag is enriched with a link that directs to a products view filtered by that tag.
     *
     * The following property is added to the `$item` object:
     * - `$item->tags`: An array of tags associated with the item, each containing a `tag_link` property
     *   that points to the products view filtered by the respective tag.
     *
     * @param object $item The item object passed by reference. The method updates the tags property within the object.
     *
     * @return void
     *
     * @since 1.3.0
     */
    private function processItemTags(&$item)
    {
        $tags = SiteEasyStoreHelper::getTags($item->id);

        if (!empty($tags)) {
            $item->tags = array_map(function ($tag) {
                $tag['tag_link'] = Route::_('index.php?option=com_easystore&view=products&tags=' . $tag['tag_id']);
                return $tag;
            }, $tags);
        }
    }

    /**
     * Retrieves and assigns review data for a given item.
     *
     * This method fetches review data associated with the specified item and adds it to the
     * `$item` object. The review data typically includes information such as ratings and comments
     * from users who have reviewed the product.
     *
     * The following property is added to the `$item` object:
     * - `$item->reviewData`: Contains the review data for the item, retrieved from the ProductModel.
     *
     * @param object $item The item object passed by reference. The method updates the reviewData property within the object.
     *
     * @return void
     *
     * @since 1.3.0
     */
    private function processItemReview(&$item)
    {
        $item->reviewData = ProductModel::getReviewData($item->id);
    }

    /**
     * Checks if a given item is in the user's wishlist and updates the item object accordingly.
     *
     * This method determines if the specified product is included in the user's wishlist and adds
     * an `inWishList` property to the `$item` object. If the user is not logged in, the wishlist status
     * will not be checked or set.
     *
     * The following property is added to the `$item` object:
     * - `$item->inWishList`: A boolean value indicating whether the item is in the user's wishlist.
     *   It is true if the item is in the wishlist and false otherwise.
     *
     * @param object $item The item object passed by reference. The method updates the inWishList property within the object.
     * @param object $user The user object containing user details, including the user ID.
     *
     * @return void
     *
     * @since 1.3.0
     */
    private function processItemWishlist(&$item, $user)
    {
        if ($user->id) {
            $item->inWishList = $this->isProductInWishlist($item->id, $user->id);
        }
    }


    /**
     * Processes stock and availability information for a given item.
     *
     * This method retrieves and sets various stock and availability attributes for the specified item.
     * It checks for variants and their availability, determines the active variant, retrieves the product
     * thumbnail, and calculates the stock status. The method enriches the `$item` object with relevant
     * stock and availability data.
     *
     * The following properties are added to the `$item` object:
     * - `$item->availability`: An array representing the availability status of the item, based on its variants.
     * - `$item->active_variant`: The currently active variant of the product, including its tax rate.
     * - `$item->thumbnail`: The thumbnail image for the product.
     * - `$item->prices`: The price segments for the product.
     * - `$item->stock`: The stock details for the product.
     * - `$item->overallStock`: The overall stock status of the product, which may indicate if it is out of stock or unavailable.
     *
     * @param object $item The item object passed by reference. The method updates various stock and availability properties within the object.
     *
     * @return void
     *
     * @since 1.3.0
     */
    private function processItemStockAndAvailability(&$item)
    {
        $item->availability = !empty($item->variants)
            ? $this->checkAvailability($item->variants, $item->options)
            : [];

        $item->active_variant = $this->getVariantById($item, null);
        if (isset($item->active_variant)) {
            $item->active_variant->tax_rate = $item->tax_rate;
        }
        $item->thumbnail = $this->getProductThumbnail($item);
        $item->prices = $this->getPriceSegments($item, true);
        $item->stock = $this->getProductStock($item);
        $item->overallStock = in_array($item->stock->status, [ProductStock::OUT_OF_STOCK, ProductStock::UNAVAILABLE], true)
            ? $this->getOverallProductStockStatus($item)
            : $item->stock->status;
    }


    /**
     * Determines whether a product should be removed based on its variants' inventory status.
     *
     * This method checks the inventory status of a product's variants to decide if the product
     * should be removed from availability. If the product has variants, it counts how many of
     * them are out of stock or unavailable based on the inventory tracking settings. If all variants
     * are out of stock or unavailable, the method returns true, indicating that the product should
     * be removed; otherwise, it returns false.
     *
     * @param object $item The item object containing product and variant information, including
     *                     inventory tracking and status properties.
     *
     * @return bool Returns true if the product should be removed (i.e., all variants are out of
     *              stock or unavailable), or false otherwise.
     *
     * @since 1.3.0
     */
    private function shouldRemoveProduct($item)
    {
        $counter = 0;
        $variantCount = count($item->variants);

        if ($item->has_variants) {
            foreach ($item->variants as $variant) {
                if ($item->is_tracking_inventory && $variant->inventory_amount <= 0) {
                    $counter++;
                } elseif (!$item->is_tracking_inventory && !$variant->inventory_status) {
                    $counter++;
                }
            }

            return $counter == $variantCount;
        }

        return false;
    }

    protected function filterBySearchQuery($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        $search = trim($search);

        $db           = $this->getDatabase();
        $search       = StringHelper::toRegexSafeString($search);
        $tagsSubQuery = $this->getProductsFromTags($search);

        $query->where($db->quoteName('a.title') . ' REGEXP ' . $db->quote($search) . ' OR ' . $db->quoteName('a.id') . ' IN (' . $tagsSubQuery . ')');

        return $query;
    }

    protected function getProductsFromTags($search)
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select('DISTINCT ptm.product_id')
            ->from($db->quoteName('#__easystore_tags', 't'))
            ->join('LEFT', $db->quoteName('#__easystore_product_tag_map', 'ptm') . ' ON (' . $db->quoteName('t.id') . ' = ' . $db->quoteName('ptm.tag_id') . ')')
            ->where($db->quoteName('t.title') . ' REGEXP ' . $db->quote($search));

        return $query;
    }

    /**
     * Sub Query to get the discounted price.
     *
     * @param   string   aParam  Param
     * @return  DatabaseQuery  An SQL query
     * @since   1.0.0
     */
    public function getSubQuery()
    {
        $db       = $this->getDatabase();
        $subQuery = $db->getQuery(true);

        $subQuery->select(
            [
                'CASE
                    WHEN (' . $db->quoteName('sub.has_sale') . ' = 1 AND ' . $db->quoteName('sub.discount_value') . ' > 0.00)
                        THEN
                            CASE
                                WHEN (' . $db->quoteName('sub.discount_type') . ' = "percent")
                                    THEN (' . $db->quoteName('sub.regular_price') . ' - (' . $db->quoteName('sub.regular_price') . ' * ' . $db->quoteName('sub.discount_value') . ') / 100)
                                ELSE (' . $db->quoteName('sub.regular_price') . ' - ' . $db->quoteName('sub.discount_value') . ')
                            END
                        ELSE sub.regular_price
                END AS filter_price',
            ]
        )
            ->from($db->quoteName('#__easystore_products', 'sub'))
            ->where($db->quoteName('a.id') . ' = ' . $db->quoteName('sub.id'));

        return $subQuery;
    }

    protected function createBaseQuery($pks = [])
    {
        $user  = $this->getCurrentUser();
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query
            ->select([
                'DISTINCT a.*',
                $db->quoteName('c.id', 'category_id'),
                $db->quoteName('c.title', 'category_title'),
                $db->quoteName('c.alias', 'category_alias'),
                '(CASE WHEN ' . $db->quoteName('a.has_variants') . ' = 1 THEN MIN(' . $db->quoteName('ps.price') . ') ELSE ' . $db->quoteName('a.regular_price') . ' END) as min_price',
            ])
            ->from($db->quoteName('#__easystore_products', 'a'))
            ->join('LEFT', $db->quoteName('#__easystore_categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
            ->join('LEFT', $db->quoteName('#__easystore_product_skus', 'ps'), $db->quoteName('ps.product_id') . ' = ' . $db->quoteName('a.id'))
            ->where($db->quoteName('a.published') . ' = 1')
            ->where($db->quoteName('c.published') . ' = 1')
            ->group($db->quoteName('a.id'));

        if (!empty($pks)) {
            $query->where($db->quoteName('a.id') . ' IN (' . implode(',', $pks) . ')');
        }

        $ordering = Factory::getApplication()->input->get('filter_sortby', 'ordering', 'STRING');
        $direction = strtoupper($this->getState('list.direction', 'ASC'));

        if ($ordering === 'ordering') {
            $query->order($db->quoteName('a.ordering') . ' ' . $direction);
        }

        $groups = $user->getAuthorisedViewLevels();

        if (!empty($groups)) {
            $query->whereIn($db->quoteName('a.access'), $groups);
        }

        return $query;
    }

    protected function filterByCategory($query, $catIds)
    {
        if (empty($catIds)) {
            return $query;
        }

        if (!is_array($catIds)) {
            $catIds = [$catIds];
        }

        return $query->whereIn($this->getDatabase()->quoteName('a.catid'), $catIds);
    }

    protected function filterByTags($query, $tagIds)
    {
        if (!empty($tagIds)) {
            return $query->join('LEFT', $this->getDatabase()->quoteName('#__easystore_product_tag_map', 'pro_tag_map') . ' ON pro_tag_map.product_id = a.id')
                ->whereIn($this->getDatabase()->quoteName('pro_tag_map.tag_id'), $tagIds);
        }

        return $query;
    }

    protected function filterByBrands($query, $brandIds)
    {
        if (!empty($brandIds)) {
            return $query->join('LEFT', $this->getDatabase()->quoteName('#__easystore_brands', 'brand') . ' ON brand.id = a.brand_id')->whereIn($this->getDatabase()->quoteName('brand.id'), $brandIds);
        }

        return $query;
    }

    protected function filterByVariants($query, $variantNames)
    {
        if (!empty($variantNames) && is_array($variantNames)) {
            return $query->join('INNER', $this->getDatabase()->quoteName('#__easystore_product_option_values', 'v') . ' ON ' . $this->getDatabase()->quoteName('a.id') . ' = ' . $this->getDatabase()->quoteName('v.product_id'))
                ->whereIn($this->getDatabase()->quoteName('v.name'), $variantNames, ParameterType::STRING);
        }

        return $query;
    }

    protected function filterByInventoryStatus($query, $inventoryStatusValue)
    {
        if ($inventoryStatusValue !== 'all') {
            $quantityOperator = '>';
            if (!$inventoryStatusValue) {
                $quantityOperator = '<=';
            }
            return $query->where(
                '((' . $this->getDatabase()->quoteName('a.has_variants') . ' = 0 AND ' .
                $this->getDatabase()->quoteName('a.is_tracking_inventory') . ' = 0 AND ' .
                $this->getDatabase()->quoteName('a.inventory_status') . ' = ' . (int) $inventoryStatusValue . ') OR (' .
                $this->getDatabase()->quoteName('a.has_variants') . ' = 0 AND ' .
                $this->getDatabase()->quoteName('a.is_tracking_inventory') . ' = 1 AND ' .
                $this->getDatabase()->quoteName('a.quantity') . $quantityOperator . ' 0 ) OR (' .
                $this->getDatabase()->quoteName('a.has_variants') . ' = 1 AND ' .
                $this->getDatabase()->quoteName('a.is_tracking_inventory') . ' = 0 AND ' .
                $this->getDatabase()->quoteName('ps.inventory_status') . ' = ' . (int) $inventoryStatusValue . ') OR (' .
                $this->getDatabase()->quoteName('a.has_variants') . ' = 1 AND ' .
                $this->getDatabase()->quoteName('a.is_tracking_inventory') . ' = 1 AND ' .
                $this->getDatabase()->quoteName('ps.inventory_amount') . $quantityOperator . ' 0))'
            );
        }

        return $query;
    }

    protected function filterByPriceRange($query, $min, $max)
    {
        if (!empty($min) && !empty(trim($max))) {
            return $query->where('(
                CASE
                    WHEN  ' . $this->getDatabase()->quoteName("a.has_variants") . ' = 0
                    THEN ' . $this->getDatabase()->quoteName("a.regular_price") . '
                    ELSE ' . $this->getDatabase()->quoteName("ps.price") . '
                END) BETWEEN ' . $min . ' AND ' . $max);
        }

        return $query;
    }

    protected function filterByWishlist($query)
    {
        $db   = $this->getDatabase();
        $user = Factory::getApplication()->getIdentity();

        if ($user->guest) {
            $query->where($db->quoteName('a.id') . ' = 0');
            return $query;
        }

        $subQuery = $db->getQuery(true);
        $subQuery->select($db->quoteName('wishlist.product_id'))
            ->from($db->quoteName('#__easystore_wishlist', 'wishlist'))
            ->where($db->quoteName('wishlist.user_id') . ' = ' . $db->quote($user->id));

        $query->where($db->quoteName('a.id') . ' IN (' . $subQuery . ')');

        return $query;
    }

    protected function filterByOnSale($query)
    {
        $query->where($this->getDatabase()->quoteName('a.has_sale') . ' = 1');

        return $query;
    }

    protected function filterByFeatured($query)
    {
        $query->where($this->getDatabase()->quoteName('a.featured') . ' = 1');

        return $query;
    }

    protected function filterByLanguage($query)
    {
        if (Multilanguage::isEnabled()) {
            return $query->whereIn($this->getDatabase()->quoteName('a.language'), [Factory::getApplication()->getLanguage()->getTag(), '*'], ParameterType::STRING);
        }

        return $query;
    }

    protected function orderByBestSellingProducts($query)
    {
        $db = $this->getDatabase();

        $query
            ->select('COUNT(DISTINCT opm.order_id) AS frequency')
            ->join('LEFT', $db->quoteName('#__easystore_order_product_map', 'opm') . ' ON (' . $db->quoteName('a.id') . ' = ' . $db->quoteName('opm.product_id') . ')')
            ->group($db->quoteName('a.id'))
            ->order($db->quoteName('frequency') . ' DESC')
            ->order($db->quoteName('created') . ' DESC');

        return $query;
    }

    protected function orderBy($query, $sortBy)
    {
        $db                  = $this->getDatabase();
        [$field, $direction] = FilterHelper::getOrder($sortBy);

        if ($field === 'price') {
            $column = $db->quoteName('min_price');
        } else {
            $column = $db->quoteName("a.{$field}");
        }

        switch ($field) {
            case 'best_selling':
                return $this->orderByBestSellingProducts($query);
            default:
                $query->order($column . ' ' . $direction);
                return $query;
        }

        return $query;
    }

    protected function getCategoryIds($filterCategories)
    {
        $filterCategories = $filterCategories ? explode(',', $filterCategories) : [$filterCategories];

        return (!empty($filterCategories) && is_array($filterCategories))
        ? $this->getCategoryIdsFromAliases($filterCategories)
        : [];
    }

    protected function getCategoryIdsFromAliases($aliases)
    {
        $cacheKey = md5(implode(',', $aliases));
        if (isset($this->categoryIdsCache[$cacheKey])) {
            return $this->categoryIdsCache[$cacheKey];
        }

        $db       = $this->getDatabase();
        $subQuery = $db->getQuery(true);

        $subQuery->select($db->quoteName('c.id'))
            ->from($db->quoteName('#__easystore_categories', 'c'))
            ->whereIn($db->quoteName('c.alias'), $aliases, ParameterType::STRING);

        $db->setQuery($subQuery);

        $catIds = $db->loadColumn();

        if (!empty($catIds)) {
            $allCategories = $this->getAllCategories();
            $parentIds     = [];

            foreach ($catIds as $catId) {
                $this->getCategoryIdList($allCategories, $catId, $parentIds);
            }

            $catIds = empty($parentIds) ? $catIds : array_merge($catIds, $parentIds);
        }

        $this->categoryIdsCache[$cacheKey] = $catIds;

        return $catIds;
    }

    public function getCategoryIdList($allCategories, $catid, &$ids)
    {
        $ids[] = $catid;

        foreach ($allCategories as $categories) {
            if ($categories->parent_id == $catid) {
                $this->getCategoryIdList($allCategories, $categories->id, $ids);
            }
        }
    }

    public function getAllCategories()
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('cat.id, cat.parent_id')
            ->from($db->quoteName('#__easystore_categories', 'cat'))
            ->where($db->quoteName('cat.published') . ' = 1');

        if (Multilanguage::isEnabled()) {
            $query->whereIn($db->quoteName('cat.language'), [Factory::getApplication()->getLanguage()->getTag(), '*'], ParameterType::STRING);
        }

        $db->setQuery($query);

        return $db->loadObjectList();
    }

    protected function getTagIds($filterTags)
    {
        $filterTags = $filterTags ? explode(',', $filterTags) : [$filterTags];
        return (!empty($filterTags) && is_array($filterTags))
        ? $this->getTagIdsFromAliases($filterTags)
        : [];
    }

    protected function getTagIdsFromAliases($aliases)
    {
        $cacheKey = md5(implode(',', $aliases));
        if (isset($this->tagIdsCache[$cacheKey])) {
            return $this->tagIdsCache[$cacheKey];
        }

        $db       = $this->getDatabase();
        $subQuery = $db->getQuery(true);

        $subQuery->select($db->quoteName('t.id'))
            ->from($db->quoteName('#__easystore_tags', 't'))
            ->whereIn($db->quoteName('t.alias'), $aliases, ParameterType::STRING);

        $db->setQuery($subQuery);

        $tagIds = $db->loadColumn();
        $this->tagIdsCache[$cacheKey] = empty($tagIds) ? [] : $tagIds;

        return $this->tagIdsCache[$cacheKey];
    }

    protected function getBrandIds($filterBrands)
    {
        $filterBrands = $filterBrands ? explode(',', $filterBrands) : [$filterBrands];
        return (!empty($filterBrands) && is_array($filterBrands))
        ? $this->getBrandIdsFromAliases($filterBrands)
        : [];
    }

    protected function getBrandIdsFromAliases($aliases)
    {
        $db       = $this->getDatabase();
        $subQuery = $db->getQuery(true);

        $subQuery->select($db->quoteName('t.id'))
            ->from($db->quoteName('#__easystore_brands', 't'))
            ->whereIn($db->quoteName('t.alias'), $aliases, ParameterType::STRING);

        $db->setQuery($subQuery);

        $tagIds = $db->loadColumn();

        return !empty($tagIds) ? $tagIds : [];
    }

    protected function getVariantNames($filterVariants)
    {
        $filterVariants = $filterVariants ? explode(',', $filterVariants) : $filterVariants;
        return (!empty($filterVariants) && is_array($filterVariants))
        ? $filterVariants
        : [];
    }

    protected function getInventoryStatusValue($inventoryStatus)
    {
        $inventoryStatusValue = ['out-of-stock' => 0, 'in-stock' => 1];

        return (!empty($inventoryStatus) && $inventoryStatus !== 'all')
        ? $inventoryStatusValue[$inventoryStatus]
        : 'all';
    }

    public function getFilters()
    {
        return self::AVAILABLE_FILTERS;
    }
}
