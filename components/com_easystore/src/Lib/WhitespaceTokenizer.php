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

use InvalidArgumentException;

class WhitespaceTokenizer implements Tokenizer
{
    public function tokenize(string $text): array
    {
        $substrings = preg_split('/[\pZ\pC]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if ($substrings === false) {
            throw new InvalidArgumentException('preg_split failed on: ' . $text);
        }

        return $substrings;
    }
}
