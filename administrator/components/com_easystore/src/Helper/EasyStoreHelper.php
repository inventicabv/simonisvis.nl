<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Helper;

use Throwable;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Image\Image;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Helper\MediaHelper;
use Joomla\Database\ParameterType;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Extension\ExtensionHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper as HelperEasyStoreHelper;
use JoomShaper\Component\EasyStore\Site\Model\CouponModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * EasyStore component helper.
 *
 * @since  1.0.0
 */
class EasyStoreHelper extends ContentHelper
{
    /**
     * Default values for options. Organized by option group.
     *
     * @var     array
     * @since   1.0.0
     */
    protected static $optionDefaults = [
        'option' => [
            'option.attr'         => null,
            'option.disable'      => 'disable',
            'option.id'           => null,
            'option.key'          => 'value',
            'option.key.toHtml'   => true,
            'option.label'        => null,
            'option.label.toHtml' => true,
            'option.text'         => 'text',
            'option.text.toHtml'  => true,
            'option.class'        => 'class',
            'option.onclick'      => 'onclick',
        ],
    ];

    /**
     * Get option list in text/value format for a selected table
     *
     * @param   string $table
     * @param   string $value
     * @param   string $text
     * @return  array
     */
    public static function getOptions(string $table, string $text = 'title', string $value = 'id', ?int $published = null)
    {
        $options = [];

        $db              = Factory::getContainer()->get(DatabaseInterface::class);
        $query           = $db->getQuery(true);
        $isCategoryTable = false;

        if ($table == '#__easystore_categories') {
            $isCategoryTable = true;
        }

        if ($isCategoryTable) {
            $query->select(
                [
                    $db->quoteName($value, 'value'),
                    $db->quoteName($text, 'text'),
                    $db->quoteName('level'),
                    $db->quoteName('lft'),
                ]
            );
        } else {
            $query->select(
                [
                    $db->quoteName($value, 'value'),
                    $db->quoteName($text, 'text'),
                ]
            );
        }
        $query->from($db->quoteName($table, 'a'));

        if (!is_null($published)) {
            $query->where($db->quoteName('a.published') . ' = ' . $published);
        }

        if ($isCategoryTable) {
            $query->where($db->quoteName('a.alias') . ' != ' . $db->quote('root'));
            $query->where($db->quoteName('a.published') . ' = 1');
            $query->order($db->quoteName('a.lft') . ' ASC');
        } else {
            $query->order($db->quoteName('a.' . $text));
        }

        // Get the options.
        $db->setQuery($query);

        try {
            $options = $db->loadObjectList();
        } catch (\RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }

        if ($isCategoryTable) {
            // Pad the option text with spaces using depth level as a multiplier.
            for ($i = 0, $n = \count($options); $i < $n; $i++) {
                $options[$i]->text = str_repeat('- ', !$options[$i]->level ? 0 : $options[$i]->level - 1) . $options[$i]->text;
            }
        }

        return $options;
    }

    /**
     * Get option list in text/value format from settings data
     *
     * @param string $settings
     * @return array
     */
    public static function getSettingsOptions($settings)
    {
        $settingsInfo = EasyStoreHelper::getSettingsData($settings);
        $returnArray  = [];

        if ($settings == 'payment_options_in_plugin') {
            foreach ($settingsInfo as $payment) {
                $returnArray[] = [
                    'value' => $payment,
                    'text'  => ucfirst(str_replace('_', ' ', $payment)),
                ];
            }
        } elseif ($settings == 'shipping') {
            foreach ($settingsInfo as $shipping) {
                if ($shipping->shipping_enable) {
                    $returnArray[] = [
                        'value' => (float) $shipping->shipping_amount . ':' . $shipping->shipping_type,
                        'text'  => $shipping->shipping_type,
                    ];
                }
            }
        }

        return $returnArray;
    }

    /**
     * Get option list in text/value format for a select field
     *
     * @return  array
     */
    public static function getStateOptions()
    {
        $options = [];

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select(
                [
                    $db->quoteName('id', 'value'),
                    $db->quoteName('name', 'text'),
                ]
            )
            ->from($db->quoteName('#__easystore_cities', 'a'))
            ->where($db->quoteName('a.country_id') . ' = id')
            ->setLimit(10);

        // Get the options.
        $db->setQuery($query);

        try {
            $options = $db->loadObjectList();
        } catch (\RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }

        array_unshift($options, HTMLHelper::_('select.option', '0', Text::_('COM_EASYSTORE_FIELD_COUNTRY_EMPTY_LABEL')));

        return $options;
    }

    /**
     * Upload product images
     *
     * @param int       $id     Product ID
     * @param array     $data   Image details
     * @since 1.0.0
     */
    public static function uploadImage($id, $data)
    {
        $app = Factory::getApplication();

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        if (!empty($data) && $data) {
            $dataTypes = [
                ParameterType::INTEGER,
                ParameterType::STRING,
                ParameterType::INTEGER,
                ParameterType::INTEGER,
                ParameterType::STRING,
                ParameterType::STRING,
                ParameterType::INTEGER,
            ];

            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__easystore_media'))
                ->columns($db->quoteName([
                    'product_id',
                    'name',
                    'width',
                    'height',
                    'src',
                    'alt_text',
                    'ordering',
                ]));

            foreach ($data as $value) {
                // sanitize and normalize media field value
                $image = basename(MediaHelper::getCleanMediaFieldValue($value['product_image']));

                $query->values(implode(',', $query->bindArray([
                    $id,
                    $image,
                    $value['width'],
                    $value['height'],
                    $value['product_image'],
                    $value['alt'],
                    0,
                ], $dataTypes)));
            }

            $db->setQuery($query);

            try {
                $db->execute();
                // Create thumbnails of the uploaded images
                self::createThumbnails($data);
            } catch (\RuntimeException $e) {
                $app->enqueueMessage($e->getMessage(), 'error');
            }
        }
    }

    /**
     * Change image path of uploaded image
     *
     * @param   array   $image_infos    Image Details
     * @param   string  $title          Product Title
     * @return  array
     * @since   1.0.0
     */
    public static function changeImagePath($image_infos, $title)
    {
        $cparams          = ComponentHelper::getParams('com_media');
        $defaultImagePath = $cparams->get('image_path');
        $destinationPath  = "$defaultImagePath/easystore/products";
        $folderPath       = JPATH_ROOT . '/' . $destinationPath;

        if (!is_dir($folderPath)) {
            Folder::create($folderPath);
        }

        foreach ($image_infos as &$value) {
            $counter   = random_int(0, 999);
            $imagePath = $value['product_image'];
            //Create source path
            $path    = MediaHelper::getCleanMediaFieldValue($imagePath);
            $srcPath = JPATH_ROOT . '/' . $path;
            //Create new path
            $imgExtension           = pathinfo($srcPath, PATHINFO_EXTENSION);
            $title                  = OutputFilter::stringURLSafe($title);
            $updatedPath            = $destinationPath . '/' . $title . '_' . $counter . '.' . $imgExtension;
            $value['product_image'] = str_replace($path, $updatedPath, $value['product_image']);
            $destPath               = JPATH_ROOT . '/' . $updatedPath;

            try {
                File::move($srcPath, $destPath);
            } catch (\RuntimeException $e) {
                Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            }
        }

        return $image_infos;
    }

    /**
     * Update images in the database
     *
     * @param   int     $id             Product ID
     * @param   array   $image_infos    Image Details
     * @since   1.0.0
     */
    public static function updateImage($id, $image_infos)
    {
        $db  = Factory::getContainer()->get(DatabaseInterface::class);
        $app = Factory::getApplication();

        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__easystore_media'))
            ->where($db->quoteName('product_id') . ' = ' . $id);
        $db->setQuery($query);
        try {
            $db->execute();
            self::uploadImage($id, $image_infos);
        } catch (\RuntimeException $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Update variants in the database
     *
     * @param   int     $id             Product ID
     * @param   array   $variants    Image Details
     * @since   1.0.0
     */
    public static function updateVariants($id, $variants)
    {
        $db  = Factory::getContainer()->get(DatabaseInterface::class);
        $app = Factory::getApplication();

        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__easystore_product_variants'))
            ->where($db->quoteName('product_id') . ' = ' . $id);
        $db->setQuery($query);

        try {
            $db->execute();
            // self::manageVariant($id, $variants);
        } catch (\RuntimeException $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Create Thumbs of the images
     *
     * @param array     $data   Image Details
     * @since 1.0.0
     */
    public static function createThumbnails($data)
    {
        $app     = Factory::getApplication();
        $cParams = ComponentHelper::getParams('com_easystore');
        $size    = $cParams->get('thumbnail_size', '100x100');

        // Thumbs Folder
        $mediaParams      = ComponentHelper::getParams('com_media');
        $defaultImagePath = $mediaParams->get('image_path');
        $destinationPath  = JPATH_ROOT . "/$defaultImagePath/easystore/products/thumbs";

        foreach ($data as $value) {
            $image = MediaHelper::getCleanMediaFieldValue($value['product_image']);
            $image = new Image(JPATH_ROOT . '/' . $image);

            try {
                $image->createThumbnails($size, $image::SCALE_FIT, $destinationPath, false);
            } catch (\Exception $e) {
                $app->enqueueMessage($e->getMessage(), 'error');
            }
        }
    }

    /**
     * Function to get Order status names
     *
     * @param string $value
     * @return string
     */
    public static function getOrderStatusName(string $value)
    {
        if ($value === 'active') {
            $status = Text::_('COM_EASYSTORE_ACTIVE');
        } elseif ($value === 'draft') {
            $status = Text::_('COM_EASYSTORE_INACTIVE');
        } elseif ($value === 'archived') {
            $status = Text::_('COM_EASYSTORE_ARCHIVED');
        } elseif ($value === 'delete') {
            $status = Text::_('COM_EASYSTORE_TRASHED');
        } else {
            $status = Text::_('COM_EASYSTORE_INACTIVE');
        }

        return $status;
    }

    private static function decoded($price)
    {
        return html_entity_decode($price, ENT_QUOTES | ENT_HTML5);
    }

    /**
     * Function to format Currency with settings data
     *
     * @param mixed $currencyValue
     * @param bool $segmentedData   If true then it will return segmented string with <span>
     *
     * @return string|object
     */
    public static function formatCurrency($currencyValue, $segmentedData = false)
    {
        $settings = SettingsHelper::getSettings();

        $currency                  = $settings->get('general.currency', self::getDefaultCurrency());
        $currencyPosition          = $settings->get('general.currencyPosition', 'before');
        $currencyFormat            = $settings->get('general.currencyFormat', 'short');
        $currencyDecimalSeparator  = $settings->get('general.decimalSeparator', '.');
        $currencyThousandSeparator = $settings->get('general.thousandSeparator', ',');

        if (is_null($currencyValue)) {
            $currencyValue = '0.00';
        }

        if (empty($currency)) {
            return self::decoded($currencyValue);
        }

        $chunks = explode(':', $currency);

        if ($segmentedData) {
            $result                     = new \stdClass();
            $result->main_value         = $currencyValue;
            $updatedCurrencyValue       = number_format($currencyValue, 2, $currencyDecimalSeparator, $currencyThousandSeparator);
            $result->formatted_value    = $updatedCurrencyValue;
            $updatedCurrencyObject      = self::separateAmountAndDecimal($updatedCurrencyValue, $currencyDecimalSeparator);
            $result->currency_format    = $currencyFormat;
            $result->currency_position  = $currencyPosition;
            $result->decimal_separator  = $currencyDecimalSeparator;
            $result->thousand_separator = $currencyThousandSeparator;
            $currency                   = $currencyFormat === 'short' ? $chunks[1] : $chunks[0];
            $result->currency           = $currency;
            $result->segments           = new \stdClass();
            $result->segments->amount   = $updatedCurrencyObject->amount;
            $result->segments->decimal  = $updatedCurrencyObject->decimal;

            return $result;
        } else {
            $updatedCurrencyValue = number_format($currencyValue, 2, $currencyDecimalSeparator, $currencyThousandSeparator);
            $currency             = $currencyFormat === 'short' ? $chunks[1] : $chunks[0] . ' ';
            $updatedCurrencyValue = $currencyPosition === 'before'
            ? $currency . $updatedCurrencyValue
            : $updatedCurrencyValue . $currency;

            return self::decoded($updatedCurrencyValue);
        }
    }

    /**
     * Function to separate Number amount & Decimal value
     *
     * @param float $number
     * @param string $decimalSeparator
     * @return object
     */
    private static function separateAmountAndDecimal($number, $decimalSeparator = '.')
    {
        // Convert the number to a string to make it easier to split
        $numberStr = strval($number);

        // Split the string at the decimal point
        $parts = explode($decimalSeparator, $numberStr);

        // If there is no decimal point, assume the decimal value is '00'
        if (count($parts) == 1) {
            $amount  = $parts[0];
            $decimal = '00';
        } else {
            $amount  = $parts[0];
            $decimal = $parts[1];
        }

        return (object) ['amount' => $amount, 'decimal' => $decimal];
    }

    /**
     *  Calculate Discounted Price
     *
     * @param  string  $discountType     Discount Type: Percentage / Amount
     * @param  string  $discountValue    Discount Amount
     * @param  string  $price            Regular Price
     * @return float
     * @since  1.0.0
     */
    public static function calculateDiscountedPrice($discountType, $discountValue, $price)
    {
        if ($discountType === 'percent') {
            $discountedPrice = (float) $price - ((float) $price * (float) $discountValue) / 100;
        } else {
            $discountedPrice = (float) $price - (float) $discountValue;
        }
        return (float) bcdiv($discountedPrice * 100, '100', 2);
    }

    /**
     * Calculate the discount value by it's properties
     *
     * @param string $discountType
     * @param string|float $discountValue
     * @param string|float $price
     * @param int $quantity
     * @return float
     */
    public static function calculateDiscountValue($discountType, $discountValue, $price, $quantity = null)
    {
        if ($discountType === 'percent') {
            $discountPrice = ((float) $price * (float) $discountValue) / 100;
        } else {
            if (!empty($quantity)) {
                $discountPrice = (float) $discountValue * $quantity;
            } else {
                $discountPrice = (float) $discountValue;
            }
        }

        return $discountPrice;
    }

    /**
     * Function to get currency symbol
     *
     * @return string
     */
    public static function getCurrencySymbol()
    {
        $settings = SettingsHelper::getSettings();
        $currency = $settings->get('general.currency', self::getDefaultCurrency());
        $chunks   = explode(':', $currency);
        return $chunks[1] ?? self::getDefaultCurrency('symbol');
    }

    /**
     * Function to get settings data
     *
     * @param string $settings
     * @param mixed $default
     * @return mixed
     */
    public static function getSettingsData($settings, $default = [])
    {
        $params       = ComponentHelper::getComponent('com_easystore')->getParams();
        $shippingInfo = $params->get($settings);

        return $shippingInfo ?? $default;
    }

    /**
     * Generates an HTML selection list.
     *
     * @param   array    $data       An array of objects, arrays, or scalars.
     * @param   string   $name       The value of the HTML name attribute.
     * @param   mixed    $attribs    Additional HTML attributes for the `<select>` tag.
     * @param   string   $optKey     The name of the object variable for the option value. If
     *                               set to null, the index of the value array is used.
     * @param   string   $optText    The name of the object variable for the option text.
     * @param   mixed    $selected   The key that is selected (accepts an array or a string).
     * @param   mixed    $idtag      Value of the field id or null by default
     * @param   bool  $translate  True to translate
     *
     * @return  string  HTML for the select list.
     *
     * @since   1.0.0
     */
    public static function customList(
        $data,
        $name,
        $attribs = null,
        $optKey = 'value',
        $optText = 'text',
        $selected = null,
        $idtag = false,
        $translate = false
    ) {
        // Set default options
        $options = array_merge(HTMLHelper::$formatOptions, ['format.depth' => 0, 'id' => false]);

        if (is_array($attribs) && func_num_args() === 3) {
            // Assume we have an options array
            $options = array_merge($options, $attribs);
        } else {
            // Get options from the parameters
            $options['id']             = $idtag;
            $options['list.attr']      = $attribs;
            $options['list.translate'] = $translate;
            $options['option.key']     = $optKey;
            $options['option.text']    = $optText;
            $options['list.select']    = $selected;
        }

        $attribs = '';

        if (isset($options['list.attr'])) {
            if (is_array($options['list.attr'])) {
                $attribs = ArrayHelper::toString($options['list.attr']);
            } else {
                $attribs = $options['list.attr'];
            }

            if ($attribs !== '') {
                $attribs = ' ' . $attribs;
            }
        }

        $id = $options['id'] !== false ? $options['id'] : $name;
        $id = str_replace(['[', ']', ' '], '', $id);

        // If the selectbox contains "form-select-color-state" then load the JS file
        if (strpos($attribs, 'form-select-color-state') !== false) {
            Factory::getDocument()->getWebAssetManager()
                ->registerScript(
                    'webcomponent.select-colour-es5',
                    'system/fields/select-colour-es5.min.js',
                    ['dependencies' => ['wcpolyfill']],
                    ['defer'        => true, 'nomodule' => true]
                )
                ->registerAndUseScript(
                    'webcomponent.select-colour',
                    'system/fields/select-colour.min.js',
                    ['dependencies' => ['webcomponent.select-colour-es5']],
                    ['type'         => 'module']
                );
        }

        $baseIndent = str_repeat($options['format.indent'], $options['format.depth']++);
        $html       = $baseIndent . '<select' . ($id !== '' ? ' id="' . $id . '"' : '') . ' name="' . $name . '"' . $attribs . '>' . $options['format.eol']
        . static::customOptions($data, $options) . $baseIndent . '</select>' . $options['format.eol'];

        return $html;
    }

    /**
     * Generates the option tags for an HTML select list (with no select tag
     * surrounding the options).
     *
     * @param   array    $arr        An array of objects, arrays, or values.
     * @param   mixed    $optKey     If a string, this is the value of the options
     * @param   string   $optText    The name of the object variable for the option text.
     * @param   mixed    $selected   The key that is selected (accepts an array or a string)
     * @param   bool  $translate  Translate the option values.
     *
     * @return  string  HTML for the select list
     *
     * @since   1.5
     */
    public static function customOptions($arr, $optKey = 'value', $optText = 'text', $selected = null, $translate = false)
    {
        $options = array_merge(
            HTMLHelper::$formatOptions,
            static::$optionDefaults['option'],
            ['format.depth' => 0, 'groups' => true, 'list.select' => null, 'list.translate' => false]
        );

        if (is_array($optKey)) {
            // Set default options and overwrite with anything passed in
            $options = array_merge($options, $optKey);
        } else {
            // Get options from the parameters
            $options['option.key']     = $optKey;
            $options['option.text']    = $optText;
            $options['list.select']    = $selected;
            $options['list.translate'] = $translate;
        }

        $html       = '';
        $baseIndent = str_repeat($options['format.indent'], $options['format.depth']);

        foreach ($arr as $elementKey => &$element) {
            $attr  = '';
            $extra = '';
            $label = '';
            $id    = '';
            $image = '';

            if (is_array($element)) {
                $key  = $options['option.key'] === null ? $elementKey : $element[$options['option.key']];
                $text = $element[$options['option.text']];

                if (isset($element[$options['option.attr']])) {
                    $attr = $element[$options['option.attr']];
                }

                if (isset($element[$options['option.id']])) {
                    $id = $element[$options['option.id']];
                }

                if (isset($element[$options['option.label']])) {
                    $label = $element[$options['option.label']];
                }

                if (isset($element[$options['option.disable']]) && $element[$options['option.disable']]) {
                    $extra .= ' disabled="disabled"';
                }

                if (isset($element['image']) && !empty($element['image'])) {
                    $image = Uri::root() . $element['image'];
                }
            } elseif (is_object($element)) {
                $key  = $options['option.key'] === null ? $elementKey : $element->{$options['option.key']};
                $text = $element->{$options['option.text']};

                if (isset($element->{$options['option.attr']})) {
                    $attr = $element->{$options['option.attr']};
                }

                if (isset($element->{$options['option.id']})) {
                    $id = $element->{$options['option.id']};
                }

                if (isset($element->{$options['option.label']})) {
                    $label = $element->{$options['option.label']};
                }

                if (isset($element->{$options['option.disable']}) && $element->{$options['option.disable']}) {
                    $extra .= ' disabled="disabled"';
                }

                if (isset($element->{$options['option.class']}) && $element->{$options['option.class']}) {
                    $extra .= ' class="' . $element->{$options['option.class']} . '"';
                }

                if (isset($element->{$options['option.onclick']}) && $element->{$options['option.onclick']}) {
                    $extra .= ' onclick="' . $element->{$options['option.onclick']} . '"';
                }

                if (isset($element->image) && !empty($element->image)) {
                    $image = Uri::root() . $element->image;
                }
            } else {
                // This is a simple associative array
                $key  = $elementKey;
                $text = $element;
            }
            /*
             * The use of options that contain optgroup HTML elements was
             * somewhat hacked for J1.5. J1.6 introduces the grouplist() method
             * to handle this better. The old solution is retained through the
             * "groups" option, which defaults true in J1.6, but should be
             * deprecated at some point in the future.
             */

            $key = (string) $key;

            if ($key === '<OPTGROUP>' && $options['groups']) {
                $html .= $baseIndent . '<optgroup label="' . ($options['list.translate'] ? Text::_($text) : $text) . '">' . $options['format.eol'];
                $baseIndent = str_repeat($options['format.indent'], ++$options['format.depth']);
            } elseif ($key === '</OPTGROUP>' && $options['groups']) {
                $baseIndent = str_repeat($options['format.indent'], --$options['format.depth']);
                $html .= $baseIndent . '</optgroup>' . $options['format.eol'];
            } else {
                // If no string after hyphen - take hyphen out
                $splitText = explode(' - ', $text, 2);
                $text      = $splitText[0];

                if (isset($splitText[1]) && $splitText[1] !== '' && !preg_match('/^[\s]+$/', $splitText[1])) {
                    $text .= ' - ' . $splitText[1];
                }

                if (!empty($label) && $options['list.translate']) {
                    $label = Text::_($label);
                }

                if ($options['option.label.toHtml']) {
                    $label = htmlentities($label);
                }

                if (is_array($attr)) {
                    $attr = ArrayHelper::toString($attr);
                } else {
                    $attr = trim($attr);
                }

                $extra = ($id ? ' id="' . $id . '"' : '') . ($label ? ' label="' . $label . '"' : '') . ($attr ? ' ' . $attr : '') . $extra;

                if (is_array($options['list.select'])) {
                    foreach ($options['list.select'] as $val) {
                        $key2 = is_object($val) ? $val->{$options['option.key']} : $val;

                        if ($key == $key2) {
                            $extra .= ' selected="selected"';
                            break;
                        }
                    }
                } elseif ((string) $key === (string) $options['list.select']) {
                    $extra .= ' selected="selected"';
                }

                if ($options['list.translate']) {
                    $text = Text::_($text);
                }

                // Generate the option, encoding as required
                $html .= $baseIndent . '<option value="' . ($options['option.key.toHtml'] ? htmlspecialchars($key, ENT_COMPAT, 'UTF-8') : $key) . '"'
                    . $extra;
                if (!empty($image)) {
                    $html .= 'data-img="' . $image . '"';
                }
                $html .= '>';
                $html .= $options['option.text.toHtml'] ? htmlentities(html_entity_decode($text, ENT_COMPAT, 'UTF-8'), ENT_COMPAT, 'UTF-8') : $text;
                $html .= '</option>' . $options['format.eol'];
            }
        }

        return $html;
    }

    /**
     * Function to get the first image of Images object
     *
     * @param $images
     * @return string
     */
    public static function getFirstImage($images)
    {
        if (empty($images)) {
            return '';
        }

        foreach ($images as $image) {
            return $image->product_image;
        }
    }

    /**
     * Check if a column has an alias.
     *
     * @param string            $columnString  The column name with or without an alias.
     * @return array|bool
     * @since 1.0.0
     */
    public static function hasAlias($columnString)
    {
        // Split the string by '.'
        $parts = explode('.', $columnString);

        if (count($parts) === 2) {
            return $parts;
        }

        return;
    }

    /**
     * Function to set flash message in session
     *
     * @param string $message
     * @param string $type
     * @return void
     */
    public static function setFlash($message, $type = 'success')
    {
        /**
         * @var CMSApplication
         */
        $app     = Factory::getApplication();
        $session = $app->getSession();
        $data    = (object) [
            'type'    => $type,
            'message' => $message,
        ];
        $session->set('com_easystore.flash_message', $data);
    }

    /**
     * Performs various checks if it is allowed to save the content.
     *
     * @param array $file File
     *
     * @return object
     */
    public static function isValid($file): object
    {
        if (empty($file)) {
            return (object) ['status' => false, 'message' => null];
        }

        $input               = Factory::getApplication()->input;
        $errorMsg            = new \stdClass();
        $helper              = new MediaHelper();
        $contentLength       = $input->server->getInt('CONTENT_LENGTH');
        $params              = ComponentHelper::getParams('com_media');
        $imageExtensions     = $params->get('image_extensions', '');
        $videoExtensions     = $params->get('video_extensions', '');
        $paramsUploadMaxsize = $params->get('upload_maxsize', 0) * 1024 * 1024;
        $uploadMaxFilesize   = $helper->toBytes(ini_get('upload_max_filesize'));
        $postMaxSize         = $helper->toBytes(ini_get('post_max_size'));
        $memoryLimit         = $helper->toBytes(ini_get('memory_limit'));

        $imageExtensions = explode(',', $imageExtensions);
        $videoExtensions = explode(',', $videoExtensions);

        $allowedExtensions = array_merge($imageExtensions, $videoExtensions);

        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            $errorMsg->status  = false;
            $errorMsg->message = Text::_('COM_EASYSTORE_PRODUCT_IMAGE_UPLOAD_ERR_NO_FILE');

            return $errorMsg;
        }

        if (($postMaxSize > 0 && $contentLength > $postMaxSize) || ($memoryLimit !== '-1' && $contentLength > $memoryLimit)) {
            $errorMsg->status  = false;
            $errorMsg->name    = $file['name'];
            $errorMsg->message = Text::_('COM_EASYSTORE_PRODUCT_IMAGE_TOTAL_SIZE_EXCEEDS');

            return $errorMsg;
        }

        if (($file['error'] === UPLOAD_ERR_INI_SIZE) || ($paramsUploadMaxsize > 0 && $file['size'] > $paramsUploadMaxsize) || ($uploadMaxFilesize > 0 && $file['size'] > $uploadMaxFilesize)) {
            $errorMsg->status  = false;
            $errorMsg->name    = $file['name'];
            $errorMsg->message = Text::_('COM_EASYSTORE_PRODUCT_IMAGE_LARGE');

            return $errorMsg;
        }

        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedExtensions)) {
            $errorMsg->status  = false;
            $errorMsg->name    = $file['name'];
            $errorMsg->message = Text::_('COM_EASYSTORE_PRODUCT_IMAGE_NOT_ALLOWED_EXTENSIONS');

            return $errorMsg;
        }

        return (object) ['status' => true, 'message' => null];
    }

    /**
     * Function to generate Sort By query string
     *
     * @param string $param
     * @return object
     */
    public static function sortBy(string $param)
    {
        $firstCharacter = substr($param, 0, 1);
        $orderDirection = "ASC";

        if ($firstCharacter === '-') {
            $param          = substr($param, 1);
            $orderDirection = "DESC";
        }

        $ordering = (object) [
            'field'     => $param,
            'direction' => $orderDirection,
        ];

        return $ordering;
    }

    /**
     * Function to return the 1st element of an array
     *
     * @param array $array
     * @return mixed
     */
    public static function first(array $array)
    {
        return !empty($array) ? $array[0] : null;
    }

    /**
     * Function to get Order prices by Id
     *
     * @param int $id   Order Id
     * @param object $item  Order object
     * @return object
     */
    public static function getOrderPrices(int $id, object $item)
    {
        $orm = new EasyStoreDatabaseOrm();

        $products = $orm->setColumns(['product_id', 'variant_id', 'discount_type', 'discount_value', 'quantity', 'price'])
            ->hasMany($id, '#__easystore_order_product_map', 'order_id')
            ->loadObjectList();

        $sub_total = 0.00;

        if (!empty($products)) {
            foreach ($products as $product) {
                $discountedPrice = 0.00;

                if ($product->discount_type === 'percent') {
                    $discountedPrice = (float) $product->price - ((float) $product->price * (float) $product->discount_value / 100);
                } else {
                    $discountedPrice = (float) $product->price - (float) $product->discount_value;
                }

                $sub_total += $discountedPrice * $product->quantity;
            }
        }

        $item->shipping = !empty($item->shipping) ? \json_decode($item->shipping) : null;
        $shipping_cost  = !empty($item->shipping->rate) ? (float) $item->shipping->rate : 0;

        if ($item->discount_type === 'percent') {
            $item->discount       = $sub_total * (float) $item->discount_value / 100;
            $totalDiscountedPrice = $sub_total - $item->discount + $shipping_cost;
        } else {
            $item->discount       = (float) $item->discount_value;
            $totalDiscountedPrice = $sub_total - $item->discount + $shipping_cost;
        }

        $price = (object) [
            'sub_total'     => (float) $sub_total,
            'discount'      => (float) $item->discount,
            'shipping_cost' => $shipping_cost,
            'total'         => $totalDiscountedPrice,
        ];

        return $price;
    }

    /**
     * Function to get Order calculated fields by Id
     *
     * This function retrieves an order by its ID and calculates various amounts associated with the order, including
     * the sub-total, tax, discount, coupon amount, shipping cost, and net amount. It formats these amounts both with
     * and without currency symbols.
     *
     * @param int  $id             Order Id
     * @param bool $fromCheckout   Set value to true if it is called from checkout page
     * @since  1.2.0
     *
     * @return object   An object containing the calculated amounts for the order
     *                  - sub_total: The total price of the products before tax and discounts
     *                  - tax_amount: The total tax amount
     *                  - sub_total_after_tax: The sub-total amount plus tax
     *                  - coupon_amount: The total coupon discount amount
     *                  - order_discount: The total order discount amount
     *                  - shipping_cost: The total shipping cost
     *                  - net_amount: The final net amount after all adjustments
     *                  - sub_total_with_currency: The sub-total formatted with currency
     *                  - tax_amount_with_currency: The tax amount formatted with currency
     *                  - sub_total_after_tax_with_currency: The sub-total after tax formatted with currency
     *                  - coupon_amount_with_currency: The coupon amount formatted with currency
     *                  - order_discount_with_currency: The order discount formatted with currency
     *                  - shipping_cost_with_currency: The shipping cost formatted with currency
     *                  - net_amount_with_currency: The net amount formatted with currency
     */
    public static function getOrderCalculatedAmounts(int $id, bool $fromCheckout = false)
    {
        $orm = new EasyStoreDatabaseOrm();

        $order = $orm->get("#__easystore_orders", "id", $id)->loadObject();


        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select(['pm.*', 'p.catid', 'p.has_variants'])
            ->from($db->quoteName('#__easystore_order_product_map', 'pm'))
            ->join('LEFT', $db->quoteName('#__easystore_products', 'p') . ' ON (' . $db->quoteName('pm.product_id') . ' = ' . $db->quoteName('p.id') . ')')
            ->where($db->quoteName('pm.order_id') . ' = ' . $id);

        $db->setQuery($query);

        $products = $db->loadObjectList();

        $sub_total = 0.00;

        if (!empty($products)) {
            foreach ($products as $product) {
                $updatedPrice = 0.00;

                $product->item_price       = $product->price;
                $product->discounted_price = self::calculateDiscountedPrice($product->discount_type, $product->discount_value, $product->price);

                $updatedPrice = ($product->discount_value > 0) ? $product->discounted_price : $product->price;

                $product->total_price = $updatedPrice * $product->quantity;

                $sub_total += $product->total_price;

                $product->sku_id = $product->variant_id;
            }
        }

        $order->shipping = !empty($order->shipping) ? \json_decode($order->shipping) : null;
        $shippingCost    = !empty($order->shipping->rate) ? (float) $order->shipping->rate : 0;

        if ($order->discount_type === 'percent') {
            $order->discount = $sub_total * (float) $order->discount_value / 100;
        } else {
            $order->discount = (float) $order->discount_value;
        }

        $order->sale_tax = (float) $order->sale_tax;

        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        /** @var ComponentInterface $component */
        $component = $app->bootComponent('com_easystore');
        /** @var CouponModel $couponModel */
        $couponModel         = $component->getMVCFactory()->createModel('Coupon', 'Site');

        $orderSummary        = (object) [
            'sub_total'     => $sub_total,
            'items'         => $products,
            'shipping_cost' => $shippingCost,
        ];

        $couponAmountDetails = $order->coupon_code ? $couponModel->getCouponAmount($order, $orderSummary, $fromCheckout) : 0.00;
        $couponAmount = isset($couponAmountDetails->amount) ? $couponAmountDetails->amount : 0.00;
        $netAmount    = $sub_total + $order->sale_tax - $couponAmount - $order->discount + $shippingCost;

        $response = (object) [
            'sub_total'                         => $sub_total,
            'tax_amount'                        => $order->sale_tax,
            'sub_total_after_tax'               => $sub_total + $order->sale_tax,
            'coupon_amount'                     => $couponAmount,
            'order_discount'                    => $order->discount,
            'shipping_cost'                     => $shippingCost,
            'net_amount'                        => $netAmount,
            'sub_total_with_currency'           => self::formatCurrency($sub_total),
            'tax_amount_with_currency'          => self::formatCurrency($order->sale_tax),
            'sub_total_after_tax_with_currency' => self::formatCurrency($sub_total + $order->sale_tax),
            'coupon_amount_with_currency'       => self::formatCurrency($couponAmount),
            'order_discount_with_currency'      => self::formatCurrency($order->discount),
            'shipping_cost_with_currency'       => self::formatCurrency($shippingCost),
            'net_amount_with_currency'          => self::formatCurrency($netAmount),
        ];

        return $response;
    }

    /**
     * Function to type cast from string/int to int, float, bool by checking types
     *
     * @param mixed     $response   Object or Array of object
     * @param array     $types      List of types to type cast i.e ['price' => 'float', 'is_gift_card' => 'boolean']
     * @return mixed
     */
    public static function typeCorrection($response, array $types)
    {
        $keys = array_keys($types);

        if (!is_array($response)) {
            foreach ($response as $key => $value) {
                if (!\in_array($key, $keys) || is_null($value) || $value === '') {
                    continue;
                }

                $response->$key = self::typeCast($value, $types[$key]);
            }

            return $response;
        }

        foreach ($response as &$object) {
            foreach ($object as $key => $value) {
                if (!\in_array($key, $keys) || is_null($value) || $value === '') {
                    continue;
                }

                $object->$key = self::typeCast($value, $types[$key]);
            }
        }

        return $response;
    }

    private static function typeCast($value, string $type)
    {
        switch ($type) {
            case 'integer':
                return (int) $value;
                break;

            case 'float':
                return (float) $value;
                break;

            case 'boolean':
                return (bool) $value;
                break;

            default:
                return $value;
        }
    }

    /**
     * Undocumented function
     *
     * @param $file
     * @param string $folderPath
     * @param string $fileName  The name of the uploaded file
     * @return mixed
     */
    public static function uploadFile($file, string $folderPath, ?string $fileName = null)
    {
        if (!EasyStoreMediaHelper::isActionableFolder($folderPath)) {
            return false;
        }

        $imageObject = (object) [
            'name' => null,
            'src'  => null,
        ];

        if (!empty($file)) {
            $folder    = $folderPath;
            $mediaFile = preg_replace('@\s+@', "-", File::makeSafe(basename(strtolower($file['name']))));
            $baseName  = is_null($fileName) ? File::stripExt($mediaFile) : $fileName;
            $ext       = pathinfo($mediaFile, PATHINFO_EXTENSION);
            $mediaName = $baseName . '.' . $ext;
            $dest      = JPATH_ROOT . '/' . Path::clean($folder) . '/' . $mediaName;
            $src       = Path::clean($folder) . '/' . $mediaName;

            if (File::upload($file['tmp_name'], $dest, false, true)) {
                $imageObject->name = $mediaName;
                $imageObject->src  = $src;
            }
        }

        return $imageObject;
    }

    public static function generateUuidV4()
    {
        $data = openssl_random_pseudo_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0F | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3F | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function stringToNumber(string $value)
    {
        if (preg_match('/[0-9]+(\.[0-9]+)?/', $value, $matches)) {
            return (float) $matches[0];
        }

        return (float) $value;
    }

    public static function parseJson($value)
    {
        if (!empty($value) && is_string($value)) {
            return json_decode($value);
        }

        return $value;
    }

    /**
     * Function to get phone code by Phone number
     *
     * @param  string $phone
     * @return string
     * @since  1.0.0
     */
    public static function detectPhoneCodeByPhoneNumber($phone)
    {
        $path            = JPATH_ROOT . '/media/com_easystore/data/countries.json';
        $countryJsonData = file_get_contents($path);

        $countries = !empty($countryJsonData) && is_string($countryJsonData) ? json_decode($countryJsonData) : [];
        $phone     = str_replace(['+', '-'], '', $phone);
        $result    = '';

        foreach ($countries as $country) {
            $phoneCode = str_replace(['+', '-'], '', $country->phone_code);

            if (strpos($phone, $phoneCode) === 0) {
                $result = $country->phone_code;
                break;
            }
        }

        return $result;
    }

    /**
     * Generates an HTML element representing a circular image placeholder with initials.
     *
     * @param string $name            The name of the user.
     * @param string $className       The CSS class name to apply to the generated div element.
     * @since 1.0.0
     */
    public static function createImagePlaceHolder($name, $className)
    {
        $nameChunks             = explode(' ', $name);
        $firstLetterOfFirstName = !empty($nameChunks[0]) ? strtoupper(substr($nameChunks[0], 0, 1)) : '';
        $firstLetterOfLastName  = !empty($nameChunks[1]) ? strtoupper(substr($nameChunks[1], 0, 1)) : '';
        $circleText             = $firstLetterOfFirstName . $firstLetterOfLastName;

        $html = "<div class='$className'>$circleText</div>";

        echo $html;
    }

    /**
     * Get the Joomla User information by Easystore user ID.
     *
     * @param int $customerId
     * @return object|null
     */
    public static function getUserByCustomerId($customerId)
    {
        if (empty($customerId)) {
            return null;
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select(['eu.*', 'u.name', 'u.email'])
            ->from($db->quoteName('#__users', 'u'))
            ->join('LEFT', $db->quoteName('#__easystore_users', 'eu') . ' ON (' . $db->quoteName('eu.user_id') . ' = ' . $db->quoteName('u.id') . ')')
            ->where($db->quoteName('eu.id') . ' = ' . (int) $customerId);

        $db->setQuery($query);

        try {
            return $db->loadObject() ?? null;
        } catch (Throwable $error) {
            return null;
        }
    }

    /**
     * Check if a filetype is matched with the provided valid file formats
     *
     * @param string $filePath
     * @param array  $validFileFormats
     *
     * @return bool
     */
    public static function validateFileType($filePath = '', $validFileFormats = ['mp4', 'avi', 'mov', 'mkv', 'flv', 'wmv', 'webm'])
    {
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

        if (in_array($fileExtension, $validFileFormats)) {
            return true;
        }

        return false;
    }

    /**
     * Function to get the default currency values if not set on settings
     * @param   mixed   $type   Can be null|'code'|'symbol'
     * @return  string
     */
    public static function getDefaultCurrency($type = null)
    {
        $code   = 'USD';
        $symbol = '$';

        if ($type === 'code') {
            return $code;
        } elseif ($type === 'symbol') {
            return $symbol;
        } else {
            return $code . ':' . $symbol;
        }
    }

    /**
     * Function to get if Component is Installed & Enabled
     *
     * @param string $componentName
     * @return object
     */
    public static function isComponentInstalled($componentName)
    {
        $response          = new \stdClass();
        $response->status  = false;
        $response->message = '';

        // Check if the component is installed.
        if (ComponentHelper::isInstalled($componentName)) {
            // Check if the component is enabled.
            if (ComponentHelper::isEnabled($componentName)) {
                $response->status  = true;
                $response->message = 'Component "' . $componentName . '" installed and enabled';
            } else {
                $response->status  = false;
                $response->message = 'Component "' . $componentName . '" installed but not enabled';
            }
        } else {
            $response->status  = false;
            $response->message = 'Component "' . $componentName . '" not installed';
        }

        return $response;
    }

    /**
     * Function to get Country Id & State Id from JSON
     *
     * @param string $countryName
     * @param string $stateName
     * @return object
     */
    public static function getCountryStateIdFromJson($countryName, $stateName)
    {
        $path      = JPATH_ROOT . '/media/com_easystore/data/countries.json';
        $jsonData  = file_get_contents($path);
        $data      = !empty($jsonData) && is_string($jsonData) ? json_decode($jsonData) : [];
        $countryId = '';
        $stateId   = '';

        foreach ($data as $value) {
            if ($value->name == $countryName) {
                $countryId = $value->numeric_code;

                foreach ($value->states as $state) {
                    if ($state->name == $stateName) {
                        $stateId = $state->id;
                        break;
                    }
                }
                break;
            }
        }

        return (object) ['country' => $countryId, 'state' => $stateId];
    }

    /**
     * Function to check if the alias is unique in the given table
     * @param string $alias
     * @param string $table
     * @param string $key
     * @return string
     */
    public static function makeAliasUnique($alias, $table, $key = 'alias')
    {
        $isExists = EasyStoreDatabaseOrm::get($table, $key, $alias)->loadObject()->id ?? false;

        if ($isExists) {
            $alias = StringHelper::increment($alias, 'dash');
        }

        return $alias;
    }

    /**
     * EasyStore Current Build Version
     * @return string
     */
    public static function getCurrentVersion()
    {
        $extensionInfo = ExtensionHelper::getExtensionRecord('com_easystore', 'component');
        $cache         = new Registry($extensionInfo->manifest_cache);
        return 'v' . $cache->get('version');
    }

    /**
     * Retrieves the country and state from the settings or customer shipping address.
     *
     * @return array An array containing 'country' and 'state'.
     */
    public static function getCountryAndState()
    {
        // Retrieve general settings
        $settings = SettingsHelper::getSettings();
        $country = $settings->get('general.country', null);
        $state = $settings->get('general.state', null);

        // Get the current customer by user ID
        $customer = HelperEasyStoreHelper::getCustomerByUserId(Factory::getApplication()->getIdentity()->id);

        // Check if the customer has a shipping address and decode if needed
        if (!empty($customer) && !empty($customer->shipping_address)) {
            $shippingAddress = is_string($customer->shipping_address) ? json_decode($customer->shipping_address) : $customer->shipping_address;

            // If shipping address exists, override the country and state from shipping address
            if (!empty($shippingAddress)) {
                $country = $shippingAddress->country ?? $country;
                $state  = $shippingAddress->state ?? $state;
            }
        }

        // Return country and state as an array
        return [ $country, $state ];
    }

    /**
     * Retrieves payment information for a specific manual payment plugin in the EasyStore.
     *
     * @param  string $plugin The name of the plugin.
     * @return object         An object containing additional information and payment instructions for the manual payment plugin.
     * @since  1.7.0
     */
    public static function getManualPaymentInfo($plugin)
    {
        $pluginInfo            = ExtensionHelper::getExtensionRecord($plugin, 'plugin', 0, 'easystore');
        $params                = json_decode($pluginInfo->params);
        $additionalInformation = isset($params->additional_information) ? $params->additional_information : null;
        $paymentInstruction    = isset($params->payment_instruction) ? $params->payment_instruction : null;

        return (object) [
            'additional_information' => $additionalInformation,
            'payment_instruction'    => $paymentInstruction,
        ];
    }

    /**
     * Function to get Country alpha code by country numeric code
     *
     * @param string $numeric_code
     * @return object
     */
    public static function getCountryAlphaCode($numeric_code)
    {
        if (empty($numeric_code)) {
            return (object) ['code' => ''];
        }

        $path        = JPATH_ROOT . '/media/com_easystore/data/countries.json';
        $jsonData    = file_get_contents($path);
        $countries   = !empty($jsonData) && is_string($jsonData) ? json_decode($jsonData) : [];
        $countryCode = new \stdClass();

        foreach ($countries as $country) {
            if ($country->numeric_code === $numeric_code) {
                $countryCode->code = $country->alpha_2;
                break;
            }
        }

        return $countryCode;
    }
}
