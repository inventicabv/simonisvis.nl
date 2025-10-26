<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Validators\Rules;

use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Rule class of Required
 *
 * @since 1.2.0
 */
class RequiredRule implements FieldRuleInterface
{
    /**
     * Custom error message
     *
     * @var string $message
     *
     * @since 1.2.0
     */
    private $message;

    /**
     * Required Rule __construct
     *
     * @param mixed $message Custom message for required rule
     *
     * @throws \InvalidArgumentException
     *
     * @since 1.2.0
     */
    public function __construct($message)
    {
        if (!isset($message)) {
            throw new \InvalidArgumentException("Custom message requires a 'message' value.");
        }

        $this->message = $message;
    }

    /**
     * Validate the required rule
     *
     * @param mixed $value  value for validation
     *
     * @return bool
     *
     * @since 1.2.0
     */
    public function validate($value): bool
    {
        return !empty($value);
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
        return $this->message ?: Text::_("COM_EASYSTORE_ERROR_MESSAGE_REQUIRED_FIELD");
    }
}
