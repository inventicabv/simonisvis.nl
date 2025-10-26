<?php

/**
 * @package     EasyStore.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Administrator\Constants;

/**
 * Product source constants.
 *
 * @since 1.4.0
 */
final class ProductListSource
{
    /**
     * Represents products from a collection
     *
     * @since 1.4.0
     */
    public const COLLECTION = 'collection';

    /**
     * Represents the latest products
     *
     * @since 1.4.0
     */
    public const LATEST = 'latest';

    /**
     * Represents the oldest products
     *
     * @since 1.4.0
     */
    public const OLDEST = 'oldest';

    /**
     * Represents products that are on sale
     *
     * @since 1.4.0
     */
    public const ON_SALE = 'on_sale';

    /**
     * Represents the best-selling products
     *
     * @since 1.4.0
     */
    public const BEST_SELLING = 'best_selling';

    /**
     * Represents featured products
     *
     * @since 1.4.0
     */
    public const FEATURED = 'featured';

    /**
     * Represents products in the wishlist
     *
     * @since 1.4.0
     */
    public const WISHLIST = 'wishlist';

    /**
     * Represents related products
     *
     * @since 1.4.0
     */
    public const RELATED = 'related';

    /**
     * Represents products in the up-sells
     *
     * @since 1.5.0
     */
    public const UP_SELLS = 'up_sells';
}
