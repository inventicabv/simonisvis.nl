<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Validators\Rules;

use InvalidArgumentException;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Class responsible for pattern validation rule.
 *
 * @since 1.2.0
 */
class PatternRule implements FieldRuleInterface
{
    /**
     * Pattern to validate
     *
     * @var string $pattern
     *
     * @since 1.2.0
     */
    private $pattern;

    /**
     * Custom message
     *
     * @var string $message
     *
     * @since 1.2.0
     */
    private $message;

    /**
     * Pattern Rule __construct
     *
     * @param mixed $options Options for pattern to match
     * @param string $message Custom message for pattern to match
     *
     * @throws \InvalidArgumentException
     *
     * @since 1.2.0
     */
    public function __construct($options, $message)
    {
        if (!isset($options['pattern'])) {
            throw new InvalidArgumentException("Pattern rule requires a 'pattern' option.");
        }

        $this->pattern = $options['pattern'];

        if (!isset($message)) {
            throw new InvalidArgumentException("Custom message requires a 'message' value.");
        }

        $this->message = $message;
    }

    /**
     * Validate the pattern rule
     *
     * @param mixed $value  value for validation
     *
     * @return bool
     *
     * @since 1.2.0
     */
    public function validate($value): bool
    {
        return preg_match($this->pattern, $value) === 1;
    }

    /**
     * Error Message
     *
     * @return string
     *
     * @since 1.2.0
     */
    public function getErrorMessage(): string
    {
        return $this->message ?: Text::_("COM_EASYSTORE_ERROR_MESSAGE_PATTERN_FIELD");
    }
}
