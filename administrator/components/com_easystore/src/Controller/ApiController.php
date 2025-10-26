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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Controller\BaseController;
use JoomShaper\Component\EasyStore\Administrator\Concerns\OrderEditable;
use JoomShaper\Component\EasyStore\Administrator\Concerns\Taxable;
use JoomShaper\Component\EasyStore\Administrator\Traits\User;
use JoomShaper\Component\EasyStore\Administrator\Traits\Media;
use JoomShaper\Component\EasyStore\Administrator\Traits\Order;
use JoomShaper\Component\EasyStore\Administrator\Traits\Product;
use JoomShaper\Component\EasyStore\Administrator\Traits\Settings;
use JoomShaper\Component\EasyStore\Administrator\Traits\Analytics;
use JoomShaper\Component\EasyStore\Administrator\Traits\AppConfig;
use JoomShaper\Component\EasyStore\Administrator\Traits\Languages;
use JoomShaper\Component\EasyStore\Administrator\Traits\Categories;
use JoomShaper\Component\EasyStore\Administrator\Traits\Extensions;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;
use JoomShaper\Component\EasyStore\Administrator\Traits\ProductCoupon;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Controller for a API
 *
 * @since  1.0.0
 */
class ApiController extends BaseController
{
    use Analytics;
    use AppConfig;
    use Media;
    use Order;
    use Product;
    use User;
    use Settings;
    use Languages;
    use Extensions;
    use ProductCoupon;
    use Categories;
    use OrderEditable;
    use Taxable;

    public $output;

    /**
     * The Application
     *
     * @var    CMSApplication
     * @since  4.0.0
     */
    protected $app;

    /**
     * Method to check if you can add a new record.
     *
     * Extended classes can override this if necessary.
     *
     * @param   array  $data  An array of input data.
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    protected function allowAdd($data = [])
    {
        $acl = AccessControl::create();
        return $acl->canCreate();
    }

    /**
     * Method to check if you can edit an existing record.
     *
     * Extended classes can override this if necessary.
     *
     * @param   array   $data  An array of input data.
     * @param   string  $key   The name of the key for the primary key; default is id.
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    protected function allowEdit($data = [], $key = 'id')
    {
        return true;
    }

    /**
     * Method to check if you can save a new or existing record.
     *
     * Extended classes can override this if necessary.
     *
     * @param   array   $data  An array of input data.
     * @param   string  $key   The name of the key for the primary key.
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    protected function allowSave($data, $key = 'id')
    {
        $recordId = $data[$key] ?? '0';

        if ($recordId) {
            return $this->allowEdit($data, $key);
        } else {
            return $this->allowAdd($data);
        }
    }

    /**
     * Method to check if it's allowed to delete an existing file or folder.
     *
     * @param   array   $data  An array of input data.
     * @param   string  $key   The name of the key for the primary key; default is id.
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    protected function allowDelete($data = [], $key = 'id')
    {
        $acl = AccessControl::create();
        return $acl->canDelete();
    }

    /**
     * Function to format value to Numeric
     *
     * @param string $value
     * @param string $type
     * @param float $default
     * @return number
     */
    private function formatToNumeric($value, $type = 'int', $default = 0)
    {
        if ($type === 'int') {
            return (!empty($value) && is_numeric($value)) ? (int) $value : $default;
        } elseif ($type === 'decimal') {
            return (!empty($value) && is_numeric($value)) ? number_format((float) $value, 2, '.', '') : number_format((float) $default, 2, '.', '');
        } else {
            return $default;
        }
    }

    /**
     * Function to get input method
     *
     * @return string
     *
     * @since   1.0.0
     */
    private function getInputMethod()
    {
        $input  = Factory::getApplication()->input;
        $method = $input->getString('_method', 'GET');

        return \strtoupper($method);
    }

    /**
     * Check given HTTP method is allowed or not
     *
     * @param array $notAllowedMethods
     * @param string $method
     *
     * @return void
     *
     * @since   1.0.0
     *
     */
    private function checkNotAllowedMethods(array $notAllowedMethods, string $method)
    {
        if (in_array($method, $notAllowedMethods)) {
            $this->sendResponse(Text::_('COM_EASYSTORE_METHOD_NOT_ALLOWED'), 405);
        }
    }

    /**
     * An abstraction of the $input->get() method.
     * Here we are just checking the null, true, false values those are coming as string.
     * If we found those values then return the respective values,
     * otherwise return the original filtered value.
     *
     * @param   string  $name       The request field name.
     * @param   mixed   $default    Any default value.
     * @param   string  $filter     The filter similar to the ->get() method.
     *
     * @return  mixed
     */
    public function getInput(string $name, $default = null, string $filter = 'cmd')
    {
        $input = Factory::getApplication()->input;
        $value = $input->get($name, $default, $filter);

        if (empty($value) || is_array($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        switch (strtolower($value)) {
            case 'null':
                return null;
            case 'true':
                return 1;
            case 'false':
                return 0;
            default:
                return $value;
        }
    }

    /**
     * Send JSON Response to the client.
     * {"success":true,"message":"ok","messages":null,"data":[{"key":"value"}]}
     *
     * @param   mixed   $response   The response array or data.
     * @param   int     $statusCode The status code of the HTTP response.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function sendResponse($response, int $statusCode = 200)
    {
        $this->app->setHeader('Content-Type', 'application/json');
        $this->app->setHeader('Cache-Control', 'no-cache');
        $this->app->setHeader('status', $statusCode, true);
        $this->app->sendHeaders();

        echo new JsonResponse($response);

        $this->app->close();
    }

    /**
     * Function to concat Date & Time and convert as mysql timestamp
     *
     * @param string $date
     * @param string $time
     * @return string
     */
    public function concatDateTime(string $date, string $time)
    {
        if (empty($date)) {
            return null;
        }

        $date = date('Y-m-d', strtotime($date));

        if (empty($time)) {
            $time = '00:00:00';
        } else {
            $time = date('H:i:s', strtotime($time));
        }

        return $date . ' ' . $time;
    }
}
