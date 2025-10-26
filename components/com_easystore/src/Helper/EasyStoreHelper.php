<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Helper;

use DateTime;
use DOMXPath;
use Exception;
use Throwable;
use DOMDocument;
use Brick\Money\Money;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Brick\Math\RoundingMode;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Extension\ExtensionHelper;
use Joomla\Filesystem\Path;
use JoomShaper\Component\EasyStore\Site\Model\OrderModel;
use JoomShaper\Component\EasyStore\Site\Model\ProductModel;
use JoomShaper\Component\EasyStore\Site\Traits\ProductOption;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper as EasyStoreAdminHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * EasyStore component helper.
 *
 * @since  1.0.0
 */
class EasyStoreHelper
{
    use ProductOption;

    /**
     * Register and load component assets outside of the component scope
     *
     * @return WebAssetManager
     * @since 1.0.0
     */
    public static function wa()
    {
        /** @var CMSApplication */
        $app      = Factory::getApplication();
        $document = $app->getDocument();
        $wa       = $document->getWebAssetManager();
        $registry = $wa->getRegistry();
        $registry->addExtensionRegistryFile('com_easystore');

        $document->addScriptOptions('easystore.base', rtrim(Uri::root(), '/'));

        return $wa;
    }

    public static function attachRequiredAssets()
    {
        static::wa()
            ->useStyle('com_easystore.site')
            ->useStyle('com_easystore.product.site')
            ->useStyle('com_easystore.products.site')
            ->useStyle('com_easystore.cart.drawer.site')
            ->useScript('com_easystore.products.site')
            ->useScript('com_easystore.alpine.site');
    }

    /**
     * Load layout file
     *
     * @param  string $layoutFile Layout file
     * @param  array  $displayData Display data
     * @return string
     *
     * @since 1.0.0
     * @since 1.5.0 improve the implementation for overwrite support
     */
    public static function loadLayout($layoutFile, $displayData = [])
    {
        $templateName = Factory::getApplication()->getTemplate();
        $templatePath = JPATH_ROOT . "/templates/{$templateName}/html/layouts/com_easystore/";
        $basePath     = JPATH_ROOT . '/components/com_easystore/layouts/';
        $layoutPath   = str_replace('.', '/', $layoutFile) . '.php';

        // Check if the layout exists in the template override path
        if (file_exists($templatePath . $layoutPath)) {
            return LayoutHelper::render($layoutFile, $displayData, $templatePath);
        }

        if (is_dir($templatePath)) {
            // Search recursively in subfolders
            foreach (Folder::folders($templatePath, '.', true, true) as $folder) {
                if (file_exists($folder . '/' . $layoutPath)) {
                    return LayoutHelper::render($layoutFile, $displayData, $folder);
                }
            }
        }

        return LayoutHelper::render($layoutFile, $displayData, $basePath);
    }

    /**
     * Determine whether the checkbox is in a checked state or not.
     *
     * @param   int              $id            The ID of the item to check.
     * @param   array            $checked       Array of all checked ids.
     * @return string|bool
     * @since 1.0.0
     */
    public static function isChecked($id, $checked)
    {
        if (in_array($id, $checked)) {
            return 'checked';
        }

        return false;
    }

    /**
     * Verify whether the user is eligible to review this product.
     *
     * @param  int $userId   User ID
     * @param  int $id       Product ID
     * @since  1.0.0
     * @return bool
     */
    public static function canReview($userId, $id)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $orm = new EasyStoreDatabaseOrm();

        $customerId = $orm->get('#__easystore_users', 'user_id', $userId, 'id')->loadResult();

        if (!empty($customerId)) {
            $query->select('COUNT(opm.product_id)')
                ->from($db->quoteName('#__easystore_orders', 'o'))
                ->where($db->quoteName('o.customer_id') . " = " . $db->quote($customerId));

            $query->join('LEFT', $db->quoteName('#__easystore_order_product_map', 'opm'), $db->quoteName('opm.order_id') . ' = ' . $db->quoteName('o.id'))
                ->where($db->quoteName('opm.product_id') . " = " . $db->quote($id));

            $db->setQuery($query);

            try {
                return (bool) $db->loadResult();
            } catch (\Throwable $e) {
                throw new \Exception($e->getMessage());
            }
        }

        return false;
    }

    /**
     * Get all the reviews of a product
     *
     * @param  int    $id    Product ID
     * @return mixed         Message object
     * @since  1.0.0
     */
    public static function getReviews($id)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select($db->quoteName(['a.rating', 'a.subject', 'a.review', 'a.created']))
            ->from($db->quoteName('#__easystore_reviews', 'a'))
            ->where($db->quoteName('a.product_id') . ' = ' . $id)
            ->where($db->quoteName('a.published') . ' = 1');

        // Join over the users.
        $query->select($db->quoteName('uc.name', 'user_name'))
            ->join('LEFT', $db->quoteName('#__users', 'uc'), $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.created_by'));

        $db->setQuery($query);

        try {
            return $db->loadObjectList();
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Calculate the average rating of a product
     *
     * @param  int   $id                Product ID
     * @param  int   $totalNumOfRating  Total  number of rating of a product
     * @since  1.0.0
     * @return string
     */
    public static function getAverageRating($id, $totalNumOfRating)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select($db->quoteName('rating'))
            ->from($db->quoteName('#__easystore_reviews'))
            ->where($db->quoteName('product_id') . ' = ' . $id)
            ->where($db->quoteName('published') . ' = 1');

        $db->setQuery($query);

        try {
            $result = $db->loadObjectList();
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }

        $averageRating = $totalNumOfRating ? (float) array_sum(array_column($result, 'rating')) / $totalNumOfRating : 0;

        return number_format($averageRating, 1);
    }

    /**
     * Verify if the user has previously provided a review for this product.
     *
     * @param  int $userId   User ID
     * @param  int $id       Product ID
     * @return mixed
     * @since  1.0.0
     */
    public static function hasGivenReview($userId, $id)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        try {
            $query->select('COUNT(*)')
                ->from($db->quoteName('#__easystore_reviews'))
                ->where($db->quoteName('product_id') . ' = ' . $id)
                ->where($db->quoteName('created_by') . ' = ' . $userId)
                ->whereIn($db->quoteName('published'), [0, 1]);

            $db->setQuery($query);
            return $db->loadResult();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Calculate total price of an order
     *
     * @param  object  $item
     * @return float
     * @since  1.0.0
     */
    public static function calculateTotalPrice($item)
    {
        $discountedPrice = ($item->discount_value) ? EasyStoreAdminHelper::calculateDiscountedPrice($item->discount_type, $item->discount_value, $item->sub_total) : $item->sub_total;

        $totalPrice = ($discountedPrice + $item->sale_tax + $item->shipping_value - $item->coupon_amount);

        return floatval($totalPrice);
    }

    /**
     * Get all sub category Ids of a category
     *
     * @param  object  $item
     * @return float
     * @since  1.0.0
     */

    public static function getDescendantCategoryIds($catid)
    {
        if ($catid <= 0) {
            $catid = 1;
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select($db->quoteName('id'))
            ->from($db->quoteName('#__easystore_categories'))
            ->where($db->quoteName('parent_id') . ' = ' . $db->quote($catid));

        $db->setQuery($query);

        $results = $db->loadColumn();

        $allChildren = [$catid]; // Include the original $catid

        foreach ($results as $childCatId) {
            $allChildren[] = $childCatId;

            // Recursive call to get children of this child
            $childChildren = static::getDescendantCategoryIds($childCatId);
            $allChildren   = array_merge($allChildren, $childChildren);
        }

        $allChildren = array_unique($allChildren);

        return $allChildren;
    }

    /**
     * Get category of a product
     *
     * @param  int     $id     Product ID
     * @return mixed           Array on success, false on failure.
     * @since  1.0.0
     */
    public static function getCategories($id)
    {
        // Create a new query object.
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select([$db->quoteName('c.id', 'category_id'), $db->quoteName('c.title', 'category_name'), $db->quoteName('c.alias', 'category_alias')])
            ->from($db->quoteName('#__easystore_categories', 'c'))
            ->where($db->quoteName('c.id') . ' = ' . $db->quote($id));

        $db->setQuery($query);

        try {
            return $db->loadAssocList();
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Get tree of a category
     *
     * @param  int     $id     Category ID
     * @return mixed           Array on success, false on failure.
     * @since  1.0.0
     */
    public static function getCategoryPath($id)
    {
        static $cache = [];

        if (isset($cache[$id])) {
            return $cache[$id];
        }
        
        $aliasPath    = [];
        $detailedPath = [];

        while ($id > 0) {
            $result = static::getCategoryById($id);

            if ($result) {
                // Exclude 'root' alias
                if ($result->alias !== 'root') {
                    $aliasPath[$result->id]    = $result->alias;
                    $detailedPath[$result->id] = [
                        'id'        => $result->id,
                        'title'     => $result->title,
                        'alias'     => $result->alias,
                        'parent_id' => $result->parent_id,
                    ];
                }
                $id = $result->parent_id;
            } else {
                // Exit loop if category not found
                break;
            }
        }

        $cache[$id] = [
            'aliasPath'    => $aliasPath,
            'detailedPath' => $detailedPath,
        ];

        return $cache[$id];
    }

    /**
     * Get category by ID
     *
     * @param  int     $id     Category ID
     * @return mixed           Object on success, false on failure.
     * @since  1.4.7
     */
    public static function getCategoryById($id)
    {
        static $cache = [];

        if (isset($cache[$id])) {
            return $cache[$id];
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select(['id', 'title', 'alias', 'parent_id'])
            ->from($db->quoteName('#__easystore_categories'))
            ->where($db->quoteName('id') . ' = ' . $db->quote($id));

        $db->setQuery($query);

        try {
            $result = $db->loadObject();
            $cache[$id] = $result;
            return $result;
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Get tags of a product
     *
     * @param  int        $id     Product ID
     * @return mixed              Object on success, false on failure.
     * @since  1.0.0
     */
    public static function getTags($id)
    {
        // Create a new query object.
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select([$db->quoteName('t.id', 'tag_id'), $db->quoteName('t.alias', 'tag_alias'), $db->quoteName('t.title', 'tag_name')])
            ->from($db->quoteName('#__easystore_tags', 't'));

        $query->join('LEFT', $db->quoteName('#__easystore_product_tag_map', 'product_tag_map') . ' ON product_tag_map.tag_id = t.id')
            ->where($db->quoteName('product_tag_map.product_id') . ' = ' . $db->quote($id));

        $db->setQuery($query);

        try {
            return $db->loadAssocList();
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Get icon by name
     *
     * @param  string    $iconName
     * @return string
     * @since  1.0.0
     */
    public static function getIcon(string $iconName)
    {
        $path = JPATH_ROOT . '/components/com_easystore/assets/icons/' . $iconName . '.svg';
        if (file_exists($path)) {
            $svg = file_get_contents($path);
            return '<span class="easystore-svg">' . $svg . '</span>';
        }
    }

    /**
     * Get the product image
     *
     * @param  string    $title
     * @return string
     * @since  1.0.0
     */
    public static function setSiteTitle(string $title)
    {
        $app      = Factory::getApplication();
        $siteName = Factory::getConfig()->get('sitename');

        // add site name before or after
        if ($app->get('sitename_pagetitles', 0) == 1) {
            $metaTitle = $siteName . ' - ' . $title;
        } elseif ($app->get('sitename_pagetitles', 0) == 2) {
            $metaTitle = $title . ' - ' . $siteName;
        } else {
            $metaTitle = $title;
        }

        Factory::getDocument()->setTitle($metaTitle);
    }

    /**
     * Display a message
     *
     * @param  string $messageType   Message Type. Ex: error,info,warning
     * @param  string $message       Message to display.
     * @return string
     * @since  1.0.0
     */
    public static function showMessage($messageType, $message)
    {
        $alertClass = ($messageType === 'error') ? "alert-danger" : "alert-$messageType";

        $output = '<div class="alert ' . $alertClass . '">';
        $output .= '<p>' . $message . '</p>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Generate default variants
     *
     * @param int $productId
     * @return string
     */
    public static function generateDefaultVariants($productId)
    {
        $variants = self::getOptions($productId);
        $result   = '';
        if (!empty($variants)) {
            $count = count((array) $variants);
            $i     = 1;

            foreach ($variants as $variant) {
                $result .= $variant->id . ':';
                foreach ($variant->values as $value) {
                    if ($i == $count) {
                        $result .= $value->id;
                    } else {
                        $result .= $value->id . ',';
                    }
                    break;
                }
                $i++;
            }
        }

        return $result;
    }

    /**
     * Initiate EasyStore for SP Page Builder
     * @param string $view
     * @return mixed
     */

    public static function initEasyStore(string $view)
    {
        $app          = Factory::getApplication();
        $input        = $app->input;
        $option       = $input->get('option', '', 'STRING');
        $optionView   = $input->get('view', '', 'STRING');
        $optionLayout = $input->get('layout', 'default', 'STRING');

        $context = $option . '.' . $optionView . '.' . $optionLayout;

        $wa = static::wa();

        if ($view === 'single') {
            $product = null;

            $wa->useStyle('com_easystore.site')
                ->useStyle('com_easystore.product.site');

            if ($context === 'com_sppagebuilder.page.default') {
                $wa->useScript('com_easystore.product.site')
                    ->useScript('com_easystore.wishlist.site')
                    ->useScript('com_easystore.alpine.site');

                $productModel = new ProductModel();

                $db    = Factory::getContainer()->get(DatabaseInterface::class);
                $query = $db->getQuery(true);
                $query->select('id');
                $query->from('#__easystore_products')
                    ->where($db->quoteName('published') . ' = 1');
                $db->setQuery($query);
                $product_id = $db->loadResult();

                if (empty($product_id)) {
                    throw new Exception('Create a product before previewing. No published products available', 404);
                }

                $product = $productModel->getItem((int) $product_id);
            }

            if ($product) {
                return [1, true, ['easystoreItem' => $product, 'easystoreList' => []]];
            }
        }

        if (in_array($view, ['storefront', 'collection'], true)) {
            $wa->useStyle('com_easystore.site')
                ->useStyle('com_easystore.products.site');

            if ($context !== 'com_sppagebuilder.form.edit') {
                $wa->useScript('com_easystore.products.site');
            }
        }

        return [];
    }

    /**
     * Get the page builder content
     *
     * @return object
     * @since  1.0.0
     */
    public static function getPageBuilderData(string $view = 'single')
    {
        if (ComponentHelper::isEnabled('com_sppagebuilder')) {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select('*')
                ->from('#__sppagebuilder')
                ->where([
                    $db->quoteName('extension') . ' = ' . $db->quote('com_easystore'),
                    $db->quoteName('extension_view') . ' = ' . $db->quote($db->escape($view)),
                    $db->quoteName('published') . ' = 1',
                ]);

            $db->setQuery($query);

            return $db->loadObject() ?? false;
        }

        return false;
    }

    /**
     * Get the customer information by Joomla user ID.
     *
     * @param int $userId
     * @return object|null
     */
    public static function getCustomerByUserId($userId)
    {
        if (empty($userId)) {
            return null;
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select(['u.name', 'u.email', 'eu.*'])
            ->from($db->quoteName('#__users', 'u'))
            ->join('LEFT', $db->quoteName('#__easystore_users', 'eu') . ' ON (' . $db->quoteName('eu.user_id') . ' = ' . $db->quoteName('u.id') . ')')
            ->where($db->quoteName('u.id') . ' = :userId')
            ->bind(':userId', $userId);

        $db->setQuery($query);

        try {
            $profile = $db->loadObject() ?? null;
            if ($profile) {
                $profile->avatar = $profile->image ? '<img src="' . $profile->image . '" alt="" class="easystore-profile-image-circle">': static::getIcon('user');
            }
            return $profile;
        } catch (Throwable $error) {
            return null;
        }
    }

    /**
     * Get the customer by the customer ID
     *
     * @param int $customerId
     * @return object|null
     */
    public static function getCustomerById($customerId)
    {
        if (empty($customerId)) {
            return null;
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select(['eu.*', 'u.name', 'u.email'])->from($db->quoteName('#__easystore_users', 'eu'))
            ->where($db->quoteName('eu.id') . ' = ' . $customerId)
            ->leftJoin($db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('u.id') . ' = ' . $db->quoteName('eu.user_id') . ')');

        try {
            $db->setQuery($query);

            return $db->loadObject() ?? null;
        } catch (Throwable $error) {
            return null;
        }
    }

    /**
     * Generate responsive columns based on the number of columns
     *
     * @return object
     * @since  1.0.0
     */
    public static function generateResponsiveColumn(int $cols)
    {
        if ($cols < 1 || $cols > 8) {
            $cols = 8;
        }

        // Initialize variables
        $maxPercentage = 100.0;
        $percentages   = [];

        function formatPercentage($percentage)
        {
            return $percentage == round($percentage) ? (int) $percentage : number_format($percentage, 2);
        }

        // Calculate percentages for different screen sizes
        $percentages["xl"] = formatPercentage($maxPercentage / $cols, 2);
        $percentages["lg"] = formatPercentage($maxPercentage / ceil($cols / 2), 2);
        $percentages["md"] = formatPercentage($maxPercentage / ceil($cols / 4), 2);
        $percentages["sm"] = formatPercentage($maxPercentage / 2, 2);
        $percentages["xs"] = formatPercentage($maxPercentage, 2);

        return $percentages;
    }

    /**
     * Extract the name string and make first name and last name.
     *
     * @param string $name
     * @return array
     */
    public static function extractName(string $name)
    {
        $name  = preg_replace("@\s+@", ' ', $name);
        $names = explode(' ', $name);

        switch (count($names)) {
            case 0:
                return ['', ''];
            case 1:
                return [$names[0], ''];
            default:
                $lastName = array_pop($names);
                return [implode(' ', $names), $lastName];
        }

        return ['', ''];
    }

    /**
     * Function to build nested Categories
     *
     * @param array $categories
     * @param int $parentId
     * @return array
     */
    public static function buildNestedCategories($categories, $parentId = 1)
    {
        $nestedCategories = [];

        foreach ($categories as $category) {
            if ($category->cat_parent_id == $parentId) {
                $category->child    = self::buildNestedCategories($categories, $category->cat_id);
                $nestedCategories[] = $category;
            }
        }

        unset($category);

        return $nestedCategories;
    }

    /**
     * Function to get Country Name & State Name from JSON
     *
     * @param string $countryId
     * @param int $stateId
     * @return object
     */
    public static function getCountryStateFromJson($countryId, $stateId)
    {
        $path        = JPATH_ROOT . '/media/com_easystore/data/countries.json';
        $jsonData    = file_get_contents($path);
        $countries   = !empty($jsonData) && is_string($jsonData) ? json_decode($jsonData) : [];
        $countryName = '';
        $stateName   = '';

        foreach ($countries as $country) {
            if ($country->numeric_code == $countryId) {
                $countryName = $country->name;

                foreach ($country->states as $state) {
                    if ($state->id == $stateId) {
                        $stateName = $state->name;
                        break;
                    }
                }

                break;
            }
        }

        return (object) ['country' => $countryName, 'state' => $stateName];
    }

    /**
     * Function to get Country ISO alpha2/alpha3 name bu id
     *
     * @param string $countryId
     * @return object
     */
    public static function getCountryIsoNames($countryId)
    {
        $path        = JPATH_ROOT . '/media/com_easystore/data/countries.json';
        $jsonData    = file_get_contents($path);
        $countries   = !empty($jsonData) && is_string($jsonData) ? json_decode($jsonData) : [];
        $countryName = new \stdClass();

        foreach ($countries as $country) {
            if ($country->numeric_code == $countryId) {
                $countryName->iso2 = $country->alpha_2;
                $countryName->iso3 = $country->alpha_3;
                break;
            }
        }

        return $countryName;
    }


    /**
     * Function to get Country code by country name
     *
     * @param string $countryName
     * @return object
     */
    public static function getCountryCode($countryName)
    {
        $path        = JPATH_ROOT . '/media/com_easystore/data/countries.json';
        $jsonData    = file_get_contents($path);
        $countries   = !empty($jsonData) && is_string($jsonData) ? json_decode($jsonData) : [];
        $countryCode = new \stdClass();

        foreach ($countries as $country) {
            if ($country->name === $countryName) {
                $countryCode->code = $country->numeric_code;
                break;
            }
        }

        return $countryCode;
    }

    /**
     * Function to generate Options from JSON
     *
     * @param string $fieldType         Can be 'country', 'state' or 'phoneCode'
     * @param string $stateCountryId    Only need when fieldType is 'state'
     * @return array
     */
    public static function getOptionsFromJson($fieldType = 'country', $stateCountryId = null)
    {
        $path      = JPATH_ROOT . '/media/com_easystore/data/countries.json';
        $jsonData  = file_get_contents($path);
        $data      = !empty($jsonData) && is_string($jsonData) ? json_decode($jsonData) : [];
        $options[] = (object) ['name' => Text::_('JSELECT'), 'value' => ''];

        foreach ($data as $country) {
            if ($country->numeric_code === '000') {
                continue;
            }
            if ($fieldType === 'country') {
                $options[] = (object) [
                    'name'  => $country->name,
                    'value' => $country->numeric_code,
                ];
            } elseif ($fieldType === 'state') {
                if ($stateCountryId == $country->numeric_code) {
                    foreach ($country->states as $state) {
                        $options[] = (object) [
                            'name'  => $state->name,
                            'value' => $state->id,
                        ];
                    }
                    break;
                }
            } elseif ($fieldType === 'phoneCode') {
                $phoneCode = str_replace(['+', '-'], '', $country->phone_code ?? '');
                $options[] = (object) [
                    'name'  => $country->emoji . ' ' . $country->name . ' (' . $phoneCode . ')',
                    'value' => $phoneCode,
                ];
            }
        }

        return $options;
    }

    /**
     * Function to check coupon code validity
     *
     * @param string $startDate
     * @param string $endDate
     * @return bool
     */
    public static function isCouponCodeValid($startDate, $endDate = null)
    {
        $tz        = new \DateTimeZone(Factory::getApplication()->get('offset'));
        $today     = new DateTime('now', $tz);
        $startDate = new DateTime($startDate, $tz);

        if ($startDate > $today) {
            return false;
        }

        if ($endDate !== null) {
            $endDate = new DateTime($endDate, $tz);

            return $today >= $startDate && $today <= $endDate;
        }

        return $today >= $startDate;
    }

    /**
     * Get Min and Max price
     *
     * @return array
     */
    public static function getPrice()
    {
        // Get the database connection
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        // Create a new query builder object
        $query = $db->getQuery(true);

        // Define the SELECT clause with conditional expressions
        $query->select([
            'IF(p.has_variants = 0, MIN(p.regular_price), MIN(ps.price)) AS min_price',
            'IF(p.has_variants = 0, MAX(p.regular_price), MAX(ps.price)) AS max_price',
        ]);

        // Define the FROM clause and LEFT JOIN
        $query->from($db->quoteName('#__easystore_products', 'p'))
            ->leftJoin($db->quoteName('#__easystore_product_skus', 'ps') . ' ON ' . $db->quoteName('ps.product_id') . ' = ' . $db->quoteName('p.id'));

        // Define the WHERE clause
        $query->where($db->quoteName('p.published') . ' = 1');

        // Define the GROUP BY clause
        $query->group($db->quoteName('p.has_variants'));

        // Execute the query
        $db->setQuery($query);

        // Get the results as an associative array
        $results = $db->loadAssocList();

        // Initialize variables for min and max prices
        $minPrice = null;
        $maxPrice = null;

        // Loop through the results to find the overall min and max prices
        foreach ($results as $result) {
            if ($minPrice === null || $result['min_price'] < $minPrice) {
                $minPrice = (float) $result['min_price'];
            }
            if ($maxPrice === null || $result['max_price'] > $maxPrice) {
                $maxPrice = (float) $result['max_price'];
            }
        }

        return [$minPrice, $maxPrice];
    }

    /**
     * Get order data in a format which is suitable for payment processing.
     *
     * @param  object $data            - Data information for payment.
     *
     * @return object                  - The formatted order data object.
     * @since  1.0.7
     */
    public static function getOrderDataForPayment($data)
    {
        $backToOrderPage = Route::_(Uri::base() . 'index.php?option=com_easystore&view=order&id=' . $data->order_id, false);

        $shippingAddress = !empty($data->shipping_address) && is_string($data->shipping_address) ? json_decode($data->shipping_address) : $data->shipping_address;
        $billingAddress  = !empty($data->billing_address) && is_string($data->billing_address) ? json_decode($data->billing_address) : $data->billing_address;

        // Set country iso names and state name from shipping address
        $shippingAddress->country_iso_names = static::getCountryIsoNames($shippingAddress->country);
        $shippingAddress->state_name        = static::getCountryStateFromJson($shippingAddress->country, $shippingAddress->state);

        // Set country iso names and state name from billing address
        $billingAddress->country_iso_names = static::getCountryIsoNames($billingAddress->country);
        $billingAddress->state_name        = static::getCountryStateFromJson($billingAddress->country, $billingAddress->state);

        $orderData = (new OrderModel())->getOrderItemForPayment($data->order_id);

        $order                = new \stdClass();
        $settings             = SettingsHelper::getSettings();
        $currencyInfo         = $settings->get('general.currency', EasyStoreAdminHelper::getDefaultCurrency());
        $countryId            = $shippingAddress->country_code ?? $shippingAddress->country;
        $isTaxIncludedInPrice = boolval($orderData->is_tax_included_in_price ?? 0);

        $chunks              = explode(':', $currencyInfo);
        $currency            = $chunks[0] ?? EasyStoreAdminHelper::getDefaultCurrency('code');
        $currencyNumericCode = self::getCurrencyNumericCode($currency);

        foreach ($orderData->items as $itemData) {
            $item = new \stdClass();

            $item->id                     = $itemData->product_id;
            $item->title                  = $itemData->title;
            $item->quantity               = $itemData->quantity;
            $item->price                  = $itemData->item_price;
            $item->price_in_smallest_unit = self::getMinorAmountBasedOnCurrency($itemData->item_price, $currency);
            $item->image                  = !empty($itemData->image->src) ? Uri::base() . '/' . Path::clean($itemData->image->src) : null;

            $order->items[] = $item;
        }

        $order->total_price                             = $orderData->total;
        $order->total_price_in_smallest_unit            = self::getMinorAmountBasedOnCurrency($orderData->total, $currency);
        $order->order_id                                = $data->order_id;
        $order->store_name                              = $settings->get('general.storeName', 'EasyStore');
        $order->tax                                     = $isTaxIncludedInPrice ? null : ($orderData->taxable_amount ?: null);
        $order->tax_in_smallest_unit                    = self::getMinorAmountBasedOnCurrency($order->tax, $currency);
        $order->currency                                = $currency;
        $order->country                                 = static::getCountryIsoNames($countryId);
        $order->shipping_charge                         = $orderData->shipping_cost ?? null;
        $order->shipping_charge_in_smallest_unit        = self::getMinorAmountBasedOnCurrency($order->shipping_charge, $currency);
        $order->coupon_discount_amount                  = $orderData->coupon_discount ?? null;
        $order->coupon_name                             = $orderData->coupon_code ?? null;
        $order->coupon_category                         = $orderData->coupon_category ?? null;
        $order->coupon_discount_amount_in_smallest_unit = self::getMinorAmountBasedOnCurrency($order->coupon_discount_amount, $currency);
        $order->back_to_checkout_page                   = $backToOrderPage;
        $order->decimal_separator                       = $settings->get('general.decimalSeparator', '.');
        $order->thousands_separator                     = $settings->get('general.thousandSeparator', ',');
        $order->currency_numeric_code                   = $currencyNumericCode;
        $order->subtotal                                = $orderData->sub_total;
        $order->shipping_address                        = $shippingAddress;
        $order->billing_address                         = $billingAddress;
        $order->shipping_method                         = !empty($orderData->shipping->name) ? $orderData->shipping->name : null;

        if (is_null($orderData->customer_id)) {
            $backToOrderPage = Uri::getInstance($backToOrderPage);
            $backToOrderPage->setVar('guest_token', $orderData->order_token);
            $order->back_to_checkout_page = $backToOrderPage->__toString();

            $order->user_name   = $shippingAddress->name ?? '';
            $order->user_number = $shippingAddress->phone ?? '';
            $order->user_email  = $data->customer_email ?? '';
        } else {
            $customer           = EasyStoreHelper::getCustomerById($orderData->customer_id) ?? new \stdClass();
            $customer           = self::getCustomerByUserId($customer->user_id);
            $order->user_name   = $customer->name ?? '';
            $order->user_number = $customer->phone ?? '';
            $order->user_email  = $data->customer_email ?? '';
        }

        return $order;
    }

    /**
     * Get stock counts of filter
     *
     * @return array
     */
    public static function getStockCounts()
    {
        // Get a database connection.
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        // Create a new query object.
        $query = $db->getQuery(true);

        // CASE conditions for in-stock and out-of-stock items
        $inStockConditions = [
            'a.has_variants = 0 AND a.is_tracking_inventory = 0 AND a.inventory_status = 1',
            'a.has_variants = 0 AND a.is_tracking_inventory = 1 AND a.quantity > 0',
            'a.has_variants = 1 AND a.is_tracking_inventory = 0 AND ps.inventory_status = 1',
            'a.has_variants = 1 AND a.is_tracking_inventory = 1 AND ps.inventory_amount > 0',
        ];

        $outStockConditions = [
            'a.has_variants = 0 AND a.is_tracking_inventory = 0 AND a.inventory_status = 0',
            'a.has_variants = 0 AND a.is_tracking_inventory = 1 AND a.quantity <= 0',
            'NOT EXISTS (SELECT 1 FROM #__easystore_product_skus AS eps WHERE eps.product_id = a.id AND eps.inventory_status = 1 AND a.is_tracking_inventory = 0) AND ((a.has_variants = 1 AND a.is_tracking_inventory = 0 AND ps.inventory_status = 0) OR (a.has_variants = 1 AND a.is_tracking_inventory = 1 AND ps.inventory_amount <= 0))',
        ];

        $query->select([
            "COUNT(DISTINCT CASE WHEN (" . implode(') OR (', $inStockConditions) . ") THEN a.id ELSE NULL END) AS total_in_stock_count",
            "COUNT(DISTINCT CASE WHEN (" . implode(') OR (', $outStockConditions) . ") THEN a.id ELSE NULL END) AS total_out_of_stock_count",
        ])
            ->from($db->quoteName('#__easystore_products', 'a'))
            ->join('LEFT', $db->quoteName('#__easystore_product_skus', 'ps') . ' ON ' . $db->quoteName('ps.product_id') . ' = ' . $db->quoteName('a.id'))
            ->where($db->quoteName('a.published') . ' = 1');

        // Set the query for execution
        $db->setQuery($query);

        // Execute the query and fetch the result
        $results = $db->loadRow();

        // Query only to remove count products with variants with has some variants with 0 quantity
        $query = $db->getQuery(true);
        $query->select("*")
            ->from($db->quoteName('#__easystore_products', 'a'))
            ->where($db->quoteName('a.published') . ' = 1')
            ->where($db->quoteName('a.has_variants') . ' = 1')
            ->where($db->quoteName('a.is_tracking_inventory') . ' = 1');

        $db->setQuery($query);

        $variantProducts = $db->loadObjectList();

        $toRemoveOutOfStockCounter = 0;

        foreach ($variantProducts as $product) {
            $orm      = new EasyStoreDatabaseOrm();
            $variants = $orm->hasMany($product->id, '#__easystore_product_skus', 'product_id')->loadObjectList();

            $count   = count($variants);
            $counter = 0;
            foreach ($variants as $variant) {
                if ($variant->inventory_amount <= 0) {
                    $counter++;
                }
            }

            if ($count != $counter) {
                $toRemoveOutOfStockCounter++;
            }
        }

        $results[1] = $results[1] - $toRemoveOutOfStockCounter;
        $results[1] = $results[1] < 0 ? 0 : $results[1];

        // Return the results
        return $results;
    }

    /**
     * Function for payment status badge color
     *
     * @param string $status
     * @return string
     */
    public static function getPaymentBadgeColor($status)
    {
        if ($status === 'paid') {
            $badgeStatus = "success";
        } elseif ($status === 'unpaid') {
            $badgeStatus = "warning";
        } elseif ($status === 'pending') {
            $badgeStatus = "dark";
        } elseif ($status === 'refunded') {
            $badgeStatus = "info";
        } elseif ($status === 'failed') {
            $badgeStatus = "danger";
        } else {
            $badgeStatus = "secondary";
        }

        return $badgeStatus;
    }

    /**
     * Function to return payment status language string
     *
     * @param string $status
     * @return string
     */
    public static function getPaymentStatusString($status)
    {
        if ($status === 'paid') {
            $result = Text::_('COM_EASYSTORE_PAYMENT_STATUS_PAID');
        } elseif ($status === 'unpaid') {
            $result = Text::_('COM_EASYSTORE_PAYMENT_STATUS_UNPAID');
        } elseif ($status === 'pending') {
            $result = Text::_('COM_EASYSTORE_PAYMENT_STATUS_PENDING');
        } elseif ($status === 'canceled') {
            $result = Text::_('COM_EASYSTORE_PAYMENT_STATUS_CANCELED');
        } elseif ($status === 'failed') {
            $result = Text::_('COM_EASYSTORE_PAYMENT_STATUS_FAILED');
        } elseif ($status === 'refunded') {
            $result = Text::_('COM_EASYSTORE_PAYMENT_STATUS_REFUNDED');
        } else {
            $result = ucfirst(str_replace('_', ' ', $status));
        }

        return $result;
    }

    /**
     * Function to return payment method name
     * @param mixed $method
     * @return string
     */
    public static function getPaymentMethodString($method)
    {
        $result = '';

        if ($method === 'manual_payment') {
            $result = Text::_('COM_EASYSTORE_PAYMENT_METHOD_COD');
        } else {
            $plugin = ExtensionHelper::getExtensionRecord($method, 'plugin', 0, 'easystore');

            if (!empty($plugin)) {
                $param  = json_decode($plugin->params);
                $result = $param->title ?? '';
            }
        }

        return $result;
    }

    /**
     * Get the numeric code of a currency based on its currency code.
     *
     * @param  string   $currency -- The currency code.
     * @return int|null           -- The numeric code of the currency or null if not found.
     * @since  1.0.0
     */

    public static function getCurrencyNumericCode($currency)
    {
        $path     = JPATH_ROOT . '/media/com_easystore/data/currencies.json';
        $jsonData = file_get_contents($path);

        $currencies = !empty($jsonData) && is_string($jsonData) ? json_decode($jsonData) : [];

        if (!empty($currencies)) {
            $index = array_search($currency, array_column($currencies, 'code'));

            if ($index !== false) {
                return $currencies[$index]->numeric_code;
            }
        }

        // Return a default value or handle the case when the currency is not found
        return null;
    }

    /**
     * Function to get order summary data by order id
     * @param int $orderId
     * @param bool $fromCheckout   Set value to true if it is called from checkout page
     * @return array
     *
     * @since  1.0.9
     */
    public static function getOrderSummary(int $orderId, bool $fromCheckout = false)
    {
        $loggedUser = Factory::getApplication()->getIdentity();
        $user       = EasyStoreHelper::getCustomerByUserId($loggedUser->id);
        $pk         = $orderId;

        $isGuestOrder = 0;
        $guest        = new \stdClass();

        // Create a new query object.
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select(
            [
                $db->quoteName('o.id'),
                $db->quoteName('o.discount_type'),
                $db->quoteName('o.discount_value'),
                $db->quoteName('o.shipping'),
                $db->quoteName('o.tracking_number'),
                $db->quoteName('o.transaction_id'),
                $db->quoteName('o.shipping_carrier'),
                $db->quoteName('o.tracking_url'),
                $db->quoteName('o.coupon_category'),
                $db->quoteName('o.coupon_code'),
                $db->quoteName('o.coupon_type'),
                $db->quoteName('o.coupon_amount'),
                $db->quoteName('o.sale_tax'),
                $db->quoteName('o.shipping_type'),
                $db->quoteName('o.shipping_type'),
                $db->quoteName('o.shipping_value'),
                $db->quoteName('o.payment_method'),
                $db->quoteName('o.is_guest_order'),
                $db->quoteName('o.customer_id'),
                $db->quoteName('o.customer_email'),
                $db->quoteName('o.shipping_address'),
                $db->quoteName('o.billing_address'),
                $db->quoteName('o.company_name'),
                $db->quoteName('o.vat_information'),
            ]
        )
            ->from($db->quoteName('#__easystore_orders', 'o'))
            ->where($db->quoteName('o.id') . ' = ' . $pk)
            ->where($db->quoteName('o.published') . ' = 1');

        $db->setQuery($query);

        try {
            $item = $db->loadObject();

            $isGuestOrder = $item->is_guest_order;

            if (empty($orderId) || is_null($orderId)) {
                throw new Exception(Text::_("COM_EASYSTORE_ORDER_NOT_FOUND"), 404);
            }

            if (!empty($isGuestOrder)) {
                $guest->name                                 = '';
                $guest->email                                = $item->customer_email;
                $guest->is_billing_and_shipping_address_same = false;
            }

            $orderSummary = EasyStoreAdminHelper::getOrderCalculatedAmounts($item->id, $fromCheckout);

            $item->sub_total                           = $orderSummary->sub_total;
            $item->extra_discount_amount               = $orderSummary->order_discount;
            $item->shipping_cost                       = $orderSummary->shipping_cost;
            $item->sale_tax                            = (float) $item->sale_tax;
            $item->sub_total_tax                       = $item->sub_total + $item->sale_tax;
            $item->coupon_discount                     = $orderSummary->coupon_amount;
            $item->total_price                         = $orderSummary->net_amount;
            $item->sub_total_with_currency             = EasyStoreAdminHelper::formatCurrency($item->sub_total);
            $item->sub_total_tax_with_currency         = EasyStoreAdminHelper::formatCurrency($item->sub_total_tax);
            $item->extra_discount_amount_with_currency = !empty($item->extra_discount_amount) ? EasyStoreAdminHelper::formatCurrency($item->extra_discount_amount) : '';
            $item->total_price_with_currency           = EasyStoreAdminHelper::formatCurrency($item->total_price);
            $item->shipping_cost_with_currency         = EasyStoreAdminHelper::formatCurrency($item->shipping_cost);
            $item->coupon_discount_with_currency       = EasyStoreAdminHelper::formatCurrency($item->coupon_discount);
            $item->sale_tax_with_currency              = EasyStoreAdminHelper::formatCurrency($item->sale_tax);

            $orderModel     = new OrderModel();
            $item->products = $orderModel->getProducts($item->id);

            foreach ($item->products as &$product) {
                $product->price_with_currency          = EasyStoreAdminHelper::formatCurrency($product->price);
                $product->discount_price_with_currency = EasyStoreAdminHelper::formatCurrency(EasyStoreAdminHelper::calculateDiscountedPrice($product->discount_type, $product->discount_value, $product->price));
                $product->total_price                  = floatval($product->quantity * $product->price);
                $product->total_price_with_currency    = EasyStoreAdminHelper::formatCurrency($product->total_price);

                unset($product->media);
            }

            unset($product);

            if (empty($isGuestOrder) && !empty($user->shipping_address)) {
                $user->shipping_address = $orderModel->generateAddress($user->shipping_address);
            } else {
                $guest->shipping_address = $orderModel->generateAddress($item->shipping_address);
            }

            if (empty($isGuestOrder) && !empty($user->billing_address)) {
                $user->billing_address = $orderModel->generateAddress($user->billing_address);
            } else {
                $guest->billing_address = $orderModel->generateAddress($item->billing_address);
            }

            $item->customerData = empty($isGuestOrder) ? $user : $guest;

            return (array) $item;
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Retrieve a list of manual payment plugins available in the EasyStore.
     *
     * @return array List of manual payment plugin names.
     * @since  1.0.10
     */

    public static function getManualPaymentLists()
    {
        $plugins        = PluginHelper::getPlugin('easystore');
        $manualPayments = [];

        array_map(function ($plugin) use (&$manualPayments) {
            if (!empty($plugin->params) && is_string($plugin->params)) {
                $params = json_decode($plugin->params);

                if (isset($params->payment_type) && $params->payment_type === 'manual') {
                    array_push($manualPayments, $plugin->name);
                }
            }
        }, $plugins);

        return $manualPayments;
    }

    /**
     * Retrieves payment information for a specific manual payment plugin in the EasyStore.
     *
     * @param  string $plugin The name of the plugin.
     * @return object         An object containing additional information and payment instructions for the manual payment plugin.
     * @since  1.0.10
     */
    public static function getManualPaymentInfo($plugin)
    {
        $pluginInfo            = ExtensionHelper::getExtensionRecord($plugin, 'plugin', 0, 'easystore');
        $params                = json_decode($pluginInfo->params);
        $additionalInformation = isset($params->additional_information) ? $params->additional_information : null;
        $paymentInstruction    = isset($params->payment_instruction) ? $params->payment_instruction : null;

        return (object) [
            'additional_information' => $additionalInformation,
            'payment_instruction'    => $paymentInstruction,
        ];
    }

    /**
     * Function to get product options by id
     *
     * @param int $productId
     * @return object|array
     */
    public static function getProductOptionsById(int $productId)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true);
        $query->select([
            $db->quoteName('po.id', 'option_id'),
            $db->quoteName('po.product_id'),
            $db->quoteName('po.name', 'option_name'),
            $db->quoteName('po.type', 'option_type'),
            $db->quoteName('pov.id', 'value_id'),
            $db->quoteName('pov.name', 'option_value'),
            $db->quoteName('pov.color', 'option_color'),
            $db->quoteName('pov.ordering', 'value_ordering'),
            $db->quoteName('po.ordering', 'option_ordering'),
        ]);

        $query->from($db->quoteName('#__easystore_product_options', 'po'));
        $query->join(
            'INNER',
            $db->quoteName('#__easystore_product_option_values', 'pov') .
            ' ON (' . $db->quoteName('po.id') . ' = ' . $db->quoteName('pov.option_id') . ')'
        );
        $query->where($db->quoteName('po.product_id') . ' = ' . $db->quote($productId));
        $query->order($db->quoteName('po.ordering') . ' ASC, ' . $db->quoteName('pov.ordering') . ' ASC');
        $db->setQuery($query);
        $options = $db->loadAssocList();

        $groupedOptions = [];

        foreach ($options as $row) {
            $optionId       = $row['option_id'];
            $productId      = $row['product_id'];
            $optionName     = $row['option_name'];
            $optionType     = $row['option_type'];
            $optionOrdering = $row['option_ordering'];
            $valueId        = $row['value_id'];
            $optionValue    = $row['option_value'];
            $valueOrdering  = $row['value_ordering'];
            $optionColor    = $row['option_color'];

            if (!isset($groupedOptions[$optionId])) {
                $groupedOptions[$optionId] = [
                    'option_id'       => $optionId,
                    'product_id'      => $productId,
                    'option_name'     => $optionName,
                    'option_type'     => $optionType,
                    'option_ordering' => $optionOrdering,
                    'values'          => [],
                ];
            }

            $valueEntry = [
                'value_id'       => $valueId,
                'option_value'   => $optionValue,
                'value_ordering' => $valueOrdering,
            ];

            if ($optionType === 'color' && !empty($optionColor)) {
                $valueEntry['option_color'] = $optionColor;
            }

            $groupedOptions[$optionId]['values'][] = $valueEntry;
        }

        $groupedOptions = array_values($groupedOptions);

        return $groupedOptions;
    }

    /**
     * Function to map combination name with product options
     *
     * @param array $options
     * @param string $combination
     * @return array
     */
    public static function detectProductOptionFromCombination(array $options, $combination)
    {
        if (empty($options) || empty($combination)) {
            return [];
        }

        $combinationArray = explode(';', $combination);

        $result = [];

        if (!empty($combinationArray)) {
            foreach ($combinationArray as $com) {
                foreach ($options as $option) {
                    if (!empty($option['values'])) {
                        foreach ($option['values'] as $value) {
                            if (strtolower($com) === strtolower($value['option_value'])) {
                                $result[] = (object) [
                                    'key'   => $option['option_name'],
                                    'name'  => $value['option_value'],
                                    'color' => $value['option_color'] ?? '',
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Converts a given amount to its smallest unit based on the currency.
     * If the amount is null or empty, it returns null.
     *
     * @param  float    $amount   The amount to be converted.
     * @param  string   $currency The currency code (e.g., 'USD', 'EUR').
     * @return int|null           The amount in the smallest unit of the given currency, or null if the input amount is
     *                            null or empty.
     * @since  1.2.0
     */
    public static function getMinorAmountBasedOnCurrency($amount, $currency)
    {
        return Money::of((float) $amount, $currency, null, RoundingMode::HALF_UP)->getMinorAmount()->toInt();
    }

    /**
     * Get placeholder image path
     *
     * @return string
     */
    public static function getPlaceholderImage()
    {
        return Uri::root(true) . '/media/com_easystore/images/thumbnail.jpg';
    }

    /**
     * Retrieves the menu item ID for a given page link if it exists and is published.
     *
     * This function searches the Joomla menu table to find a menu item where the link
     * matches the provided page link and the item is published.
     *
     * @param string $pageLink The page link to search for in the menu items.
     *
     * @return int|null The menu item ID if found and published, null if not found.
     *
     * @since 1.3.0
     */
    public static function getMenuItemId($pageLink)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select($db->quoteName('id'));
        $query->from($db->quoteName('#__menu'));
        $query->where($db->quoteName('link') . ' LIKE ' . $db->quote('%' . $pageLink . '%'));
        $query->where($db->quoteName('published') . ' = ' . $db->quote('1'));
        $db->setQuery($query);
        $result = $db->loadResult();

        // Return the result if found, otherwise return null
        return $result ?: null;
    }

    /**
     * Processes parameters and plugins for a product component.
     *
     * This function handles the following tasks:
     *
     * 1. Merges app-level parameters.
     * 2. Processes content plugins.
     * 3. Updates the product description with the processed text.
     *
     * @param  object  $product  The product object.
     * @return object  The updated product object.
     *
     * @since 1.3.4
     */
    public static function prepareProductData($product)
    {
        $app    = Factory::getApplication();
        $params = ComponentHelper::getParams("com_easystore");
        $active = $app->getMenu()->getActive();

        // Check if the active menu item matches the component and view
        if ($active && $active->component === 'com_easystore' && $active->query['view'] === 'product') {
            $temp       = clone $params;
            $menuParams = $active->getParams();
            $temp->merge($menuParams);
            $params = $temp;
        }

        // Merge app-level parameters
        $params->merge($app->getParams());

        // Process content plugins
        PluginHelper::importPlugin('content');
        $product->text = $product->description ?? '';
        $app->triggerEvent('onContentPrepare', ['com_easystore.product', &$product, &$params]);

        // Update product description with the processed text
        if (!empty($product->text)) {
            $product->description = $product->text;
        }

        return $product;
    }

    public static function getBrandLink($id){
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select($db->quoteName('alias'))
            ->from($db->quoteName('#__easystore_brands'))
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('id') . ' = ' . $id);
        $db->setQuery($query);
        $result = $db->loadResult();

        if (empty($result)) {
            return '';
        }

        return Route::_('index.php?option=com_easystore&view=products&filter_brands=' . $result);
    }

    /**
     * Get brand image
     *
     * @param int $id
     * @return string
     */
    public static function getBrandImage($id){
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select($db->quoteName('image'))
            ->from($db->quoteName('#__easystore_brands'))
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('id') . ' = ' . $id);
        $db->setQuery($query);
        $result = $db->loadResult();

        if (empty($result)) {
            return static::getPlaceholderImage();
        }

        return Uri::root(true) . '/' . Path::clean($result);
    }

    /**
     * Adds a leading slash to relative image src attributes in an HTML string.
     *
     * Skips:
     * - Absolute URLs (e.g., http://, https://, ftp://)
     * - Protocol-relative URLs (e.g., //example.com/image.jpg)
     * - Data URIs (e.g., data:image/png;base64,...)
     * - Paths already starting with a slash.
     * - Images with empty or missing src attributes.
     *
     * Attempts to preserve the input structure (fragment vs full document).
     *
     * @param string $html The input HTML string (can be a full document or a fragment).
     * @return string|false The modified HTML string, or false on failure to parse the HTML.
     */
    public static function addLeadingSlashToImageSrc($html): string|false
    {
        if (trim($html) === '') {
            return '';
        }

        libxml_use_internal_errors(true);
    
        $dom = new DOMDocument();
        $internalEncodingHtml = '<?xml encoding="UTF-8">' . $html;
        $loadOptions = LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD;
        $loadSuccess = $dom->loadHTML($internalEncodingHtml, $loadOptions);

        if (!$loadSuccess) {
            libxml_clear_errors();
            return false;
        }

        $xpath = new DOMXPath($dom);
        $images = $xpath->query('//img');
    
        foreach ($images as $img) {
            if (!$img->hasAttribute('src')) {
                continue; // Skip images without src
            }

            $src = $img->getAttribute('src');
            $trimmedSrc = trim($src);

            if ($trimmedSrc === '') {
                continue;
            }

            $urlParts = parse_url($trimmedSrc);
            $hasScheme = isset($urlParts['scheme']) && $urlParts['scheme'] !== '';
            $isProtocolRelative = strpos($trimmedSrc, '//') === 0;
            $startsWithSlash = strpos($trimmedSrc, '/') === 0;

            if (!$hasScheme && !$isProtocolRelative && !$startsWithSlash) {
                $img->setAttribute('src', '/' . $trimmedSrc);
            }
        }

        $outputHtml = '';
        foreach ($dom->childNodes as $node) {
            $outputHtml .= $dom->saveHTML($node);
        }

        $outputHtml = preg_replace('/^\s*<\?xml [^>]*\?>\s*/i', '', $outputHtml);
    
        libxml_clear_errors();

        return $outputHtml;
    }

    /**
     * Converts a weight value from one unit to another specified unit.
     * Uses kilograms (kg) as an internal base unit for calculations.
     *
     * @param float $value The numerical weight value to convert.
     * @param string $fromUnit The unit of the input weight (e.g., 'g', 'lb', 'oz', 'kg'). Case-insensitive.
     * @param string $toUnit The desired unit for the output (e.g., 'g', 'lb', 'oz', 'kg'). Case-insensitive.
     * @return float The converted value in the target unit, or the original value if either unit is not recognized.
     */
    public static function convertWeight(float $value, string $fromUnit, string $toUnit): float
    {
        $conversionFactorsToKg = [
            'kg' => 1.0,
            'g' => 0.001,
            'lb' => 0.45359237,
            'oz' => 0.0283495231,
        ];

        $normalizedFromUnit = strtolower(trim($fromUnit));

        $normalizedToUnit = strtolower(trim($toUnit));

        if (!array_key_exists($normalizedFromUnit, $conversionFactorsToKg) || !array_key_exists($normalizedToUnit, $conversionFactorsToKg)) {

            $normalizedFromUnit = $normalizedToUnit;
        }

        $valueInKg = $value * $conversionFactorsToKg[$normalizedFromUnit];

        $factorFromKgToToUnit = 1 / $conversionFactorsToKg[$normalizedToUnit];

        $convertedValue = $valueInKg * $factorFromKgToToUnit;

        return $convertedValue;
    }

    /**
     * Get the count of products associated with a specific brand.
     *
     * @param int $brandId The ID of the brand.
     * @return int The count of products associated with the brand.
     * 
     * @since 1.5.0
     */
    public static function getBrandProductCount(int $brandId)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('COUNT(DISTINCT a.id)')
            ->from($db->quoteName('#__easystore_products', 'a'))
            ->where($db->quoteName('a.brand_id') . ' = ' . $brandId)
            ->where($db->quoteName('a.published') . ' = 1');

        $db->setQuery($query);

        try {
            return (int) $db->loadResult();
        } catch (\RuntimeException $e) {
            return 0;
        }
    }
}
