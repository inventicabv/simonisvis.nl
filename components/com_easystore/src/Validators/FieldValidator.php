<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Validators;

use JoomShaper\Component\EasyStore\Site\Validators\Rules\RuleFactory;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Class responsible for validating form fields.
 *
 * @since 1.2.0
 */
class FieldValidator
{
    /**
     * @var array Holds validation errors.
     *
     * @since 1.2.0
     */
    protected $errors = [];

    /**
     * @var array Associative array of field validators.
     *
     * @since 1.2.0
     */
    protected $fields = [];

    /**
     * Add a field with specified validation rules to the validator.
     *
     * @param mixed $value The value to be validated.
     * @param array $ruleTypes An associative array where keys are field names and values are arrays of rule types.
     * @param string $message [Optional] Custom message to be passed to the show the field message.
     * @param array $options [Optional] Options to be passed to the validation rules.
     * @return void
     * @since 1.2.0
     */
    public function addField($value, $ruleTypes, $message = '', $options = [])
    {
        foreach ($ruleTypes as $field => $types) {
            $rules     = [];
            foreach ($types as $type) {
                $rules[] = RuleFactory::createRule($type, $message, $options);
            }
            $this->fields[] = ['value' => $value, 'rules' => $rules, 'field' => $field];
        }
    }

    /**
     * Validate the fields based on the specified validation rules.
     *
     * @return array An associative array containing field names as keys and error messages as values.
     *
     * @since 1.2.0
     */
    public function validate(): array
    {
        $this->errors = [];

        foreach ($this->fields as $field) {
            $value     = $field['value'];
            $rules     = $field['rules'];
            $fieldName = $field['field'];

            if (is_array($value)) {
                // Handle nested field validation (e.g., shipping_address)
                foreach ($value as $subFieldName => $subFieldValue) {
                    foreach ($rules as $rule) {
                        if (!$rule->validate($subFieldValue)) {
                            $this->errors[$fieldName][$subFieldName] = $rule->getErrorMessage();
                            break; // Move to the next sub-field after encountering the first error
                        }
                    }
                }
            } else {
                // Single field validation
                foreach ($rules as $rule) {
                    if (!$rule->validate($value)) {
                        $this->errors[$fieldName] = $rule->getErrorMessage();
                        break; // Move to the next field after encountering the first error
                    }
                }
            }
        }

        return $this->errors;
    }
}
