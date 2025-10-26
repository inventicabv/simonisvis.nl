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
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\Path;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


trait ProductMedia
{
    public static function getMedia(int $productId, bool $withRoot = true)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select(['id', 'product_id', 'name', 'type', 'is_featured', 'src', 'alt_text', 'width', 'height'])
            ->from($db->quoteName('#__easystore_media'))
            ->where($db->quoteName('product_id') . ' = ' . $productId)
            ->order($db->quoteName('ordering') . ' ASC');

        $db->setQuery($query);

        try {
            $media = $db->loadObjectList();
        } catch (\Throwable $e) {
            throw $e;
        }

        if (!empty($media)) {
            foreach ($media as &$item) {
                $item->src = $withRoot ? Uri::root(true) . '/' . Path::clean($item->src) : Path::clean($item->src);
            }

            unset($item);
        }

        $thumbnail = [];

        $thumbnail = array_values(
            array_filter($media, function ($item) {
                return $item->is_featured;
            })
        );

        return (object) [
            'gallery'   => $media,
            'thumbnail' => reset($thumbnail) ?: null,
        ];
    }

    public static function getThumbnail($images)
    {
        if (empty($images)) {
            return (object) [
                'image' => '',
                'title' => '',
            ];
        }

        $thumb = reset($images);

        return (object) [
            'image' => $thumb->product_image,
            'title' => $thumb->alt,
        ];
    }
}
