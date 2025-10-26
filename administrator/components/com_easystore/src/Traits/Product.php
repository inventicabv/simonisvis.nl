<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Traits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use JoomShaper\Component\EasyStore\Administrator\Model\ProductModel;

trait Product
{
    /**
     * Function for processing productsWithVariants api methods
     *
     * @return void
     */
    public function productsWithVariants()
    {
        $requestMethod = $this->getInputMethod();

        $this->checkNotAllowedMethods(['POST', 'PUT', 'PATCH', 'DELETE'], $requestMethod);

        $this->getProductsWithVariants();
    }

    /**
     * Function to get Products with variants
     *
     * @return void
     */
    protected function getProductsWithVariants()
    {
        $params = (object) [
            'limit'  => $this->getInput('limit', 10),
            'offset' => $this->getInput('offset', 0),
            'search' => $this->getInput('search', '', 'STRING'),
        ];

        $model    = new ProductModel();
        $products = $model->getProductsWithVariants($params);

        $this->sendResponse($products);
    }

    public function browseProducts()
    {
        $search = $this->getInput('search', '', 'STRING');

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('p.id, p.title, p.published, media.src as image')
            ->from($db->quoteName('#__easystore_products', 'p'))
            ->join(
                'LEFT',
                $db->quoteName('#__easystore_media', 'media') . ' ON (' . $db->quoteName('p.id') . ' = ' . $db->quoteName('media.product_id') . ' AND ' . $db->quoteName('media.is_featured') . ' = 1' . ')'
            )
            ->where($db->quoteName('p.published') . ' = 1');

        if ($search) {
            $query->where($db->quoteName('p.title') .  ' LIKE ' . $db->quote('%' . $search . '%'));
        }

        $query->group($db->quoteName('p.id'))
            ->order($db->quoteName('p.title'));

        $query->setLimit(50);

        $db->setQuery($query);

        try {
            $products = $db->loadObjectList();
        } catch (\Exception $e) {
            $this->sendResponse(['message' => $e->getMessage()], 500);
        }

        $fallbackImage = Uri::root(true) . '/media/com_easystore/images/thumbnail.jpg';

        if (!empty($products)) {
            foreach ($products as &$product) {
                $product->image = $product->image ? Uri::root(true) . '/' . $product->image : $fallbackImage;
                $product->url = Route::_('index.php?option=com_easystore&view=product&layout=edit&id=' . $product->id, false);
            }

            unset($product);
        }

        $this->sendResponse($products);
    }
}
