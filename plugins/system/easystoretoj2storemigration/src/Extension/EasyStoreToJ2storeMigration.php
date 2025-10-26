<?php

/**
 * @package     EasyStore.Plugin
 * @subpackage  System.easystoretoj2storemigration
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Plugin\System\EasyStoreToJ2storeMigration\Extension;

use Joomla\CMS\Factory;
use Joomla\Event\Event;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Path;
use Joomla\CMS\Access\Access;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Helper\MediaHelper;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Session\SessionInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\User\User as JoomlaUser;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Application\CMSApplication;
use JoomShaper\Component\EasyStore\Administrator\Traits\Migration;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Plugin\MigrationPlugin;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;
use JoomShaper\Component\EasyStore\Administrator\Controller\MigrationController;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


final class EasyStoreToJ2storeMigration extends MigrationPlugin implements SubscriberInterface
{
    use Migration;

    private const CHUNK_SIZE = MigrationController::CHUNK_SIZE;

    /**
     * function for getSubscribedEvents : new Joomla 4 feature
     *
     * @return array
     *
     * @since   1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'migrateSettings'   => 'migrateSettings',
            'migrateCategories' => 'migrateCategories',
            'migrateTags'       => 'migrateTags',
            'migrateCoupons'    => 'migrateCoupons',
            'migrateProducts'   => 'migrateProducts',
            'migrateCustomers'  => 'migrateCustomers',
            'migrateOrders'     => 'migrateOrders',
        ];
    }

    /**
     * Function to migrate Settings data
     *
     * @param Event $event -- The event object that contains information about the payment.
     *
     * @return array
     */
    public function migrateSettings(Event $event)
    {
        try {
            $arguments     = $event->getArguments();
            $migrationData = $arguments['subject'] ?: new \stdClass();

            if ($migrationData->state !== 'settings' && $migrationData->migrateFrom !== 'j2store') {
                $response = [
                    'status'  => 'failed',
                    'message' => 'Something went wrong',
                ];

                $event->setArgument('result', $response);
                exit;
            }

            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);

            $query->select('js.*');
            $query->from($db->quoteName('#__j2store_configurations', 'js'));

            $db->setQuery($query);
            $j2storeSettings = $db->loadObjectList();

            $query->clear();

            $query->select('es.*');
            $query->from($db->quoteName('#__easystore_settings', 'es'));
            $query->where($db->quoteName('key') . ' = ' . $db->quote('general'));
            $query->orWhere([$db->quoteName('key') . ' = ' . $db->quote('products')]);

            $db->setQuery($query);
            $easyStoreSettings = $db->loadObjectList();

            $generalSettings  = [];
            $productsSettings = [];

            $storeName      = '';
            $storeEmail     = '';
            $addressLineOne = '';
            $addressLineTwo = '';
            $postcode       = '';
            $country        = '';
            $state          = 0;
            $city           = 0;
            $zoneId         = 0;
            $currency       = '';
            $weight         = '';
            $dimension      = '';

            foreach ($j2storeSettings as $j2settings) {
                if ($j2settings->config_meta_key == 'store_name') {
                    $storeName = $j2settings->config_meta_value;
                } elseif ($j2settings->config_meta_key == 'admin_email') {
                    $emails     = explode(',', $j2settings->config_meta_value);
                    $storeEmail = trim($emails[0]);
                } elseif ($j2settings->config_meta_key == 'store_address_1') {
                    $addressLineOne = $j2settings->config_meta_value;
                } elseif ($j2settings->config_meta_key == 'store_address_2') {
                    $addressLineTwo = $j2settings->config_meta_value;
                } elseif ($j2settings->config_meta_key == 'store_city') {
                    $city = $j2settings->config_meta_value;
                } elseif ($j2settings->config_meta_key == 'store_zip') {
                    $postcode = $j2settings->config_meta_value;
                } elseif ($j2settings->config_meta_key == 'country_id') {
                    $countryId = $j2settings->config_meta_value;
                } elseif ($j2settings->config_meta_key == 'zone_id') {
                    $zoneId = $j2settings->config_meta_value;
                } elseif ($j2settings->config_meta_key == 'config_currency') {
                    $currencyJsonFilePath = JPATH_ROOT . '/media/com_easystore/data/currencies.json';
                    $currency             = "USD:US$";

                    if (file_exists($currencyJsonFilePath)) {
                        $currencies = json_decode(file_get_contents($currencyJsonFilePath));

                        foreach ($currencies as $currency) {
                            if ($currency->code == $j2settings->config_meta_value) {
                                $currency = $currency->code . ':' . $currency->symbol;
                                break;
                            }
                        }
                    }
                } elseif ($j2settings->config_meta_key == 'config_weight_class_id') {
                    $weightId = $j2settings->config_meta_value;
                } elseif ($j2settings->config_meta_key == 'config_length_class_id') {
                    $lengthId = $j2settings->config_meta_value;
                }
            }

            // get country name from j2store
            $countryData = EasyStoreDatabaseOrm::get('#__j2store_countries', 'j2store_country_id', $countryId, 'country_name')->loadObject();
            $countryName = $countryData->country_name ?? '';

            // get zone name from j2store
            $stateData = EasyStoreDatabaseOrm::get('#__j2store_zones', 'j2store_zone_id', $zoneId, 'zone_name')->loadObject();
            $stateName = $stateData->zone_name ?? '';

            // get weight name from j2store
            $weightData = EasyStoreDatabaseOrm::get('#__j2store_weights', 'j2store_weight_id', $weightId, 'weight_unit')->loadObject();
            $weight     = $weightData->weight_unit ?? '';

            // get length name from j2store
            $dimensionData = EasyStoreDatabaseOrm::get('#__j2store_lengths', 'j2store_length_id', $lengthId, 'length_unit')->loadObject();
            $dimension     = $dimensionData->length_unit ?? '';

            // get country state id
            $CountryCityId = EasyStoreHelper::getCountryStateIdFromJson($countryName, $stateName);
            $country       = $CountryCityId->country ?? '';
            $state         = $CountryCityId->state ?? '';

            foreach ($easyStoreSettings as $settings) {
                if ($settings->key === 'general') {
                    $generalSettings = json_decode($settings->value, true);
                } elseif ($settings->key === 'products') {
                    $productsSettings = json_decode($settings->value, true);
                }
            }

            $generalSettings['storeName']      = $storeName;
            $generalSettings['storeEmail']     = $storeEmail;
            $generalSettings['addressLineOne'] = $addressLineOne;
            $generalSettings['addressLineTwo'] = $addressLineTwo;
            $generalSettings['postcode']       = $postcode;
            $generalSettings['country']        = $country;
            $generalSettings['state']          = $state;
            $generalSettings['city']           = $city;
            $generalSettings['currency']       = $currency;

            $productsSettings['standardUnits']['weight']    = $weight;
            $productsSettings['standardUnits']['dimension'] = $dimension;

            $generalSettingsData = (object) [
                'key'   => 'general',
                'value' => json_encode($generalSettings),
            ];

            $productSettingsData = (object) [
                'key'   => 'products',
                'value' => json_encode($productsSettings),
            ];

            $this->updateSettings($generalSettingsData);
            $this->updateSettings($productSettingsData);

            $response = [
                'status'   => 'success',
                'message'  => 'done',
                'progress' => 5,
            ];
        } catch (\Throwable $th) {
            $response = [
                'status'  => 'failed',
                'message' => $th->getMessage()
            ];
        }

        $event->setArgument('result', $response);
    }

    /**
     * Function to migrate categories
     *
     * @param Event $event -- The event object that contains information about the payment.
     *
     * @return array
     */
    public function migrateCategories(Event $event)
    {
        try {
            $arguments     = $event->getArguments();
            $migrationData = $arguments['subject'] ?: new \stdClass();

            if ($migrationData->state !== 'categories' && $migrationData->migrateFrom !== 'j2store') {
                $response = [
                    'status'  => 'failed',
                    'message' => 'Something went wrong',
                ];

                $event->setArgument('result', $response);
                exit;
            }

            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);

            $query->select([
                $db->quoteName('id'),
                $db->quoteName('title'),
                $db->quoteName('alias'),
                $db->quoteName('description'),
                $db->quoteName('parent_id'),
                $db->quoteName('lft'),
                $db->quoteName('rgt'),
                $db->quoteName('level'),
                $db->quoteName('path'),
                $db->quoteName('published'),
                $db->quoteName('access'),
                $db->quoteName('language'),
                $db->quoteName('created_time'),
                $db->quoteName('created_user_id'),
                $db->quoteName('modified_time'),
                $db->quoteName('modified_user_id'),
            ])
                ->from($db->quoteName('#__categories'))
                ->where([$db->quoteName('title') . ' = ' . $db->quote('ROOT'), $db->quoteName('alias') . ' = ' . $db->quote('root'), $db->quoteName('level') . ' = 0'])
                ->orWhere([$db->quoteName('extension') . ' = ' . $db->quote('com_content')]);

            $db->setQuery($query);
            $categories = $db->loadObjectList();

            $this->deleteTableData('#__easystore_categories', true);

            $categoriesArray = [];
            $uniqueAliasList = [];

            foreach ($categories as $category) {
                if (in_array($category->alias, $uniqueAliasList)) {
                    $category->alias .= '-' . $category->id;
                } else {
                    $uniqueAliasList[] = $category->alias;
                }

                $category->created     = $this->dateValidate($category->created_time);
                $category->created_by  = $category->created_user_id;
                $category->modified    = $this->dateValidate($category->modified_time, $category->created_time);
                $category->modified_by = $category->modified_user_id;

                unset($category->created_time, $category->created_user_id, $category->modified_time, $category->modified_user_id);

                $categoriesArray[] = (array) $category;
            }

            EasyStoreDatabaseOrm::insertMultipleRecords('#__easystore_categories', $categoriesArray);

            $response = [
                'status'   => 'success',
                'message'  => 'done',
                'progress' => 10,
            ];
        } catch (\Throwable $th) {
            $response = [
                'status'  => 'failed',
                'message' => $th->getMessage()
            ];
        }

        $event->setArgument('result', $response);
    }

    /**
     * Function to migrate tags
     *
     * @param Event $event -- The event object that contains information about the payment.
     *
     * @return array
     */
    public function migrateTags(Event $event)
    {
        try {
            $arguments     = $event->getArguments();
            $migrationData = $arguments['subject'] ?: new \stdClass();

            if ($migrationData->state !== 'tags' && $migrationData->migrateFrom !== 'j2store') {
                $response = [
                    'status'  => 'failed',
                    'message' => 'Something went wrong',
                ];

                $event->setArgument('result', $response);
                exit;
            }

            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $query = $db->getQuery(true);
            $query->select([
                $db->quoteName('id'),
                $db->quoteName('title'),
                $db->quoteName('alias'),
                $db->quoteName('description'),
                $db->quoteName('published'),
                $db->quoteName('access'),
                $db->quoteName('language'),
                $db->quoteName('created_time'),
                $db->quoteName('created_user_id'),
                $db->quoteName('modified_time'),
                $db->quoteName('modified_user_id'),
            ])
                ->from($db->quoteName('#__tags'))
                ->where($db->quoteName('alias') . ' != ' . $db->quote('root'));

            $db->setQuery($query);
            $tags = $db->loadObjectList();

            $this->deleteTableData('#__easystore_tags', true);

            $tagsArray = [];

            foreach ($tags as $tag) {
                $tag->created     = $this->dateValidate($tag->created_time);
                $tag->created_by  = $tag->created_user_id;
                $tag->modified    = $this->dateValidate($tag->modified_time, $tag->created_time);
                $tag->modified_by = $tag->modified_user_id;

                unset($tag->created_time, $tag->created_user_id, $tag->modified_time, $tag->modified_user_id);

                $tagsArray[] = (array) $tag;
            }

            EasyStoreDatabaseOrm::insertMultipleRecords('#__easystore_tags', $tagsArray);

            $response = [
                'status'   => 'success',
                'message'  => 'done',
                'progress' => 15,
            ];
        } catch (\Throwable $th) {
            $response = [
                'status'  => 'failed',
                'message' => $th->getMessage()
            ];
        }

        $event->setArgument('result', $response);
    }

    /**
     * Function to migrate Coupons
     *
     * @param Event $event -- The event object that contains information about the payment.
     *
     * @return void
     */
    public function migrateCoupons(Event $event)
    {
        try {
            $arguments     = $event->getArguments();
            $migrationData = $arguments['subject'] ?: new \stdClass();

            if ($migrationData->state !== 'coupons' && $migrationData->migrateFrom !== 'j2store') {
                $response = [
                    'status'  => 'failed',
                    'message' => 'Something went wrong',
                ];

                $event->setArgument('result', $response);
                exit;
            }

            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);
            $query->select([
                $db->quoteName('j2store_coupon_id'),
                $db->quoteName('coupon_name'),
                $db->quoteName('coupon_code'),
                $db->quoteName('enabled'),
                $db->quoteName('value'),
                $db->quoteName('value_type'),
                $db->quoteName('valid_from'),
                $db->quoteName('valid_to'),
            ])
                ->from($db->quoteName('#__j2store_coupons'));

            $db->setQuery($query);
            $coupons = $db->loadObjectList();

            $this->deleteTableData('#__easystore_coupons');

            $couponsArray = [];

            $user      = Factory::getApplication()->getIdentity();
            $createdBy = $user->id;
            $created   = Factory::getDate('now')->toSql();

            foreach ($coupons as $coupon) {
                $couponData                 = new \stdClass();
                $couponData->id             = $coupon->j2store_coupon_id;
                $couponData->title          = $coupon->coupon_name;
                $couponData->alias          = OutputFilter::stringURLSafe($coupon->coupon_code);
                $couponData->code           = $coupon->coupon_code;
                $couponData->discount_type  = ($coupon->value_type == 'percentage_cart' || $coupon->value_type == 'percentage_product') ? 'percent' : 'amount';
                $couponData->discount_value = $coupon->value;
                $couponData->start_date     = $coupon->valid_from;
                $couponData->has_date       = !empty($coupon->valid_to) ? 1 : 0;
                $couponData->end_date       = $coupon->valid_to;
                $couponData->published      = $coupon->enabled;
                $couponData->created        = $created;
                $couponData->created_by     = $createdBy;
                $couponData->modified       = $created;

                $couponsArray[] = (array) $couponData;
            }

            EasyStoreDatabaseOrm::insertMultipleRecords('#__easystore_coupons', $couponsArray);

            $response = [
                'status'   => 'success',
                'message'  => 'done',
                'progress' => 20,
            ];
        } catch (\Throwable $th) {
            $response = [
                'status'  => 'failed',
                'message' => $th->getMessage()
            ];
        }

        $event->setArgument('result', $response);
    }

    /**
     * Function to migrate Products
     *
     * @param Event $event -- The event object that contains information about the payment.
     *
     * @return void
     */
    public function migrateProducts(Event $event)
    {
        try {
            $arguments     = $event->getArguments();
            $migrationData = $arguments['subject'] ?: new \stdClass();

            if ($migrationData->state !== 'products' && $migrationData->migrateFrom !== 'j2store') {
                $response = [
                    'status'  => 'failed',
                    'message' => 'Something went wrong',
                ];

                $event->setArgument('result', $response);
                exit;
            }

            $offset       = $migrationData->offset;
            $productCount = $migrationData->productCount;

            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);

            $query->select([
                'p.*',
                'c.title',
                'c.alias',
                'c.introtext',
                'c.catid',
                'c.state AS published',
            ])
                ->from($db->quoteName('#__j2store_products', 'p'))
                ->join('LEFT', $db->quoteName('#__content', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('p.product_source_id'))
                ->where($db->quoteName('c.title') . ' IS NOT NULL');

            if ($offset > 0) {
                $query->setLimit(self::CHUNK_SIZE, $offset);
            } else {
                $query->setLimit(self::CHUNK_SIZE);
            }

            $db->setQuery($query);
            $products = $db->loadObjectList();

            $db->transactionStart();

            try {
                if ($offset == 0) {
                    $this->deleteTableData('#__easystore_products', true);
                    $this->deleteTableData('#__easystore_product_options', true);
                    $this->deleteTableData('#__easystore_product_option_values', true);
                    $this->deleteTableData('#__easystore_product_skus', true);
                    $this->deleteTableData('#__easystore_media', true);
                }

                $this->insertProducts($products);

                $db->transactionCommit();

                if ($offset <= $productCount && $productCount > 0) {
                    if ($offset == 0) {
                        $progressPercent = 30;
                    } else {
                        $progressPercent = 40;
                    }

                    $response = [
                        'status'   => 'success',
                        'message'  => 'continue',
                        'progress' => $progressPercent,
                    ];
                } else {
                    $response = [
                        'status'   => 'success',
                        'message'  => 'done',
                        'progress' => 55,
                    ];
                }

                $event->setArgument('result', $response);
            } catch (\Exception $error) {
                $db->transactionRollback();
                $response = [
                    'status'  => 'failed',
                    'message' => $error->getMessage(),
                ];

                $event->setArgument('result', $response);
            }
        } catch (\Throwable $th) {
            $response = [
                'status'  => 'failed',
                'message' => $th->getMessage()
            ];

            $event->setArgument('result', $response);
        }
    }

    /**
     * Function to migrate Products
     *
     * @param Event $event -- The event object that contains information about the payment.
     *
     * @return void
     */
    public function migrateCustomers(Event $event)
    {
        try {
            $arguments     = $event->getArguments();
            $migrationData = $arguments['subject'] ?: new \stdClass();

            if ($migrationData->state !== 'customers' && $migrationData->migrateFrom !== 'j2store') {
                $response = [
                    'status'  => 'failed',
                    'message' => 'Something went wrong',
                ];

                $event->setArgument('result', $response);
                exit;
            }

            $offset        = $migrationData->offset;
            $customerCount = $migrationData->customerCount;

            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);

            $query->select('a.*');
            $query->select('CONCAT(a.first_name, " ", a.last_name) AS customer_name');
            $query->select('c.country_name');
            $query->select('z.zone_name');
            $query->from($db->quoteName('#__j2store_addresses', 'a'));
            $query->leftJoin($db->quoteName('#__j2store_countries', 'c') . ' ON a.country_id = c.j2store_country_id');
            $query->leftJoin($db->quoteName('#__j2store_zones', 'z') . ' ON a.zone_id = z.j2store_zone_id');
            $query->where($db->quoteName('a.email') . ' != ""');
            $query->where($db->quoteName('a.first_name') . ' != ""');
            $query->order($db->quoteName('a.user_id'), 'ASC');
            $query->order($db->quoteName('a.email'), 'ASC');

            if ($offset > 0) {
                $query->setLimit(self::CHUNK_SIZE, $offset);
            } else {
                $query->setLimit(self::CHUNK_SIZE);
            }

            $db->setQuery($query);
            $customers = $db->loadObjectList();

            $db->transactionStart();

            try {
                if ($offset == 0) {
                    $this->deleteTableData('#__easystore_users');
                    $this->deleteTableData('#__easystore_guests');
                }

                $this->insertCustomers($customers);

                $db->transactionCommit();

                if ($offset <= $customerCount && $customerCount > 0) {
                    if ($offset == 0) {
                        $progressPercent = 58;
                    } else {
                        $progressPercent = 60;
                    }

                    $response = [
                        'status'   => 'success',
                        'message'  => 'continue',
                        'progress' => $progressPercent,
                    ];
                } else {
                    $response = [
                        'status'   => 'success',
                        'message'  => 'done',
                        'progress' => 62,
                    ];
                }

                $event->setArgument('result', $response);
            } catch (\Exception $error) {
                $db->transactionRollback();
                $response = [
                    'status'  => 'failed',
                    'message' => $error->getMessage(),
                ];

                $event->setArgument('result', $response);
            }
        } catch (\Throwable $th) {
            $response = [
                'status'  => 'failed',
                'message' => $th->getMessage()
            ];

            $event->setArgument('result', $response);
        }
    }

    /**
     * Function to migrate Orders
     *
     * @param Event $event -- The event object that contains information about the payment.
     *
     * @return void
     */
    public function migrateOrders(Event $event)
    {
        try {
            $arguments     = $event->getArguments();
            $migrationData = $arguments['subject'] ?: new \stdClass();

            if ($migrationData->state !== 'orders' && $migrationData->migrateFrom !== 'j2store') {
                $response = [
                    'status'  => 'failed',
                    'message' => 'Something went wrong',
                ];

                $event->setArgument('result', $response);
                exit;
            }

            $offset     = $migrationData->offset;
            $orderCount = $migrationData->orderCount;

            $db    = Factory::getContainer()->get(DatabaseInterface::class);

            // Create subqueries
            $discountTypeSubQuery = $db->getQuery(true)
            ->select($db->quoteName('od.discount_type'))
            ->from($db->quoteName('#__j2store_orderdiscounts', 'od'))
            ->where($db->quoteName('o.order_id') . ' = ' . $db->quoteName('od.order_id'))
            ->order($db->quoteName('od.order_id') . ' DESC')
            ->setLimit(1);

            $couponIdSubQuery = $db->getQuery(true)
            ->select($db->quoteName('od.discount_entity_id'))
            ->from($db->quoteName('#__j2store_orderdiscounts', 'od'))
            ->where($db->quoteName('o.order_id') . ' = ' . $db->quoteName('od.order_id'))
            ->order($db->quoteName('od.order_id') . ' DESC')
            ->setLimit(1);

            $couponCodeSubQuery = $db->getQuery(true)
            ->select($db->quoteName('od.discount_code'))
            ->from($db->quoteName('#__j2store_orderdiscounts', 'od'))
            ->where($db->quoteName('o.order_id') . ' = ' . $db->quoteName('od.order_id'))
            ->order($db->quoteName('od.order_id') . ' DESC')
            ->setLimit(1);

            $couponTypeSubQuery = $db->getQuery(true)
            ->select($db->quoteName('od.discount_value_type'))
            ->from($db->quoteName('#__j2store_orderdiscounts', 'od'))
            ->where($db->quoteName('o.order_id') . ' = ' . $db->quoteName('od.order_id'))
            ->order($db->quoteName('od.order_id') . ' DESC')
            ->setLimit(1);

            $shippingNameSubQuery = $db->getQuery(true)
            ->select($db->quoteName('os.ordershipping_name'))
            ->from($db->quoteName('#__j2store_ordershippings', 'os'))
            ->where($db->quoteName('o.order_id') . ' = ' . $db->quoteName('os.order_id'))
            ->order($db->quoteName('os.ordershipping_name') . ' DESC')
            ->setLimit(1);

            // Main query
            $query = $db->getQuery(true);
            $query->select('o.*')
                ->select('(' . $discountTypeSubQuery . ') AS discount_type')
                ->select('(' . $couponIdSubQuery . ') AS coupon_id')
                ->select('(' . $couponCodeSubQuery . ') AS coupon_code')
                ->select('(' . $couponTypeSubQuery . ') AS coupon_type')
                ->select('(' . $shippingNameSubQuery . ') AS shipping_name')
                ->from($db->quoteName('#__j2store_orders', 'o'))
                ->where($db->quoteName('o.order_type') . ' = ' . $db->quote('normal'))
                ->order($db->quoteName('o.j2store_order_id') . ' ASC');

            if ($offset > 0) {
                $query->setLimit(self::CHUNK_SIZE, $offset);
            } else {
                $query->setLimit(self::CHUNK_SIZE);
            }

            $db->setQuery($query);
            $orders = $db->loadObjectList();

            $db->transactionStart();

            try {
                if ($offset == 0) {
                    $this->deleteTableData('#__easystore_orders', true);
                    $this->deleteTableData('#__easystore_order_activities', true);
                }

                $this->insertOrders($orders);

                $db->transactionCommit();

                if ($offset <= $orderCount && $orderCount > 0) {
                    if ($offset == 0) {
                        $progressPercent = 65;
                    } else {
                        $progressPercent = 78;
                    }

                    $response = [
                        'status'   => 'success',
                        'message'  => 'continue',
                        'progress' => $progressPercent,
                    ];
                } else {
                    $response = [
                        'status'   => 'success',
                        'message'  => 'done',
                        'progress' => 100,
                    ];

                    // Remove the user id map session data
                    /** @var CMSApplication $app */
                    $app  = Factory::getApplication();
                    $session = $app->getSession();
                    $session->clear('com_easystore.user_map');
                }

                $event->setArgument('result', $response);
            } catch (\Exception $error) {
                $db->transactionRollback();
                $response = [
                    'status'  => 'failed',
                    'message' => $error->getMessage(),
                ];

                $event->setArgument('result', $response);
            }
        } catch (\Throwable $th) {
            $response = [
                'status'  => 'failed',
                'message' => $th->getMessage()
            ];

            $event->setArgument('result', $response);
        }
    }

    /**
     * Function to update Settings values
     *
     * @param object  $data
     * @return void
     */
    private function updateSettings(object $data)
    {
        $settingsData        = new \stdClass();
        $settingsData->key   = $data->key;
        $settingsData->value = $data->value;

        try {
            EasyStoreDatabaseOrm::updateOrCreate('#__easystore_settings', $settingsData, 'key');
        } catch (\Exception $error) {
            $response = [
                'status'  => 'failed',
                'message' => $error->getMessage(),
            ];

            echo json_encode($response);
            exit;
        }
    }

    /**
     * Function to insert products by chunk
     *
     * @param array $products
     * @return void
     */
    private function insertProducts($products)
    {
        $orm = new EasyStoreDatabaseOrm();

        foreach ($products as $product) {
            $productData = [
                'id'                       => $product->j2store_product_id,
                'title'                    => $product->title,
                'alias'                    => $product->alias,
                'description'              => $product->introtext,
                'catid'                    => $product->catid,
                'weight'                   => '',
                'dimension'                => '',
                'regular_price'            => 0.00,
                'inventory_status'         => 0,
                'enable_out_of_stock_sell' => 0,
                'quantity'                 => 0,
                'sku'                      => '',
                'published'                => $product->published,
                'created'                  => $this->dateValidate($product->created_on),
                'created_by'               => $product->created_by,
                'modified'                 => $this->dateValidate($product->modified_on, $product->created_on),
                'modified_by'              => $product->modified_by ?? $product->created_by,
            ];

            $variantsData = [];

            if ($product->product_type == 'simple') {
                $variantData = $orm->setColumns(['j2store_variant_id', 'price', 'availability', 'manage_stock', 'allow_backorder', 'sku', 'weight', 'length', 'width', 'height', 'length_class_id', 'weight_class_id'])
                    ->hasOne($product->j2store_product_id, '#__j2store_variants', 'product_id')
                    ->whereInReference(($orm->quoteName('is_master') . ' = 1'))
                    ->loadObject();

                $quantityData = $orm->setColumns(['j2store_productquantity_id', 'quantity'])
                    ->hasOne($variantData->j2store_variant_id, '#__j2store_productquantities', 'variant_id')
                    ->loadObject();

                $unitData    = $this->convertUnits($variantData);
                $finalWeight = $unitData->finalWeight;
                $finalLength = $unitData->finalLength;
                $finalWidth  = $unitData->finalWidth;
                $finalHeight = $unitData->finalHeight;

                $productData['weight']                   = $finalWeight;
                $productData['dimension']                = $finalLength . 'x' . $finalWidth . 'x' . $finalHeight;
                $productData['regular_price']            = $variantData->price ?? 0.00;
                $productData['is_tracking_inventory']    = $variantData->manage_stock ?? 0;
                $productData['inventory_status']         = $variantData->availability ?? 0;
                $productData['enable_out_of_stock_sell'] = $variantData->allow_backorder ?? 0;
                $productData['quantity']                 = $quantityData->quantity ?? 0;
                $productData['sku']                      = $variantData->sku;
            } elseif ($product->product_type == 'variable' || $product->product_type == 'flexivariable') {
                $variantsData = $orm->setColumns(['j2store_variant_id', 'price', 'availability', 'manage_stock', 'allow_backorder', 'sku', 'weight', 'length', 'width', 'height', 'params', 'length_class_id', 'weight_class_id'])
                    ->hasMany($product->j2store_product_id, '#__j2store_variants', 'product_id')
                    ->whereInReference(($orm->quoteName('is_master') . ' != 1'))
                    ->loadObjectList();

                $stockManage = false;

                foreach ($variantsData as $variant) {
                    if ($variant->manage_stock) {
                        $stockManage = true;
                        break;
                    }
                }

                if (!empty($variantsData)) {
                    $productData['has_variants'] = 1;
                }

                $productData['is_tracking_inventory'] = $stockManage ? 1 : 0;
            }

            $productData = (object) $productData;

            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $db->insertObject('#__easystore_products', $productData);

            $this->migrateProductImages($productData);

            if ($product->product_type == 'variable' || $product->product_type == 'flexivariable') {
                $this->migrateProductOptionsAndValues($product->j2store_product_id);

                $db = Factory::getContainer()->get(DatabaseInterface::class);

                foreach ($variantsData as $variant) {
                    $variantQuantity = $orm->setColumns(['j2store_productquantity_id', 'quantity'])
                        ->hasOne($variant->j2store_variant_id, '#__j2store_productquantities', 'variant_id')
                        ->loadObject();

                    $combinationData = $this->getCombinationNameAndValue($variant->j2store_variant_id);

                    $params = json_decode($variant->params);

                    if ($params !== null && json_last_error() === JSON_ERROR_NONE) {
                        if (!empty($params->variant_main_image) && file_exists(Path::clean(JPATH_ROOT . '/' . $params->variant_main_image))) {
                            $mediaParams = ComponentHelper::getParams('com_media');
                            $path        = $mediaParams->get('file_path', 'images');
                            $newSrcPath  = $path . '/easystore/product-' . $productData->id;
                            $index       = time();

                            $this->copyImage($params->variant_main_image, $newSrcPath, $index);

                            $existingImages = EasyStoreDatabaseOrm::get('#__easystore_media', 'product_id', $productData->id)->loadObjectList();

                            $ext         = File::getExt($params->variant_main_image);
                            $newFilename = $index . '.' . $ext;
                            $newFilePath = $newSrcPath . '/' . $newFilename;

                            $variantMediaData = (object) [
                                'product_id'  => $productData->id,
                                'name'        => $newFilename,
                                'is_featured' => empty($existingImages) ? 1 : 0,
                                'src'         => $newFilePath,
                                'alt_text'    => '',
                                'ordering'    => 0,
                                'created'     => $this->dateValidate($productData->created),
                                'created_by'  => $productData->created_by,
                                'modified'    => $this->dateValidate($productData->modified, $productData->created),
                                'modified_by' => $productData->modified_by,
                            ];

                            $db->insertObject('#__easystore_media', $variantMediaData);
                            $imageId = $db->insertid();
                        }
                    }

                    $unitData    = $this->convertUnits($variant);
                    $finalWeight = $unitData->finalWeight;

                    $productSkuData = (object) [
                        'id'                => $variant->j2store_variant_id,
                        'product_id'        => $product->j2store_product_id,
                        'combination_name'  => $combinationData->combinationName,
                        'combination_value' => $combinationData->combinationValue,
                        'price'             => $variant->price ?? 0.00,
                        'inventory_status'  => $variant->availability ?? 0,
                        'inventory_amount'  => $variantQuantity->quantity ?? 0,
                        'sku'               => $variant->sku,
                        'weight'            => $finalWeight,
                        'visibility'        => 1,
                        'created'           => $this->dateValidate($product->created_on),
                        'created_by'        => $product->created_by,
                        'modified'          => $this->dateValidate($product->modified_on, $product->created_on),
                        'modified_by'       => $product->modified_by ?? $product->created_by,
                    ];

                    if (!empty($imageId)) {
                        $productSkuData->image_id = $imageId;
                        unset($imageId);
                    }

                    $db->insertObject('#__easystore_product_skus', $productSkuData);
                }
            }

            $tagsData = $orm->hasMany($product->product_source_id, '#__contentitem_tag_map', 'content_item_id')->loadObjectList();

            if (!empty($tagsData)) {
                $this->deleteTableData('#__easystore_product_tag_map', true);
                $this->insertProductTags($tagsData);
            }
        }
    }

    /**
     * Function to migrate product images
     *
     * @param object $product
     * @return void
     */
    private function migrateProductImages($product)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('*')
            ->from($db->quoteName('#__j2store_productimages', 'pi'))
            ->where($db->quoteName('pi.product_id') . ' = ' . $product->id);

        $db->setQuery($query);
        $imageData = $db->loadObject();

        $mediaData = [];

        if (!empty($imageData)) {
            $mediaParams = ComponentHelper::getParams('com_media');
            $path        = $mediaParams->get('file_path', 'images');
            $newSrcPath  = $path . '/easystore/product-' . $product->id;

            $mainImage = !empty($imageData->main_image) ? $imageData->main_image : $imageData->thumb_image;
            $mainImageAlt = !empty($imageData->main_image_alt) ? $imageData->main_image_alt : $imageData->thumb_image_alt;

            if (!empty($imageData->main_image)) {
                $mainImage = $this->removeHashAfterExtension($imageData->main_image);
            } elseif (!empty($imageData->thumb_image)) {
                $mainImage = $this->removeHashAfterExtension($imageData->thumb_image);
            }
            
            if (!empty($mainImage) && file_exists(Path::clean(JPATH_ROOT . '/' . $mainImage))) {
                $this->copyImage($mainImage, $newSrcPath, 1);
                $ext         = File::getExt($mainImage);
                $newFilename = '1.' . $ext;
                $newFilePath = $newSrcPath . '/' . $newFilename;

                $mediaData[] = [
                    'product_id'  => $product->id,
                    'name'        => $newFilename,
                    'is_featured' => 1,
                    'src'         => $newFilePath,
                    'alt_text'    => $mainImageAlt,
                    'ordering'    => 0,
                    'created'     => $this->dateValidate($product->created),
                    'created_by'  => $product->created_by,
                    'modified'    => $this->dateValidate($product->modified, $product->created),
                    'modified_by' => $product->modified_by,
                ];
            }

            if (!empty($imageData->additional_images)) {
                $additionalImages = json_decode($imageData->additional_images);

                if ($additionalImages !== null && json_last_error() === JSON_ERROR_NONE) {
                    $additionalImagesAlt = json_decode($imageData->additional_images_alt, true);
                    $index               = 2;

                    foreach ($additionalImages as $key => $image) {
                        if (!empty($image) && file_exists(Path::clean(JPATH_ROOT . '/' . $image))) {
                            $this->copyImage($image, $newSrcPath, $index);
                            $ext         = File::getExt($image);
                            $newFilename = $index . '.' . $ext;
                            $newFilePath = $newSrcPath . '/' . $newFilename;

                            $mediaData[] = [
                                'product_id'  => $product->id,
                                'name'        => $newFilename,
                                'is_featured' => 0,
                                'src'         => $newFilePath,
                                'alt_text'    => array_key_exists($key, $additionalImagesAlt ?? []) ? $additionalImagesAlt[$key] : '',
                                'ordering'    => $index - 1,
                                'created'     => $this->dateValidate($product->created),
                                'created_by'  => $product->created_by,
                                'modified'    => $this->dateValidate($product->modified, $product->created),
                                'modified_by' => $product->modified_by,
                            ];
                        }
                        $index++;
                    }
                }
            }

            if (!empty($mediaData)) {
                EasyStoreDatabaseOrm::insertMultipleRecords('#__easystore_media', $mediaData);
            }
        }
    }

    /**
     * Function to copy image
     *
     * @param string $srcPath
     * @param string $destPath
     * @param int $index
     * @return bool
     */
    private function copyImage($srcPath, $destPath, $index)
    {
        $srcPath     = MediaHelper::getCleanMediaFieldValue($srcPath);
        $ext         = File::getExt($srcPath);
        $newFilename = $index . '.' . $ext;

        $newFilePath = $destPath . '/' . $newFilename;

        if (file_exists(Path::clean(JPATH_ROOT . '/' . $srcPath))) {
            if (!is_dir(Path::clean(JPATH_ROOT . '/' . $destPath))) {
                Folder::create(Path::clean(JPATH_ROOT . '/' . $destPath));
            }

            return File::copy(Path::clean(JPATH_ROOT . '/' . $srcPath), Path::clean(JPATH_ROOT . '/' . $newFilePath));
        }
    }

    /**
     * Function to migrate product options and values
     *
     * @param int $productId
     * @return void
     */
    private function migrateProductOptionsAndValues($productId)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select([
            'po.*',
            'o.option_unique_name',
            'o.ordering AS option_ordering',
        ])
            ->from($db->quoteName('#__j2store_product_options', 'po'))
            ->join('LEFT', $db->quoteName('#__j2store_options', 'o'), $db->quoteName('o.j2store_option_id') . ' = ' . $db->quoteName('po.option_id'))
            ->where($db->quoteName('po.product_id') . ' = ' . $productId);

        $db->setQuery($query);
        $options = $db->loadObjectList();

        foreach ($options as $option) {
            $optionData = (object) [
                'product_id' => $productId,
                'name'       => $option->option_unique_name,
                'type'       => 'list',
                'ordering'   => $option->option_ordering,
            ];

            $db->insertObject('#__easystore_product_options', $optionData);
            $productOptionId = $db->insertid();

            $query = $db->getQuery(true);
            $query->select([
                'pov.*',
                'ov.optionvalue_name',
            ])
                ->from($db->quoteName('#__j2store_product_optionvalues', 'pov'))
                ->join('LEFT', $db->quoteName('#__j2store_optionvalues', 'ov'), $db->quoteName('ov.j2store_optionvalue_id') . ' = ' . $db->quoteName('pov.optionvalue_id'))
                ->where($db->quoteName('pov.productoption_id') . ' = ' . $option->j2store_productoption_id);

            $db->setQuery($query);
            $optionValues = $db->loadObjectList();

            $isUniqueCheck = [];

            foreach ($optionValues as $optionValue) {
                $optionValueData = (object) [
                    'product_id' => $productId,
                    'option_id'  => $productOptionId,
                    'name'       => $optionValue->optionvalue_name,
                ];
                $string = $productId . '-' . $productOptionId . '-' . $optionValueData->name;
                if (!in_array($string, $isUniqueCheck)) {
                    $db->insertObject('#__easystore_product_option_values', $optionValueData);
                    $isUniqueCheck[] = $string;
                }
            }
        }
    }

    /**
     * Function to get combination name and value
     *
     * @param int $variantId
     * @return object
     */
    private function getCombinationNameAndValue($variantId)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('*')
            ->from($db->quoteName('#__j2store_product_variant_optionvalues', 'pvo'))
            ->where($db->quoteName('pvo.variant_id') . ' = ' . $variantId);

        $db->setQuery($query);
        $productOptionValueIds = $db->loadObject()->product_optionvalue_ids;

        $result = (object) [
            'combinationName'  => '',
            'combinationValue' => '',
        ];

        $orm = new EasyStoreDatabaseOrm();

        if (!empty($productOptionValueIds)) {
            $chunks = explode(',', $productOptionValueIds);
            $name   = '';
            $value  = '';
            foreach ($chunks as $optionValueId) {
                $productOptionValue = $orm->hasOne($optionValueId, '#__j2store_product_optionvalues', 'j2store_product_optionvalue_id')->loadObject();

                if (!empty($productOptionValue)) {
                    $valueId               = $productOptionValue->optionvalue_id;
                    $optionValueName       = $orm->hasOne($valueId, '#__j2store_optionvalues', 'j2store_optionvalue_id')->loadObject()->optionvalue_name;
                    empty($name) ? $name   = $optionValueName : $name .= ' | ' . $optionValueName;
                    empty($value) ? $value = $optionValueName : $value .= ';' . $optionValueName;
                }
            }

            $result->combinationName  = $name;
            $result->combinationValue = $value;
        }

        return $result;
    }

    /**
     * Function to insert product tags
     *
     * @param array $tagsData
     * @return bool
     */
    private function insertProductTags($tagsData)
    {
        $tagsDataArray = [];

        foreach ($tagsData as $tag) {
            $productId = $this->getProductIdByContentId($tag->content_item_id);

            $insertData = [
                'product_id' => $productId,
                'tag_id'     => $tag->tag_id,
            ];

            $tagsDataArray[] = $insertData;
        }

        return EasyStoreDatabaseOrm::insertMultipleRecords('#__easystore_product_tag_map', $tagsDataArray);
    }

    /**
     * Function to get product id by content id
     *
     * @param int $contentId
     * @return string
     */
    private function getProductIdByContentId($contentId)
    {
        return EasyStoreDatabaseOrm::get('#__j2store_products', 'product_source_id', $contentId)->loadObject()->j2store_product_id;
    }

    /**
     * Function to insert customers by chunk
     *
     * @param array $customers
     * @return void
     */
    private function insertCustomers($customers)
    {
        $user      = Factory::getApplication()->getIdentity();
        $createdBy = $user->id;

        foreach ($customers as $customer) {
            $db            = Factory::getContainer()->get(DatabaseInterface::class);
            $created       = Factory::getDate('now')->toSql();
            $countryCityId = EasyStoreHelper::getCountryStateIdFromJson($customer->country_name, $customer->zone_name);
            $address       = [
                "name"      => $customer->customer_name,
                "country"   => $countryCityId->country,
                "state"     => $countryCityId->state,
                "city"      => $customer->zone_name,
                "zip_code"  => $customer->zip,
                "address_1" => $customer->address_1,
                "address_2" => $customer->address_2,
            ];

            if ($customer->user_id != 0) {
                $phone = $customer->phone_1 ?? '';

                if (empty($customer->phone_1)) {
                    $phone = $customer->phone_2 ?? '';
                }

                $customerData = [
                    'user_id'    => $customer->user_id,
                    'user_type'  => 'customer',
                    'phone'      => $phone,
                    'created'    => $created,
                    'created_by' => $createdBy,
                    'modified'   => $created,
                ];

                if (!$this->isJoomlaUser($customer->user_id)) {
                    $userData = [
                        'name'      => $customer->customer_name,
                        'username'  => $customer->email,
                        'email'     => $customer->email,
                        'password'  => 'secret',
                        'password2' => 'secret',
                    ];

                    $joomlaUser = $this->setNewJoomlaUser($userData);

                    // If email exists $joomlaUser will be False
                    if (!$joomlaUser) {
                        // We need to fetch existing id
                        $existingId              = $this->getUserIdByEmail($customer->email);
                        $customerData['user_id'] = $existingId;
                    } else {
                        $customerData['user_id'] = $joomlaUser->id;
                    }
                }

                if ($customer->type == 'shipping') {
                    $customerData['shipping_address'] = json_encode($address);
                } else {
                    $customerData['billing_address'] = json_encode($address);
                }

                $customerData = (object) $customerData;

                try {
                    EasyStoreDatabaseOrm::updateOrCreate('#__easystore_users', $customerData, 'email');
                    $new_id = $db->insertid();

                    $this->setUserMap($customer->user_id, $new_id);
                } catch (\Exception $error) {
                    $db->transactionRollback();
                    $response = [
                        'status'  => 'failed',
                        'message' => $error->getMessage(),
                    ];

                    echo json_encode($response);
                    exit;
                }
            } else {
                $guestData = (object) [
                    'email'            => $customer->email,
                    'shipping_address' => json_encode($address ?? []),
                ];

                try {
                    EasyStoreDatabaseOrm::updateOrCreate('#__easystore_guests', $guestData, 'email');
                } catch (\Exception $error) {
                    $db->transactionRollback();
                    $response = [
                        'status'  => 'failed',
                        'message' => $error->getMessage(),
                    ];

                    echo json_encode($response);
                    exit;
                }
            }
        }
    }

    /**
     * Function to check if its a Joomla user
     *
     * @param int $id
     * @return bool
     */
    private function isJoomlaUser(int $id)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('*')
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('id') . ' = ' . $id);

        $db->setQuery($query);
        $db->execute();

        return $db->getNumRows() > 0 ? true : false;
    }

    /**
     * Function to get user id by email
     *
     * @param string $email
     * @return int
     */
    private function getUserIdByEmail(string $email)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('*')
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('email') . ' = ' . $db->quote($email));

        $db->setQuery($query);
        $result = $db->loadObject();

        return $result->id;
    }

    /**
     * Function to create new Joomla User
     *
     * @param array $userData
     * @return object
     */
    private function setNewJoomlaUser($userData)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('*')
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('email') . ' = ' . $db->quote($userData['email']));

        $db->setQuery($query);
        $db->execute();

        if ($db->getNumRows() > 0) {
            return false;
        }

        $joomlaUser = new JoomlaUser();

        // Bind the data.
        try {
            $joomlaUser->bind($userData);
        } catch (\Exception $error) {
            $db->transactionRollback();
            $response = [
                'status'  => 'failed',
                'message' => $error->getMessage(),
            ];

            echo json_encode($response);
            exit;
        }

        // Load the users plugin group.
        PluginHelper::importPlugin('user');

        // Retrieve the user groups so they don't get overwritten
        unset($joomlaUser->groups);
        $joomlaUser->groups = Access::getGroupsByUser($joomlaUser->id, false);

        // Store the data.
        try {
            $joomlaUser->save();
        } catch (\Exception $error) {
            $db->transactionRollback();
            $response = [
                'status'  => 'failed',
                'message' => $error->getMessage(),
            ];

            echo json_encode($response);
            exit;
        }

        return $joomlaUser;
    }

    /**
     * Function for Setting session data to map new Joomla user to track old j2store data
     * Format ['j2store_id' => 'new_id']
     *
     * @param int $oldId
     * @param int $newId
     *
     * @return void
     */
    private function setUserMap(int $oldId, int $newId)
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $session     = $app->getSession();
        $userMapData = [];

        if (!empty($session->get('com_easystore.user_map'))) {
            $userMapData = $session->get('com_easystore.user_map');
        }

        $userMapData[$oldId] = $newId;
        $session->set('com_easystore.user_map', $userMapData);
    }

    /**
     * Function to insert orders by chunk
     *
     * @param array $orders
     * @return void
     */
    private function insertOrders($orders)
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $session     = $app->getSession();
        $userMapData = [];

        if (!empty($session->get('com_easystore.user_map'))) {
            $userMapData = $session->get('com_easystore.user_map');
        }

        $cParams           = ComponentHelper::getParams('com_easystore');
        $easyStoreCurrency = $cParams->get('currency', 'USD:US$');

        foreach ($orders as $order) {

            if (empty($order->order_id)){
                continue;
            }

            $paymentStatus = 'unpaid';

            if (!empty($order->transaction_status) && strtolower($order->transaction_status) == 'completed') {
                $paymentStatus = 'paid';
            }

            if ($order->orderpayment_type == 'payment_cash' && $order->order_state_id == 1) {
                $paymentStatus = 'paid';
            }

            // Set the mapped user id
            $customerId = 0;
            if (array_key_exists($order->user_id, $userMapData)) {
                $customerId = $userMapData[$order->user_id];
            }

            if (!empty($order->orderpayment_type) && $order->orderpayment_type != 'payment_cash') {
                $paymentMethod = str_replace('payment_', '', $order->orderpayment_type);
            } else {
                $paymentMethod = 'manual_payment';
            }

            $addresses     = $this->getAddresses($order->order_id);
            $shippingValue = number_format((float) $order->order_shipping, 2, '.', '');
            $shipping      = [
                'name'               => $order->shipping_name,
                'estimate'           => '',
                'rate'               => $shippingValue,
                'offerFreeShipping'  => false,
                'offerOnAmount'      => null,
                'isLatest'           => false,
                'isCollapsed'        => true,
                'uuid'               => 'custom',
                'id'                 => htmlspecialchars(EasyStoreHelper::generateUuidV4(), ENT_QUOTES),
                'rate_with_currency' => $easyStoreCurrency . $shippingValue,
                'price'              => $shippingValue,
            ];

            $orderData = (object) [
                'id'               => $order->j2store_order_id,
                'creation_date'    => $order->created_on,
                'customer_id'      => $customerId,
                'customer_email'   => $order->user_email,
                'shipping_address' => $addresses->shipping,
                'billing_address'  => $addresses->billing,
                'customer_note'    => $order->customer_note,
                'payment_status'   => $paymentStatus,
                'fulfilment'       => $order->order_state_id == 1 ? 'fulfilled' : 'unfulfilled',
                'order_status'     => 'active',
                'is_guest_order'   => $order->user_id == 0 ? 1 : 0,
                'discount_type'    => "percent",
                'discount_value'   => 0,
                'transaction_id'   => $order->transaction_id,
                'sale_tax'         => $order->order_tax - $order->order_discount_tax + $order->order_shipping_tax,
                'shipping_value'   => $order->order_shipping,
                'shipping'         => json_encode($shipping),
                'payment_method'   => $paymentMethod,
                'created'          => $this->dateValidate($order->created_on),
                'created_by'       => $order->created_by,
                'modified'         => $this->dateValidate($order->created_on),
                'modified_by'      => $order->created_by,
            ];

            if ($order->discount_type == 'coupon') {
                $orderData->coupon_id     = $order->coupon_id;
                $orderData->coupon_code   = $order->coupon_code;
                $orderData->coupon_type   = ($order->coupon_type == 'percentage_cart' || $order->coupon_type == 'percentage_product') ? 'percent' : 'amount';
                $orderData->coupon_amount = $order->order_discount + $order->order_discount_tax;
            }

            $db = Factory::getContainer()->get(DatabaseInterface::class);
            try {
                $db->insertObject('#__easystore_orders', $orderData);

                // Process variants
                $orderItems = $this->getOrderItems($order->order_id);

                foreach ($orderItems as $orderItem) {
                    $orderProductMapData = (object) [
                        'order_id'   => $order->j2store_order_id,
                        'product_id' => $orderItem->product_id,
                        'quantity'   => $orderItem->orderitem_quantity,
                        'price'      => $orderItem->orderitem_finalprice_without_tax,
                    ];

                    if (empty($orderItem->is_master)) {
                        $orderProductMapData->variant_id = $orderItem->variant_id;
                    }

                    $db->insertObject('#__easystore_order_product_map', $orderProductMapData);
                }

                // Process order activities
                $orderHistories = $this->getOrderHistories($order->order_id);

                foreach ($orderHistories as $activity) {
                    $orderActivityData = (object) [
                        'order_id'       => $order->j2store_order_id,
                        'activity_type'  => 'comment',
                        'activity_value' => $activity->comment,
                        'created'        => $this->dateValidate($activity->created_on),
                        'created_by'     => $activity->created_by,
                        'modified'       => $this->dateValidate($activity->created_on),
                    ];

                    $db->insertObject('#__easystore_order_activities', $orderActivityData);
                }
            } catch (\Exception $error) {
                $db->transactionRollback();

                $response = [
                    'status'  => 'failed',
                    'message' => $error->getMessage(),
                ];

                echo json_encode($response);
                exit;
            }
        }
    }

    /**
     * Get J2Store order items by order id
     *
     * @param int $orderId
     * @return object
     */
    private function getOrderItems($orderId)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('i.*');
        $query->select('v.is_master');
        $query->from($db->quoteName('#__j2store_orderitems', 'i'));
        $query->join('LEFT', $db->quoteName('#__j2store_variants', 'v'), $db->quoteName('i.variant_id') . ' = ' . $db->quoteName('v.j2store_variant_id'));
        $query->where($db->quoteName('i.order_id') . ' = ' . $orderId);

        $db->setQuery($query);

        return $db->loadObjectList();
    }

    /**
     * Get J2Store order histories by order id
     *
     * @param int $orderId
     * @return object
     */
    private function getOrderHistories($orderId)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('h.*');
        $query->from($db->quoteName('#__j2store_orderhistories', 'h'));
        $query->where($db->quoteName('h.order_id') . ' = ' . $orderId);
        $query->order($db->quoteName('h.created_on'), 'ASC');

        $db->setQuery($query);

        return $db->loadObjectList();
    }

    /**
     * Function to get addresses by Order ID
     *
     * @param int $orderId
     * @return object
     */
    private function getAddresses($orderId)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $address           = new \stdClass();
        $address->shipping = '';
        $address->billing  = '';
        $shippingAddress   = [];
        $billingAddress    = [];

        $query->select('*')
            ->from($db->quoteName('#__j2store_orderinfos'))
            ->where($db->quoteName('order_id') . ' = ' . $db->quote($orderId));
        $db->setQuery($query);
        $info = $db->loadObject();

        if (!empty($info)) {
            $shippingName = !empty($info->shipping_middle_name) ? $info->shipping_first_name . ' ' . $info->shipping_middle_name . ' ' . $info->shipping_last_name : $info->shipping_first_name . ' ' . $info->shipping_last_name;
            $billingName  = !empty($info->billing_middle_name) ? $info->billing_first_name . ' ' . $info->billing_middle_name . ' ' . $info->billing_last_name : $info->billing_first_name . ' ' . $info->billing_last_name;

            $shippingCountryCityId = EasyStoreHelper::getCountryStateIdFromJson($info->shipping_country_name, $info->shipping_zone_name);
            $shippingCountry       = $shippingCountryCityId->country;
            $shippingState         = $shippingCountryCityId->state;

            $billingCountryCityId = EasyStoreHelper::getCountryStateIdFromJson($info->billing_country_name, $info->billing_zone_name);
            $billingCountry       = $billingCountryCityId->country;
            $billingState         = $billingCountryCityId->state;

            $shippingAddress = [
                'name'      => $shippingName,
                'country'   => $shippingCountry,
                'state'     => $shippingState,
                'city'      => $info->shipping_city,
                'zip_code'  => $info->shipping_zip,
                'address_1' => $info->shipping_address_1,
                'address_2' => $info->shipping_address_2,
                'phone'     => empty($info->shipping_phone_1) ? $info->shipping_phone_2 : $info->shipping_phone_1,
            ];

            $billingAddress = [
                'name'      => $billingName,
                'country'   => $billingCountry,
                'state'     => $billingState,
                'city'      => $info->billing_city,
                'zip_code'  => $info->billing_zip,
                'address_1' => $info->billing_address_1,
                'address_2' => $info->billing_address_2,
                'phone'     => empty($info->billing_phone_1) ? $info->billing_phone_2 : $info->billing_phone_1,
            ];
        }

        $address->shipping = json_encode($shippingAddress);
        $address->billing  = json_encode($billingAddress);

        return $address;
    }

    /**
     * Function to get the unit name from j2store
     *
     * @param string $unitName
     * @param int $id
     * @return string
     */
    private function getJ2storeUnitById($unitName, $id)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $tableName    = '#__j2store_' . $unitName . 's';
        $selectColumn = $unitName . '_unit';
        $idColumn     = 'j2store_' . $unitName . '_id';

        $query->select($selectColumn);
        $query->from($db->quoteName($tableName));
        $query->where($db->quoteName($idColumn) . ' = ' . $id);

        $db->setQuery($query);

        return !empty($db->loadObject()) ? $db->loadObject()->$selectColumn : ($unitName == 'weight' ? 'kg' : 'cm');
    }

    /**
     * Function to convert weight to gram
     *
     * @param mixed $weight
     * @param string $unit
     * @return mixed
     */
    private function convertWeightToGram($weight, $unit)
    {
        switch ($unit) {
            case 'g':
                return number_format($weight, 2, '.', '');
            case 'kg':
                return number_format($weight * 1000.0, 2, '.', '');
            case 'lb':
                return number_format($weight * 453.592, 2, '.', '');
            case 'oz':
                return number_format($weight * 28.349500000294, 2, '.', '');
            default:
                return number_format($weight, 2, '.', '');
        }
    }

    /**
     * Function to convert length to mm
     *
     * @param mixed $length
     * @param string $unit
     * @return mixed
     */
    private function convertLengthToMm($length, $unit)
    {
        switch ($unit) {
            case 'mm':
                return number_format($length, 2, '.', '');
            case 'cm':
                return number_format($length * 10.0, 2, '.', '');
            case 'm':
                return number_format($length * 1000.0, 2, '.', '');
            case 'in':
                return number_format($length * 25.4, 2, '.', '');
            case 'ft':
                return number_format($length * 304.8, 2, '.', '');
            case 'yd':
                return number_format($length * 914.4, 2, '.', '');
            default:
                return number_format($length, 2, '.', '');
        }
    }

    /**
     * Function to convert weight
     *
     * @param mixed $weight
     * @param string $unit
     * @return mixed
     */
    private function convertWeight($amount, $unit)
    {
        switch ($unit) {
            case 'g':
                return number_format($amount, 2, '.', '');
            case 'kg':
                return number_format($amount * 0.001, 2, '.', '');
            case 'lb':
                return number_format($amount * 0.00220462, 2, '.', '');
            case 'oz':
                return number_format($amount * 0.0352739200000000003, 2, '.', '');
            default:
                return number_format($amount, 2, '.', '');
        }
    }

    /**
     * Function to convert length to mm
     *
     * @param mixed $length
     * @param string $unit
     * @return mixed
     */
    private function convertLength($length, $unit)
    {
        switch ($unit) {
            case 'mm':
                return number_format($length, 2, '.', '');
            case 'cm':
                return number_format($length * 0.1, 2, '.', '');
            case 'm':
                return number_format($length * 0.001, 2, '.', '');
            case 'in':
                return number_format($length * 0.0393701, 2, '.', '');
            case 'ft':
                return number_format($length * 0.003280841666667, 2, '.', '');
            case 'yd':
                return number_format($length * 0.0010936138888889999563, 2, '.', '');
            default:
                return number_format($length, 2, '.', '');
        }
    }

    /**
     * Function to convert measurement units
     *
     * @param object $data
     * @return object
     */
    private function convertUnits($data)
    {
        $settings        = SettingsHelper::getSettings();
        $storeUnit       = $settings->get('products.standardUnits', '');
        $storeWeightUnit = !empty($storeUnit->weight) ? $storeUnit->weight : 'kg';
        $storeLengthUnit = !empty($storeUnit->dimension) ? $storeUnit->dimension : 'cm';

        $weightClassId = !empty($data->weight_class_id) ? $data->weight_class_id : 0;
        $lengthClassId = !empty($data->length_class_id) ? $data->length_class_id : 0;

        $currentWeightUnit = $this->getJ2storeUnitById('weight', $weightClassId);
        $currentLengthUnit = $this->getJ2storeUnitById('length', $lengthClassId);

        $weightUnit = empty($currentWeightUnit) ? $storeWeightUnit : $currentWeightUnit;
        $lengthUnit = empty($currentLengthUnit) ? $storeLengthUnit : $currentLengthUnit;

        $weight       = !empty($data->weight) ? $data->weight : 0;
        $weightInGram = $this->convertWeightToGram($weight, $weightUnit);

        $length = !empty($data->length) ? $data->length : 0;
        $width  = !empty($data->width) ? $data->width : 0;
        $height = !empty($data->height) ? $data->height : 0;

        $lengthInMm = $this->convertLengthToMm($length, $lengthUnit);
        $widthInMm  = $this->convertLengthToMm($width, $lengthUnit);
        $heightInMm = $this->convertLengthToMm($height, $lengthUnit);

        $result = new \stdClass();

        $result->finalWeight = $this->convertWeight($weightInGram, $storeWeightUnit);
        $result->finalLength = $this->convertLength($lengthInMm, $storeLengthUnit);
        $result->finalWidth  = $this->convertLength($widthInMm, $storeLengthUnit);
        $result->finalHeight = $this->convertLength($heightInMm, $storeLengthUnit);

        return $result;
    }

    /**
     * Function to delete table data
     *
     * @param string $table
     * @param bool $doDelete     Run delete operation instead of truncate
     * @return bool
     */
    private function deleteTableData($table, $doDelete = false)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        if (!$doDelete) {
            if ($db->truncateTable($table)) {
                return true;
            }

            return false;
        } else {
            $query = $db->getQuery(true);
            $query->delete($db->quoteName($table));
            $db->setQuery($query);

            try {
                $db->execute();
            } catch (\Exception $e) {
                $db->transactionRollback();
                $response = [
                    'status'  => 'failed',
                    'message' => $e->getMessage(),
                ];

                echo json_encode($response);
                exit;
            }
        }
    }

    /**
     * Function to validate and null check date
     *
     * @param mixed $date
     * @return string
     */
    private function dateValidate($date, $defaultDate = null)
    {
        $defaultDate = !is_null($defaultDate) && !empty($defaultDate) ? date('Y-m-d H:i:s', strtotime((string) $defaultDate)) : Factory::getDate('now')->toSql();

        return is_null($date) ? $defaultDate : date('Y-m-d H:i:s', strtotime((string) $date));
    }

    /**
     * Remove the first '#' only if it appears after the file extension.
     *
     * @param string $string Input file path or URL.
     * @return string Cleaned string.
     */
    function removeHashAfterExtension(string $string): string {
        return preg_replace('/(\.[a-z0-9]+)#.*/i', '$1', $string);
    }
}
