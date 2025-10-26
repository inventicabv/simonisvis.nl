<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright (C) 2023 - 2024 JoomShaper. <https: //www.joomshaper.com>
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Traits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper as EasyStoreAdminHelper;

/**
 * Trait for  Product Structured Data
 */
trait ProductStructuredData
{
    /**
     *  Product Structured Data
     *
     * @param object $product   Product
     * @return void
     *
     * @since 1.0.2
     */
    public function generateProductStructuredData($product)
    {
        $settings   = SettingsHelper::getSettings();
        $currency   =  $settings->get('general')->currency ?? EasyStoreAdminHelper::getDefaultCurrency();
        list($full) = !empty($currency) ? explode(':', $currency) : '';

        $availability = '';

        switch ($product) {
            case !empty($product->has_sale):
                $availability = "https://schema.org/Discontinued";
                break;
            case !empty($product->stock) && ($product->stock->status > 0):
                $availability = "https://schema.org/InStock";
                break;
            case !empty($product->stock) && ($product->stock->status < 0):
                $availability = "https://schema.org/OutOfStock";
                break;
            case !empty($product->stock) && ($product->stock->amount < 0):
                $availability = "https://schema.org/SoldOut";
                break;
            default:
                $availability = "https://schema.org/InStock";
                break;
        }


        // Structured data as JSON
        $data = [
            "@context"    => "https://schema.org/",
            "@type"       => "Product",
            "name"        => $product->title,
            "description" => $product->description ?? '',
            "sku"         => isset($product->sku) ? $product->sku : '',
        ];

        if (!empty($product->has_sale)) {
            $data["offers"] = [
                "@type"         => "Offer",
                "url"           => Route::_($product->link, true, Route::TLS_IGNORE, true),
                "priceCurrency" => $full,
                "price"         => $product->discounted_price,
                "availability"  => $availability,
            ];
        }

        $author = [
            "@type" => "Person",
        ];

        if (!empty($product->reviews)) {
            foreach ($product->reviews as $review) {
                $author["name"] = $review->user_name;
            }
        }

        if (isset($product->reviewData) && !empty($product->reviewData->rating)) {
            $data["review"] = [
                "@type"        => "Review",
                "reviewRating" => [
                    "@type"       => "Rating",
                    "ratingValue" => $product->reviewData->rating,
                    "bestRating"  => 5,
                ],
                "author" => $author,
            ];

            $data["aggregateRating"] = [
                "@type"       => "AggregateRating",
                "ratingValue" => $product->reviewData->rating,
                "reviewCount" => $product->reviewData->count,
            ];
        }

        if (isset($product->media)) {
            if (isset($product->media->gallery) && is_array($product->media->gallery)) {
                foreach ($product->media->gallery as $image) {
                    $data["image"][] = Route::_($image->src, true, Route::TLS_IGNORE, true);
                }
            }
        }

        /** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->addInline('script', json_encode($data, JSON_UNESCAPED_UNICODE), [], ['type' => 'application/ld+json']);
    }
}
