<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Helper;

use DateTime;
use Exception;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Class StringDecorator
 *
 * This class is responsible for decorating and undecorating strings with a prefix and/or suffix.
 * It can be used to format order IDs or any other string values.
 * 
 * It provides methods to apply and remove decorations, as well as to check if any decorations are set. Example usage:
 * 
 * $decorator = new StringDecorator('#', '-');
 * $decorated = $decorator->decorate('12345'); // Returns '#12345-'
 * $undecorated = $decorator->undecorate('#12345-'); // Returns '12345'
 *
 * The class also allows for creating an instance with settings from a configuration source. Example:
 * 
 * $decorator = StringDecorator::fromSettings();
 * $decorated = $decorator->decorate('12345'); // Returns '#12345-' based on settings
 * 
 * 
 * @since 1.5.0
 */
final class StringDecorator
{
    /**
     * @var string Default prefix to apply
     */
    private string $prefix;

    /**
     * @var string Default suffix to apply
     */
    private string $suffix;

    /**
     * @param string $prefix Default prefix to apply
     * @param string $suffix Default suffix to apply
     * 
     * @throws \InvalidArgumentException If prefix or suffix is not a string
     * 
     * @since 1.5.0
     */
    public function __construct(string $prefix = '', string $suffix = '')
    {
        $this->prefix = $prefix;
        $this->suffix = $suffix;
    }

    /**
     * Creates a StringDecorator instance with settings from configuration
     * 
     * @return self
     * @since 1.5.0
     * @since 1.7.0 This method retrieves settings for prefix, suffix, and base ID from the configuration
     */
    public static function fromSettings(): self
    {
        $prefix = SettingsHelper::getSettings()->get('general.orderIdPrefix', '#');
        $suffix = SettingsHelper::getSettings()->get('general.orderIdSuffix', '');
        
        return new self($prefix, $suffix);
    }

    /**
     * Decorates a string with prefix and suffix
     *
     * @param string|int $value The value to decorate
     * @return string The decorated string
     * 
     * @since 1.5.0
     */
    public function decorate($value): string
    {
        $value = (string)$value;

        if ($this->isEmpty()) {
            return $value;
        }

        return $this->prefix . $value . $this->suffix;
    }

    /**
     * Removes prefix and suffix from a string
     *
     * @param string $value The decorated string
     * @return string The original string without decoration
     * 
     * @since 1.5.0
     */
    public function undecorated(string $value): string
    {
        if ($this->isEmpty()) {
            return $value;
        }

        $value = $this->removePrefix($value);
        $value = $this->removeSuffix($value);

        return $value;
    }

    /**
     * Checks if the decorator has any decorations to apply
     * 
     * @return bool True if both prefix and suffix are empty, false otherwise
     * 
     * @since 1.5.0
     */
    public function isEmpty(): bool
    {
        return empty($this->prefix) && empty($this->suffix);
    }

    /**
     * Checks if the decorator has a prefix
     * 
     * @return bool True if prefix is not empty, false otherwise
     * 
     * @since 1.5.0
     */
    private function removePrefix(string $value): string
    {
        if (!empty($this->prefix) && strpos($value, $this->prefix) === 0) {
            return substr($value, strlen($this->prefix));
        }
        return $value;
    }

    /**
     * Checks if the decorator has a suffix
     * 
     * @return bool True if suffix is not empty, false otherwise
     * 
     * @since 1.5.0
     */
    private function removeSuffix(string $value): string
    {
        if (!empty($this->suffix) && substr($value, -strlen($this->suffix)) === $this->suffix) {
            return substr($value, 0, -strlen($this->suffix));
        }
        return $value;
    }
}
