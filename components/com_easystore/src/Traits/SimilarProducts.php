<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright (C) 2023 - 2024 JoomShaper. <https: //www.joomshaper.com>
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Traits;

use Throwable;
use JoomShaper\Component\EasyStore\Site\Helper\ArrayHelper;
use JoomShaper\Component\EasyStore\Site\Helper\StringHelper;
use JoomShaper\Component\EasyStore\Site\Lib\TFIDFTransformer;
use JoomShaper\Component\EasyStore\Site\Lib\CosineSimilarities;
use JoomShaper\Component\EasyStore\Site\Lib\WhitespaceTokenizer;
use JoomShaper\Component\EasyStore\Site\Lib\TokenCountVectorizer;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


trait SimilarProducts
{
    protected function generateVectors($dataset)
    {
        if (empty($dataset)) {
            return [];
        }

        $vectorizer = new TokenCountVectorizer(new WhitespaceTokenizer());
        $samples    = $vectorizer->fit($dataset);
        $vectors    = $vectorizer->transform($samples);

        $transformer = new TFIDFTransformer($vectors);
        $vectors     = $transformer->transform($vectors);

        return $vectors;
    }

    public function getSimilarProducts($pk)
    {
        $products = $this->getProducts();
        $dataset  = $this->generateDataset($products);
        $vectors  = $this->generateVectors($dataset);
        $index    = ArrayHelper::findIndex(function ($item) use ($pk) {
            return (int) $item->id === (int) $pk;
        }, $products);

        if ($index < 0 || empty($products) || empty($dataset)) {
            return [];
        }

        $baseVector = $vectors[$index];
        unset($vectors[$index]);
        unset($products[$index]);

        $similarities = [];

        foreach ($products as $i => $value) {
            $similarities[] = (object)[
                'id'    => $value->id,
                'score' => CosineSimilarities::calculate($baseVector, $vectors[$i]),
            ];
        }

        usort($similarities, function ($a, $b) {
            return $b->score <=> $a->score;
        });

        return $similarities;
    }

    public function getProducts()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select('a.id, a.title, c.title as category_title, GROUP_CONCAT(t.title) as tags')
            ->from($db->quoteName('#__easystore_products', 'a'))
            ->join(
                'LEFT',
                $db->quoteName('#__easystore_categories', 'c') .
                ' ON (' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid') . ')'
            )
            ->join(
                'LEFT',
                $db->quoteName('#__easystore_product_tag_map', 'ptm') .
                ' ON (' . $db->quoteName('a.id') . ' = ' . $db->quoteName('ptm.product_id') . ')'
            )
            ->join(
                'LEFT',
                $db->quoteName('#__easystore_tags', 't') .
                ' ON (' . $db->quoteName('t.id') . ' = ' . $db->quoteName('ptm.tag_id') . ')'
            )
            ->group($db->quoteName('a.id'))
            ->group($db->quoteName('a.title'))
            ->where($db->quoteName('a.published') . ' = 1');

        $db->setQuery($query);

        try {
            return $db->loadObjectList() ?? [];
        } catch (Throwable $error) {
            throw $error;
        }
    }

    public function generateDataset($products)
    {
        if (empty($products)) {
            return [];
        }

        $dataset = [];

        foreach ($products as $product) {
            $tags      = str_replace(',', ' ', $product->tags ?? '');
            $dataset[] = StringHelper::toAlphabeticString($product->title . ' ' . $product->category_title . ' ' . $tags);
        }

        return $dataset;
    }
}
