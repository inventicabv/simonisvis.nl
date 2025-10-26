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

class StopWords
{
    /**
     * @var array
     */
    protected $stopWords = [];

    public function __construct(array $stopWords)
    {
        $this->stopWords = array_fill_keys($stopWords, true);
    }

    public function isStopWord(string $token): bool
    {
        return isset($this->stopWords[$token]);
    }

    public static function factory(string $language = 'English'): self
    {
        $className = __NAMESPACE__ . "\\StopWords\\$language";

        if (!class_exists($className)) {
            throw new InvalidArgumentException(sprintf('Can\'t find "%s" language for StopWords', $language));
        }

        return new $className();
    }
}
