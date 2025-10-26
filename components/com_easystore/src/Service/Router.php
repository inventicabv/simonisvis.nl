<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Site\Service;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\Database\ParameterType;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

/**
 * Routing class for the EasyStore component
 */
class Router extends RouterView
{
    /**
     * Database object
     *
     * @var DatabaseInterface
     */
    private $db;

    /**
     * Router constructor
     *
     * @param   SiteApplication  $app   The application object
     * @param   AbstractMenu     $menu  The menu object to work with
     */
    public function __construct(SiteApplication $app, AbstractMenu $menu)
    {
        // Initialize database object
        $this->db = Factory::getContainer()->get(DatabaseInterface::class);

        // Register views
        $products = new RouterViewConfiguration('products');
        $products->setKey('catid')->setNestable();
        $this->registerView($products);

        $product = new RouterViewConfiguration('product');
        $product->setKey('id')->setParent($products, 'catid');
        $this->registerView($product);

        $collections = new RouterViewConfiguration('collections');
        $this->registerView($collections);

        $collection = new RouterViewConfiguration('collection');
        $collection->setKey('id')->setParent($collections);
        $this->registerView($collection);

        $orders = new RouterViewConfiguration('orders');
        $this->registerView($orders);

        $order = new RouterViewConfiguration('order');
        $order->setKey('id')->setParent($orders);
        $this->registerView($order);

        $payment = new RouterViewConfiguration('payment');
        $payment->setKey('type')->setParent($payment);
        $this->registerView($payment);

        $this->registerView(new RouterViewConfiguration('profile'));
        $this->registerView(new RouterViewConfiguration('guest'));
        $this->registerView(new RouterViewConfiguration('cart'));
        $this->registerView(new RouterViewConfiguration('checkout'));
        $this->registerView(new RouterViewConfiguration('brands'));

        // Call parent constructor
        parent::__construct($app, $menu);

        // Attach rules
        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    /**
     * Method to get the segment(s) for a list of products under a category.
     * @param int $id Category ID
     * @param array $query An associative array of URL arguments
     */
    public function getProductsSegment($id, $query)
    {
        // Fetch the category path based on the category ID
        $categoryPath = EasyStoreHelper::getCategoryPath($id);

        // If the path exists, construct the segment
        if (isset($categoryPath['aliasPath']) && !empty($categoryPath['aliasPath'])) {
            $path    = $categoryPath['aliasPath'];
            $path[0] = 'root'; // Add the root segment
            return $path;
        }
        return [];
    }

    /**
     * Method to get the segment(s) for a product
     * @param int $id Product ID
     * @param array $query An associative array of URL arguments
     */
    public function getProductSegment($id, $query)
    {
        // Fetch alias based on product ID
        $dbquery = $this->db->getQuery(true)
            ->select($this->db->quoteName(['alias']))
            ->from($this->db->quoteName('#__easystore_products'))
            ->where($this->db->quoteName('id') . ' = ' . (int) $id);
        $this->db->setQuery($dbquery);

        $alias = $this->db->loadResult();
        return [$id => $alias];
    }

    /**
     * Method to get the ID for a category by its alias
     * @param string $segment The alias of the category
     * @param array $query An associative array of URL arguments
     */
    public function getProductsId($segment, $query)
    {
        // Fetch the ID based on the alias
        $dbquery = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__easystore_categories'))
            ->where($this->db->quoteName('alias') . ' = ' . $this->db->quote($segment));
        $this->db->setQuery($dbquery);
        return $this->db->loadResult();
    }

    /**
     * Method to get the ID for a product by its alias
     * @param string $segment The alias of the product
     * @param array $query An associative array of URL arguments
     */
    public function getProductId($segment, $query)
    {
        $dbquery = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__easystore_products'))
            ->where($this->db->quoteName('alias') . ' = ' . $this->db->quote($segment));

        // Additional condition if 'catid' exists in the query
        if (isset($query['catid']) && $query['catid'] > 0) {
            $dbquery->where($this->db->quoteName('catid') . ' = ' . (int) $query['catid']);
        }

        $this->db->setQuery($dbquery);
        return $this->db->loadResult();
    }
    
    /**
     * Method to get the segment(s) for a list of products under a category.
     * @param int $id Category ID
     * @param array $query An associative array of URL arguments
     */
    public function getCollectionsSegment($id, $query)
    {
        return $this->getCollectionSegment($id, $query);
    }

    /**
     * Method to get the segment(s) for a product
     * @param int $id Product ID
     * @param array $query An associative array of URL arguments
     */
    public function getCollectionSegment($id, $query)
    {
        $dbQuery = $this->db->getQuery(true)
            ->select($this->db->quoteName('alias'))
            ->from($this->db->quoteName('#__easystore_collections'))
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $this->db->setQuery($dbQuery);

        $alias = $this->db->loadResult();

        return [(int) $id => $alias];
    }

    /**
     * Method to get the ID for a category by its alias
     * @param string $segment The alias of the category
     * @param array $query An associative array of URL arguments
     */
    public function getCollectionsId($segment, $query)
    {
        return $this->getCollectionId($segment, $query);
    }

    /**
     * Method to get the ID for a product by its alias
     * @param string $segment The alias of the product
     * @param array $query An associative array of URL arguments
     */
    public function getCollectionId($segment, $query)
    {
        $dbquery = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__easystore_collections'))
            ->where($this->db->quoteName('alias') . ' = :segment')
            ->bind(':segment', $segment);

        $this->db->setQuery($dbquery);

        return (int) $this->db->loadResult();
    }

    /**
     * Method to get the ID for a order by its alias
     * @param string $segment The alias of the order
     * @param array $query An associative array of URL arguments
     */
    public function getOrderId($segment, $query)
    {
        $dbquery = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__easystore_orders'))
            ->where($this->db->quoteName('id') . ' = ' . $this->db->quote($segment));

        $this->db->setQuery($dbquery);
        return $this->db->loadResult();
    }

    /**
     * Method to get the segment(s) for a order
     * @param int $id order ID
     * @param array $query An associative array of URL arguments
     */
    public function getOrderSegment($id, $query)
    {
        // Fetch alias based on order ID
        $dbquery = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__easystore_orders'))
            ->where($this->db->quoteName('id') . ' = ' . (int) $id);
        $this->db->setQuery($dbquery);

        $alias = $this->db->loadResult();
        return [$id => $alias];
    }
}
