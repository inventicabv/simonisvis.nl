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

interface Transformer
{
    /**
     * most transformers don't require targets to train so null allow to use fit method without setting targets
     */
    public function fit(array $samples, ?array $targets = null): array;

    public function transform(array $samples, ?array &$targets = null): array;
}
