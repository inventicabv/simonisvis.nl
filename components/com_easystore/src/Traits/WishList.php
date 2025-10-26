<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright (C) 2023 - 2024 JoomShaper. <https: //www.joomshaper.com>
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Traits;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

trait WishList
{
    /**
     * A function to determine whether a product has been added to the wishlist or not.
     *
     * @param  int $productId      Product ID
     * @param  int $userId         User ID
     * @since  1.0.0
     * @return mixed
     */
    public static function isProductInWishlist($productId, $userId)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('*')
            ->from($db->quoteName('#__easystore_wishlist'))
            ->where($db->quoteName('product_id') . " = " . $db->quote($productId))
            ->where($db->quoteName('user_id') . " = " . $db->quote($userId));

        $db->setQuery($query);

        try {
            $result = (bool) $db->loadResult();
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }

        return $result;
    }
}
