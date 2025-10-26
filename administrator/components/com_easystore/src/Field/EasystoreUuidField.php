<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Field;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Form\FormField;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;

/**
 * PopupModal field.
 *
 * @since  1.0.0
 */
class EasystoreUuidField extends FormField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'EasystoreUuid';

    protected function getInput()
    {
        $value = $this->value;

        if (empty($value)) {
            $value = htmlspecialchars(EasyStoreHelper::generateUuidV4(), ENT_QUOTES);
        }

        return '<input type="hidden" name="' . $this->name . '" id="' . $this->id . '" value="' . $value . '" />';
    }
}
