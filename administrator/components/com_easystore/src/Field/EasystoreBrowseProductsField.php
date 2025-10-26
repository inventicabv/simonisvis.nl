<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * EasystoreCountry Field class.
 *
 * @since  1.4.0
 */
class EasystoreBrowseProductsField extends FormField
{
    protected $type = 'EasystoreBrowseProducts';

    public function getInput()
    {
        /** @var CMSApplication */
        $app = Factory::getApplication();
        $document = $app->getDocument();
        $wa = $document->getWebAssetManager();
        $wa->useScript('com_easystore.app.admin.browse-products');

        $inputValue = !empty($this->value) && !is_string($this->value) ? json_encode($this->value) : '';
        $input      = '<input type="hidden" name="' . $this->name . '" id="easystore_' . $this->id . '" value=\'' . $inputValue . '\' />';
        
        $document->addScriptDeclaration("
            Joomla.fields ??= [];
            Joomla.fields.push({
                id: 'app_" . $this->id . "',
                label: '" . $this->getAttribute('label') . "'
            });
        ");

        $html = [];
        $html[] = '<div>';
        $html[] = '<div id="app_' . $this->id . '"></div>';
        $html[] = $input;
        $html[] = '</div>';

        return implode("\n", $html);
    }
}
