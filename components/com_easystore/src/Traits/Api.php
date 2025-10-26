<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Traits;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

trait Api
{
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
     * @param   mixed $default    Any default value.
     * @param   string  $filter     The filter similar to the ->get() method.
     *
     * @return  mixed
     */
    private function getInput(string $name, $default = null, string $filter = 'cmd')
    {
        $input = Factory::getApplication()->input;
        $value = $input->get($name);

        if (empty($value)) {
            return $input->get($name, $default, $filter);
        }

        if (is_array($value)) {
            return $input->get($name, $default, $filter);
        }

        switch (strtolower($value)) {
            case 'null':
                return null;
            case 'true':
                return 1;
            case 'false':
                return 0;
        }

        return $input->get($name, $default, $filter);
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
}
