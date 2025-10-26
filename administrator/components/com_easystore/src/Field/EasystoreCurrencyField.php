<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Field;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Component\ComponentHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * EasystoreCurrency Field class.
 *
 * @since  1.0.0
 */
class EasystoreCurrencyField extends ListField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'EasystoreCurrency';

    /**
    * We use a custom layout that allows for the link to be copied.
    *
    * @var  string
    * @since  1.0.0
    */
    protected $layout = 'joomla.form.field.list-fancy-select';

    /**
     * Method to attach a form object to the field.
     *
     * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
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
        $data       = [];
        $cParams    = ComponentHelper::getParams('com_easystore');

        $v    = $cParams->get('currency', EasyStoreHelper::getDefaultCurrency());
        $data = [
            ""         => Text::sprintf('COM_EASYSTORE_SELECT_CURRENCY', $v),
            "USD:$"    => "United States dollar($)",
            "ALL:Lek"  => "Albania Lek(Lek)",
            "AFN:؋"    => "Afghanistan Afghani(؋)",
            "ARS:$"    => "Argentina Peso($)",
            "AWG:ƒ"    => "Aruba Guilder(ƒ)",
            "AUD:$"    => "Australia Dollar($)",
            "AZN:₼"    => "Azerbaijan Manat(₼)",
            "BDT:৳"    => "Bangladesh Taka(৳)",
            "BGN:лв"   => "Bulgaria Lev(лв)",
            "BRL:R$"   => "Brazil Real(R$)",
            "BND:$"    => "Brunei Darussalam Dollar($)",
            "GBP:£"    => "British pound(£)",
            "KHR:៛"    => "Cambodia Riel(៛)",
            "CAD:$"    => "Canadian Dollar($)",
            "CZK:Kč"   => "Czech Koruna(Kč)",
            "DKK:kr."  => "Danish Krone(kr.)",
            "EUR:€"    => "Euro(€)",
            "HKD:HK$"  => "Hong Kong Dollar(HK$)",
            "HUF:Ft"   => "Hungarian Forint(Ft)",
            "INR:₹"    => "India Rupee(₹)",
            "ILS:₪"    => "Israeli New Sheqel(₪)",
            "JPY:¥"    => "Japanese Yen(¥)",
            "MYR:RM"   => "Malaysian Ringgit(RM)",
            "MXN:Mex$" => "Mexican Peso(Mex$)",
            "NOK:kr"   => "Norwegian Krone(kr)",
            "NZD:$"    => "New Zealand Dollar($)",
            "PHP:₱"    => "Philippine Peso(₱)",
            "PLN:zł"   => "Polish Zloty(zł)",
            "RUB:₽"    => "Russian Ruble(₽)",
            "SGD:$"    => "Singapore Dollar($)",
            "SEK:kr"   => "Swedish Krona(kr)",
            "CHF:CHF"  => "Swiss Franc(CHF)",
            "TWD:角"    => "Taiwan New Dollar(角)",
            "THB:฿"    => "Thai Baht(฿)",
            "TRY:TRY"  => "Turkish Lira(TRY)",
        ];


        return array_merge(parent::getOptions(), $data);
    }
}
