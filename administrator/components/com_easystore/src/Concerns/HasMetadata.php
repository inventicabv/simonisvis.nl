<?php

/**
 * @package     EasyStore.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Concerns;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

/**
 * Trait HasCollection
 *
 * This trait provides methods for managing product-collection relationships.
 *
 * @since 1.4.0
 */
trait HasMetadata
{
    public function validateMetadata(&$data)
    {
        if (empty($data->metatitle)) {
            $data->metatitle = '';
        }

        // Clean up description -- eliminate quotes and <> brackets
        if (!empty($data->metadesc)) {
            $bad_characters = ["\"", '<', '>'];
            $data->metadesc = StringHelper::str_ireplace($bad_characters, '', $data->metadesc);
        }

        if (empty($data->metadesc)) {
            $data->metadesc = '';
        }

        // Clean up keywords -- eliminate extra spaces between phrases
        // and cr (\r) and lf (\n) characters from string
        if (!empty($data->metakey)) {
            // Only process if not empty

            // Array of characters to remove
            $badCharacters = ["\n", "\r", "\"", '<', '>'];

            // Remove bad characters
            $afterClean = StringHelper::str_ireplace($badCharacters, '', $data->metakey);

            // Create array using commas as delimiter
            $keys = explode(',', $afterClean);

            $cleanKeys = [];

            foreach ($keys as $key) {
                if (trim($key)) {
                    // Ignore blank keywords
                    $cleanKeys[] = trim($key);
                }
            }

            // Put array back together delimited by ", "
            $data->metakey = implode(', ', $cleanKeys);
        } else {
            $data->metakey = '';
        }

        if (empty($data->metadata)) {
            $data->metadata = '{}';
        }

        return $data;
    }

    public function implementMetadata($item, $params)
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $document = $app->getDocument();
        $pathway = $app->getPathway();

        if (is_array($item)) {
            $item = (object) $item;
        }

        if (!empty($item->title)) {
            $pathway->addItem($item->title, '');
        }

        $menu = $app->getMenu()->getActive();

        if ($menu) {
            $params->def('page_heading', $params->get('page_title', $menu->title));
        }  else {
            $params->def('page_heading');
        }

        if (in_array($app->getInput()->get('view'), ['product', 'collection']) && (int) $app->getInput()->get('id') === $item->id) {
            $params->set('page_title', $item->title);
        }

        $title = $params->def('page_title', '');

        if (!empty($item->metatitle)) {
            $document->setTitle($item->metatitle);
        } elseif ($title) {
            $document->setTitle($title);
        }

        if (!empty($item->metadesc)) {
            $document->setDescription($item->metadesc);
        } elseif ($params->get('menu-meta_description')) {
            $document->setDescription($params->get('menu-meta_description'));
        }
        if (!empty($item->metakey)) {
            $document->setMetaData('keywords', $item->metakey);
        } elseif ($params->get('menu-meta_keywords')) {
            $document->setMetaData('keywords', $params->get('menu-meta_keywords'));
        }

        // For the collection page, we use the product image as the og:image
        if (!empty($item->image)) {
            $document->setMetaData('og:image', Uri::root() . $item->image);
        }

        // For the product page, we use the product thumbnail as the og:image
        if (!empty($item->media) && !empty($item->media->thumbnail)) {
            $document->setMetaData('og:image', Uri::root() . $item->media->thumbnail->src);
        }

        if ($params->get('robots')) {
            $document->setMetaData('robots', $params->get('robots'));
        }

        if (!empty($item->metadata)) {
            $metadata = new Registry($item->metadata);

            foreach ($metadata as $key => $value) {
                if ($value) {
                    $document->setMetaData($key, $value);
                }
            }
        }
    }
}
