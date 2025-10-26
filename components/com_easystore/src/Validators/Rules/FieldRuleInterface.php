<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Validators\Rules;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Field Rule Interface
 *
 * @since 1.2.0
 */
interface FieldRuleInterface
{
    /**
     * Validate the rule
     *
     * @param mixed $value value for validation
     *
     * @return mixed
     *
     * @since 1.2.0
     */
    public function validate($value): mixed;

    /**
     * Error Message
     *
     * @return mixed
     *
     * @since 1.2.0
     */
    public function getErrorMessage(): mixed;
}
