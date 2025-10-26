<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\MVC\Controller\AdminController;
use JoomShaper\Component\EasyStore\Administrator\Traits\Migration;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The Migration Controller
 *
 * @since  1.0.0
 */
class MigrationController extends AdminController
{
    use Migration;

    /**
     * The chunk size for migration batch process
     */
    public const CHUNK_SIZE = 500;

    /**
     * Migration function
     *
     * @return void
     */
    public function migrate()
    {
        ini_set('max_execution_time', 600);

        $migrationComplete = false;
        $input             = Factory::getApplication()->input;
        $offset            = $input->get('offset');
        $state             = strtolower($input->get('state'));
        $migrateFrom       = $this->setMigrateFrom();
        $component         = strtolower(str_replace('com_', '', $migrateFrom));

        if (empty($migrateFrom)) {
            $response = [
                'status'  => 'failed',
                'message' => 'Something went wrong!',
            ];

            echo json_encode($response);
            exit;
        }

        $dataForMigration    = (object) [
            'state'       => $state,
            'offset'      => $offset,
            'migrateFrom' => $migrateFrom,
        ];

        if (!PluginHelper::isEnabled('system', 'easystoreto' . $component . 'migration')) {
            $response = [
                'status'  => 'failed',
                'message' => 'Migration plugin disabled',
            ];

            echo json_encode($response);
            exit;
        }

        PluginHelper::importPlugin('system', 'easystoreto' . $component . 'migration');

        if ($state === 'settings') {
            $canMigrate = $this->canMigrate($migrateFrom);

            if (!$canMigrate->status) {
                $response = [
                    'status'  => 'failed',
                    'message' => $canMigrate->message,
                ];

                echo json_encode($response);
                exit;
            }

            $response = $this->triggerEvent('migrateSettings', $dataForMigration);
        } elseif ($state === 'categories') {
            $response = $this->triggerEvent('migrateCategories', $dataForMigration);
        } elseif ($state === 'tags') {
            $response = $this->triggerEvent('migrateTags', $dataForMigration);
        } elseif ($state === 'coupons') {
            $response = $this->triggerEvent('migrateCoupons', $dataForMigration);
        } elseif ($state === 'products') {
            $productCount                   = $this->getProductCount();
            $dataForMigration->productCount = $productCount;

            $response = $this->triggerEvent('migrateProducts', $dataForMigration);
        } elseif ($state === 'customers') {
            $customerCount                   = $this->getCustomerCount();
            $dataForMigration->customerCount = $customerCount;

            $response = $this->triggerEvent('migrateCustomers', $dataForMigration);
        } elseif ($state === 'orders') {
            $orderCount                   = $this->getOrderCount();
            $dataForMigration->orderCount = $orderCount;

            $response = $this->triggerEvent('migrateOrders', $dataForMigration);

            $migrationComplete = true;
        }

        if ($migrationComplete) {
            $migrationSettings = SettingsHelper::getSettings()->get('migration_status', '');

            if (empty($migrationSettings) || empty((array) $migrationSettings)) {
                $migrationSettings = [
                    [
                        'migration' => $component,
                        'status'    => 'complete',
                    ],
                ];
            } else {
                $settingsExists = false;

                foreach ($migrationSettings as &$setting) {
                    if ($setting->migration === $component) {
                        $setting->status = 'complete';
                        $settingsExists  = true;
                    }
                }

                unset($setting);

                if (!$settingsExists) {
                    $migrationSettings[] = [
                        'migration' => $component,
                        'status'    => 'complete',
                    ];
                }
            }

            $this->updateMigrationSettings($migrationSettings);
        }

        echo json_encode($response);
        exit;
    }

    /**
     * Function to update settings data
     *
     * @param array $settings
     * @return void
     */
    private function updateMigrationSettings($settings)
    {
        $value = json_encode($settings);

        $settingsData        = new \stdClass();
        $settingsData->key   = 'migration_status';
        $settingsData->value = $value;

        EasyStoreDatabaseOrm::updateOrCreate('#__easystore_settings', $settingsData, 'key');
    }

    /**
     * Function to get total product count
     *
     * @return int
     */
    private function getProductCount()
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('COUNT(' . $db->quoteName('j2store_product_id') . ') AS count')
            ->from($db->quoteName('#__j2store_products'));

        try {
            $db->setQuery($query);
            $count = $db->loadObject();

            return $count->count ?? 0;
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
     * Function to get total customer count
     *
     * @return int
     */
    private function getCustomerCount()
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('a.*');
        $query->select('CONCAT(a.first_name, " ", a.last_name) AS customer_name');
        $query->select($db->quote('c.country_name'));
        $query->select($db->quote('z.zone_name'));
        $query->from($db->quoteName('#__j2store_addresses', 'a'));
        $query->leftJoin($db->quoteName('#__j2store_countries', 'c') . ' ON a.country_id = c.j2store_country_id');
        $query->leftJoin($db->quoteName('#__j2store_zones', 'z') . ' ON a.zone_id = z.j2store_zone_id');
        $query->where($db->quoteName('a.email') . ' != ""');
        $query->where($db->quoteName('a.first_name') . ' != ""');
        $query->order($db->quoteName('a.user_id'), 'ASC');
        $query->order($db->quoteName('a.email'), 'ASC');

        $db->setQuery($query);
        $db->execute();

        $count = $db->getNumRows();

        return $count ?? 0;
    }

    /**
     * Function to get total order count
     *
     * @return int
     */
    private function getOrderCount()
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('o.*');
        $query->from($db->quoteName('#__j2store_orders', 'o'));
        $query->where($db->quoteName('o.order_type') . ' = ' . $db->quote('normal'));

        $db->setQuery($query);
        $db->execute();

        $count = $db->getNumRows();

        return $count ?? 0;
    }

    /**
     * Get J2Store order items by order id
     *
     * @param int $orderId
     * @return object
     */
    private function canMigrate($componentName)
    {
        // Check if plugin is installed & enabled
        $componentStatus = EasyStoreHelper::isComponentInstalled($componentName);

        if ($componentStatus->status === false) {
            $response = (object) [
                'status'  => false,
                'message' => $componentStatus->message,
            ];

            return $response;
        }

        $checkMigrationStatus = $this->checkMigrationStatus($componentName);

        if ($checkMigrationStatus->status === true) {
            $response = (object) [
                'status'  => false,
                'message' => $checkMigrationStatus->message,
            ];

            return $response;
        }

        $response = (object) [
            'status'  => true,
            'message' => 'Migration authorized',
        ];

        return $response;
    }

    /**
     * Trigger event function
     *
     * @param string $name
     * @param object $data
     * @return mixed
     */
    private function triggerEvent(string $name, object $data)
    {
        $event = AbstractEvent::create($name, ['subject' => $data,]);

        try {
            $eventResult = Factory::getApplication()->getDispatcher()->dispatch($event->getName(), $event);
            $eventResult = $eventResult->getArgument('result');
        } catch (\Throwable $th) {
            $eventResult = Factory::getApplication()->enqueueMessage($th->getMessage());
        }

        return $eventResult;
    }

    /**
     * Send JSON Response to the client.
     * {"success":true,"message":"ok","messages":null,"data":[{"key":"value"}]}
     *
     * @param   array   $response   The response array or data.
     * @param   int     $statusCode The status code of the HTTP response.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function sendResponse($response, int $statusCode = 200)
    {
        $this->app->setHeader('Content-Type', 'application/json');

        $this->app->setHeader('status', $statusCode, true);

        $this->app->sendHeaders();

        echo new JsonResponse($response);

        $this->app->close();
    }
}
