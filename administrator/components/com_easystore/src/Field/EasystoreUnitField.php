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

use Joomla\CMS\Form\Field\TextField;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;

/**
 * EasyStore Unit Field.
 *
 * @since  1.0.0
 */
class EasystoreUnitField extends TextField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'EasystoreUnit';

    /**
     * Method to attach a Form object to the field.
     *
     * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
     * @param   mixed              $value    The form field value to validate.
     * @param   string             $group    The field name group control value. This acts as an array container for the field.
     *                                       For example if the field has name="foo" and the group value is set to "bar" then the
     *                                       full field name would end up being "bar[foo]".
     *
     * @return  bool  True on success.
     *
     * @see     FormField::setup()
     * @since   3.2
     */
    public function setup(\SimpleXMLElement $element, $value, $group = null)
    {
        $result   = parent::setup($element, $value, $group);
        $settings = SettingsHelper::getSettings();

        if ($result) {
            $currency       = $settings->get('general.currency', EasyStoreHelper::getDefaultCurrency());

            $chunks         = explode(':', $currency);
            $currencySymbol = $chunks[1] ?? EasyStoreHelper::getDefaultCurrency('symbol');

            $weight    = $settings->get('products.standardUnits.weight', 'kg');
            $dimension = $settings->get('products.standardUnits.dimension', 'm');

            switch ($this->element['addonBefore']) {
                case 'currency':
                    $this->addonBefore = $currencySymbol;
                    break;
                case 'weight':
                    $this->addonBefore = $weight;
                    break;
                case 'dimension':
                    $this->addonBefore = $dimension;
                    break;
                default:
                    $this->addonBefore = (string) $this->element['addonBefore'];
                    break;
            }
        }

        return $result;
    }
}
