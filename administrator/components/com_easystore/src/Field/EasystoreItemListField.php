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

use Joomla\CMS\Form\Field\ListField;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;

/**
 * EasystoreItemList field.
 *
 * @since  1.0.0
 */
class EasystoreItemListField extends ListField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'EasystoreItemList';

    /**
     * To allow creation of new categories.
     *
     * @var    int
     * @since  1.0.0
     */
    protected $allowAdd;

    /**
     * We use a custom layout that allows for the link to be copied.
     *
     * @var  string
     * @since  1.0.0
     */
    protected $layout = 'joomla.form.field.items';

    /**
     * Optional prefix for new list items.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $customPrefix;

    /**
     * Method to attach a JForm object to the field.
     *
     * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the <field /> tag for the form field object.
     * @param   mixed              $value    The form field value to validate.
     * @param   string|null        $group    The field name group control value. This acts as an array container for the field.
     *                                       For example if the field has name="foo" and the group value is set to "bar" then the
     *                                       full field name would end up being "bar[foo]".
     *
     * @return  bool  True on success.
     *
     * @see     FormField::setup()
     * @since   1.0.0
     */
    public function setup(\SimpleXMLElement $element, $value, $group = null)
    {
        $return = parent::setup($element, $value, $group);

        if ($return) {
            $this->allowAdd     = isset($this->element['allowAdd']) ? (bool) $this->element['allowAdd'] : false;
            $this->customPrefix = (string) $this->element['customPrefix'];
        }

        return $return;
    }

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     *
     * @since   1.0.0
     */
    public function getOptions()
    {
        if (isset($this->element['get_settings'])) {
            $settings = $this->element['get_settings'];
            return array_merge(parent::getOptions(), EasyStoreHelper::getSettingsOptions($settings) ?? []);
        } else {
            $tableName  = (string) $this->element['table_name'];
            $keyField   = (string) $this->element['key_field'];
            $valueField = (string) $this->element['value_field'];

            return array_merge(parent::getOptions(), EasyStoreHelper::getOptions($tableName, $valueField, $keyField) ?? []);
        }
    }

    /**
     * Method to get the field input markup for a generic list.
     * Use the multiple attribute to enable multiselect.
     *
     * @return  string  The field input markup.
     *
     * @since   1.0.0
     */
    protected function getInput()
    {
        $data                       = $this->getLayoutData();
        $data['options']            = $this->getOptions();
        $disableAddNew              = (string) $this->element['disable_add_new'];

        if ($disableAddNew == null && $disableAddNew != 'true') {
            $data['allowCustom']    = $this->allowAdd;
            $data['customPrefix']   = $this->customPrefix;
        }

        $renderer = $this->getRenderer($this->layout);
        $renderer->setComponent('com_easystore');
        $renderer->setClient(1);

        return $renderer->render($data);
    }
}
