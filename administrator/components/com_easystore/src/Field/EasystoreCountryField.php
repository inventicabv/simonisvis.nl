<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Field;

use Joomla\CMS\Form\Field\ListField;
use JoomShaper\Component\EasyStore\Administrator\Constants\CountryCodes;
use phpseclib3\File\ASN1\Maps\CountryName;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * EasystoreCountry Field class.
 *
 * @since  1.0.0
 */
class EasystoreCountryField extends ListField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'EasystoreCountry';

    /**
     * We use a custom layout that allows for the link to be copied.
     *
     * @var  string
     * @since  1.0.0
     */
    protected $layout = 'joomla.form.field.country';

    /**
     * Method to attach a form object to the field.
     *
     * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` user for the form field object.
     * @param   mixed              $value    The form field value to validate.
     * @param   string             $group    The field name group control value. This acts as as an array container for the field.
     *                                       For example if the field has name="foo" and the group value is set to "bar" then the
     *                                       full field name would end up being "bar[foo]".
     *
     * @return  bool  True on success.
     *
     * @since  1.0.0
     */
    public function setup(\SimpleXMLElement $element, $value, $group = null)
    {
        return parent::setup($element, $value, $group);
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
        $countriesJsonPath = JPATH_ROOT . '/media/com_easystore/data/countries.json';

        if (file_exists($countriesJsonPath)) {
            $list     = json_decode(file_get_contents($countriesJsonPath));

            // Remove EU country code
            $list = array_filter($list, function ($country) {
                return $country->numeric_code !== CountryCodes::EUROPEAN_UNION;
            });

            $options  = ['' => 'Select'];
            $listType = $this->element['listType'];

            $shippingCountry = $this->form->getValue('shipping_country');
            $billingCountry  = $this->form->getValue('billing_country');

            foreach ($list as $value) {
                switch ($listType) {
                    case 'phoneCode':
                        $phoneCodeList               = $value->emoji . ' ' . $value->name . ' ' . $value->phone_code;
                        $options[$value->phone_code] = $phoneCodeList;
                        break;

                    case 'country':
                        $options[$value->numeric_code] = $value->name;
                        break;

                    case 'state':
                        if (
                            ($shippingCountry && $value->numeric_code === $shippingCountry) ||
                            ($billingCountry && $value->numeric_code === $billingCountry && $billingCountry != $shippingCountry)
                        ) {
                            $options = $this->getStates($value->states);
                            break 2; // Exit the loop and switch statement
                        }
                        break;
                }
            }
        }

        return $options;
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
        $data             = $this->getLayoutData();
        $data['options']  = $this->getOptions();
        $data['listType'] = $this->element['listType'];

        $renderer = $this->getRenderer($this->layout);

        $renderer->setComponent('com_easystore');
        $renderer->setClient(1);

        return $renderer->render($data);
    }

    /**
     * Extracts state options from an array of state objects.
     *
     * @param  array $states An array of state objects.
     *
     * @return array         An associative array where keys are state IDs and values are state names.
     * @since  1.0.0
     */
    public function getStates($states)
    {
        foreach ($states as $value) {
            $stateOptions[$value->id] = $value->name;
        }

        return $stateOptions ?? [];
    }
}
