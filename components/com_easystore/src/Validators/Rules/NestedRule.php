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
 * Class responsible for nested validation rules.
 *
 * @since 1.2.0
 */
class NestedRule implements FieldRuleInterface
{
    /**
     * Option for nested rules
     *
     * @var array $nestedOptionRules
     *
     * @since 1.2.0
     */
    private $nestedOptionRules;

    /**
     * Custom message
     * @var
     */
    private $message;

    /**
     * Errors list
     * @var array
     * @since 1.2.0
     */
    private $errors = [];

    /**
     * Nested Rule __construct
     *
     * @param string $message Custom message for pattern to match
     * @param mixed $options  Nested rules options
     *
     * @throws \InvalidArgumentException
     *
     * @since 1.2.0
     */
    public function __construct($message, $options)
    {
        if (!isset($options['nested_options'])) {
            throw new InvalidArgumentException("Nested rule requires an 'nested_options' option.");
        }
        $this->nestedOptionRules = $options['nested_options'];

        if (!isset($message)) {
            throw new InvalidArgumentException("Custom message requires a 'message' value.");
        }

        $this->message = $message;
    }

    /**
     * Validate the nested rule
     *
     * @param mixed $value  value for validation
     *
     * @return bool
     *
     * @since 1.2.0
     */
    public function validate($value): mixed
    {
        $nested = json_decode($value, true);

        if ($nested === null) {
            $this->errors['_main'] = Text::_("COM_EASYSTORE_ERROR_MESSAGE_INVALID_JSON_FIELD");
            return false;
        }

        foreach ($this->nestedOptionRules as $fieldName => $fieldRules) {
            if (!isset($nested[$fieldName])) {
                $this->errors[$fieldName] = $this->message ?: Text::_("COM_EASYSTORE_ERROR_MESSAGE_REQUIRED_FIELD");
                continue; // Continue to the next field, as it's not present
            }

            foreach ($fieldRules as $ruleType) {
                $rule = RuleFactory::createRule($ruleType);
                if (!$rule->validate($nested[$fieldName])) {
                    $this->errors[$fieldName] = $rule->getErrorMessage();
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Error Message
     *
     * @return mixed
     *
     * @since 1.2.0
     */
    public function getErrorMessage(): mixed
    {
        $errorMessages = $this->errors;
        if (isset($errorMessages['_main'])) {
            unset($errorMessages['_main']); // Remove the main error key from the error messages array
        }
        return $errorMessages;
    }
}
