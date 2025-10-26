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

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Application\CMSApplication;

/**
 * EasystoreVariant Field.
 *
 * @since  1.0.0
 */
class EasystoreVariantField extends FormField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'EasystoreVariant';

    protected function getInput()
    {
        /**
         * @var CMSApplication
         */
        $application = Factory::getApplication();
        $document    = $application->getDocument();
        $wa          = $document->getWebAssetManager();
        $wa->useScript('com_easystore.app.admin');

        $inputValue = !empty($this->value) && !is_string($this->value) ? json_encode($this->value) : '';
        $input      = '<input type="hidden" name="' . $this->name . '" id="' . $this->id . '" value=\'' . $inputValue . '\' />';

        $html = [];

        $html[] = '<div class="easystore-product-image-variants">';
        $html[] = '<div id="easystore-product-variants"></div>';
        $html[] = $input;
        $html[] = '</div>';

        return implode("\r\n", $html);
    }
}
