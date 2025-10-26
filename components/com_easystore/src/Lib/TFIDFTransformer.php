<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Lib;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class TFIDFTransformer implements Transformer
{
    /**
     * @var array
     */
    private $idf = [];

    public function __construct(array $samples = [])
    {
        if (count($samples) > 0) {
            $this->fit($samples);
        }
    }

    public function fit(array $samples, ?array $targets = null): array
    {
        $this->countTokensFrequency($samples);

        $count = count($samples);

        foreach ($this->idf as &$value) {
            $value = log((float) ($count / $value), 10.0);
        }

        return $samples;
    }

    public function transform(array $samples, ?array &$targets = null): array
    {
        foreach ($samples as &$sample) {
            foreach ($sample as $index => &$feature) {
                $feature *= $this->idf[$index];
            }
        }

        unset($sample, $feature);

        return $samples;
    }

    private function countTokensFrequency(array $samples): void
    {
        $this->idf = array_fill_keys(array_keys($samples[0]), 0);

        foreach ($samples as $sample) {
            foreach ($sample as $index => $count) {
                if ($count > 0) {
                    ++$this->idf[$index];
                }
            }
        }
    }
}
