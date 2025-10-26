<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Field\Modal;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Supports a modal product picker.
 *
 * @since  1.0.0
 */
class ProductField extends FormField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'Modal_Product';

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   1.0.0
     */
    protected function getInput()
    {
        /** @var CMSApplication */
        $app = Factory::getApplication();
        $document = $app->getDocument();

        $allowClear     = ((string) $this->element['clear'] != 'false');
        $allowSelect    = ((string) $this->element['select'] != 'false');

        // Load language
        Factory::getApplication()->getLanguage()->load('com_easystore', JPATH_ADMINISTRATOR);

        // The active product id field.
        $value = (int) $this->value ?: '';

        // Create the modal id.
        $modalId = 'Product_' . $this->id;

        /** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
        $wa = $document->getWebAssetManager();

        // Add the modal field script to the document head.
        $wa->useScript('field.modal-fields');

        // Script to proxy the select modal function to the modal-fields.js file.
        if ($allowSelect) {
            static $scriptSelect = null;

            if (is_null($scriptSelect)) {
                $scriptSelect = [];
            }

            if (!isset($scriptSelect[$this->id])) {
                $wa->addInlineScript(
                    "window.jSelectProduct_" . $this->id . " = function (id, title, catid, url, language) {
                        window.processModalSelect('Product_', '" . $this->id . "', id, title, catid, url, language);
                        document.getElementById('jform_request_catid').value = catid;
                    }",
                    [],
                    ['type' => 'module']
                );

                Text::script('JGLOBAL_ASSOCIATIONS_PROPAGATE_FAILED');

                $scriptSelect[$this->id] = true;
            }
        }

        // Setup variables for display.
        $linkProducts = 'index.php?option=com_easystore&amp;view=products&amp;layout=modal&amp;tmpl=component&amp;' . Session::getFormToken() . '=1';

        if (isset($this->element['language'])) {
            $linkProducts .= '&amp;forcedLanguage=' . $this->element['language'];
            $modalTitle    = Text::_('COM_EASYSTORE_FIELD_SELECT_PRODUCT_LABEL') . ' &#8212; ' . $this->element['label'];
        } else {
            $modalTitle    = Text::_('COM_EASYSTORE_FIELD_SELECT_PRODUCT_LABEL');
        }


        $urlSelect = $linkProducts . '&amp;function=jSelectProduct_' . $this->id;

        if ($value) {
            $db    = $this->getDatabase();
            $query = $db->getQuery(true)
                ->select($db->quoteName(['title','catid']))
                ->from($db->quoteName('#__easystore_products'))
                ->where($db->quoteName('id') . ' = :value')
                ->bind(':value', $value, ParameterType::INTEGER);
            $db->setQuery($query);

            try {
                $title = $db->loadResult();
            } catch (\RuntimeException $e) {
                Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            }
            if (empty($title)) {
                $value = '';
            }
        }

        $title = empty($title) ? Text::_('COM_EASYSTORE_FIELD_SELECT_PRODUCT_LABEL') : htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

        // The current product display field.
        $html  = '';

        if ($allowSelect || $allowClear) {
            $html .= '<span class="input-group">';
        }

        $html .= '<input class="form-control" id="' . $this->id . '_name" type="text" value="' . $title . '" readonly size="35">';

        // Select product button
        if ($allowSelect) {
            $html .= '<button'
                . ' class="btn btn-primary' . ($value ? ' hidden' : '') . '"'
                . ' id="' . $this->id . '_select"'
                . ' data-bs-toggle="modal"'
                . ' type="button"'
                . ' data-bs-target="#ModalSelect' . $modalId . '">'
                . '<span class="icon-file" aria-hidden="true"></span> ' . Text::_('JSELECT')
                . '</button>';
        }

        // Clear product button
        if ($allowClear) {
            $html .= '<button'
                . ' class="btn btn-secondary' . ($value ? '' : ' hidden') . '"'
                . ' id="' . $this->id . '_clear"'
                . ' type="button"'
                . ' onclick="window.processModalParent(\'' . $this->id . '\'); return false;">'
                . '<span class="icon-times" aria-hidden="true"></span> ' . Text::_('JCLEAR')
                . '</button>';
        }

        if ($allowSelect || $allowClear) {
            $html .= '</span>';
        }

        // Select product modal
        if ($allowSelect) {
            $html .= HTMLHelper::_(
                'bootstrap.renderModal',
                'ModalSelect' . $modalId,
                [
                    'title'      => $modalTitle,
                    'url'        => $urlSelect,
                    'height'     => '400px',
                    'width'      => '800px',
                    'bodyHeight' => 70,
                    'modalWidth' => 80,
                    'footer'     => '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'
                                        . Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>',
                ]
            );
        }

        // Note: class='required' for client side validation.
        $class = $this->required ? ' class="required modal-value"' : '';

        $html .= '<input type="hidden" id="' . $this->id . '_id" ' . $class . ' data-required="' . (int) $this->required . '" name="' . $this->name
            . '" data-text="' . htmlspecialchars(Text::_('COM_EASYSTORE_FIELD_SELECT_PRODUCT_LABEL'), ENT_COMPAT, 'UTF-8') . '" value="' . $value . '">';

        return $html;
    }

    /**
     * Method to get the field label markup.
     *
     * @return  string  The field label markup.
     *
     * @since   1.0.0
     */
    protected function getLabel()
    {
        return str_replace($this->id, $this->id . '_name', parent::getLabel());
    }
}
