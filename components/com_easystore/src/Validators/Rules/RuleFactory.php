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

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Rule Factory class to create rule instance
 *
 * @since 1.2.0
 */
class RuleFactory
{
    /**
     * Create rule class instance
     *
     * @param mixed  $type    Rule type
     * @param string $message Custom message
     * @param mixed  $options Options for extra condition
     *
     * @throws \InvalidArgumentException
     *
     * @return FieldRuleInterface
     *
     * @since 1.2.0
     */
    public static function createRule($type, $message = '', $options = []): FieldRuleInterface
    {
        $className = __NAMESPACE__ . '\\' . ucfirst($type) . 'Rule';

        if (!class_exists($className)) {
            throw new InvalidArgumentException('Unsupported rule type:' . $className . '');
        }

        return new $className($message, $options);
    }
}
