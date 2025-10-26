<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Traits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Administrator\Model\SettingsModel;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;

trait Settings
{
    public function settings()
    {
        $requestMethod = $this->getInputMethod();

        $this->checkNotAllowedMethods(['GET', 'PUT', 'DELETE', 'PATCH'], $requestMethod);

        if ($requestMethod === 'POST') {
            $this->postSettings();
        }
    }
    /**
     * Function to handle POST request for settings
     *
     * @return void
     */
    private function postSettings()
    {
        $settingsInfo           = new \stdClass();
        $settingsInfo->property = $this->getInput('property', '', 'STRING');
        $settingsInfo->data     = $this->getInput('data', '', 'RAW');

        $acl           = AccessControl::create();
        $hasPermission = $acl->canCreate() || $acl->canEdit();

        if (!$hasPermission) {
            $this->sendResponse(['message' => Text::_("COM_EASYSTORE_PERMISSION_ERROR_MSG")], 403);
        }

        if (empty($settingsInfo->property) || empty($settingsInfo->data)) {
            $this->sendResponse(['message' => Text::_("COM_EASYSTORE_FAILED_TO_UPDATE_SETTINGS")], 400);
        }

        if ($settingsInfo->property === 'general') {
            $data         = json_decode($settingsInfo->data);
            $checkList    = ['addressLineOne', 'city', 'postcode', 'country'];
            $errorMessage = '';

            foreach ($data as $key => $value) {
                if (in_array($key, $checkList) && empty($value)) {
                    $errorMessage = Text::_("COM_EASYSTORE_SETTINGS_FAILED_CANNOT_EMPTY");
                    break;
                }
            }

            if (!empty($errorMessage)) {
                $this->sendResponse(['message' => $errorMessage], 400);
            }
        }

        if ($settingsInfo->property === 'shipping') {
            $data          = json_decode($settingsInfo->data);
            $errorResponse = [];

            foreach ($data as $value) {
                foreach ($value->regions as $region) {
                    if (empty($region->country)) {
                        $errorResponse['country'] = Text::_("COM_EASYSTORE_SETTINGS_FAILED_CANNOT_EMPTY");
                        break;
                    }
                }

                if ($value->methodType === 'flat') {
                    foreach ($value->flatRate as $flatRate) {
                        if (empty($flatRate->name)) {
                            $errorResponse['flat_name'] = Text::_("COM_EASYSTORE_SETTINGS_FAILED_CANNOT_EMPTY");
                        }

                        if (!isset($flatRate->rate) || is_null($flatRate->rate)) {
                            $errorResponse['flat_rate'] = Text::_("COM_EASYSTORE_SETTINGS_FAILED_CANNOT_EMPTY");
                        }
                    }
                }

                if ($value->methodType === 'weight') {
                    foreach ($value->rateByWeight as $rateByWeight) {
                        if (empty($rateByWeight->name)) {
                            $errorResponse['weight_name'] = Text::_("COM_EASYSTORE_SETTINGS_FAILED_CANNOT_EMPTY");
                        }

                        foreach ($rateByWeight->weights as $weight) {
                            if (!isset($weight->from) || is_null($weight->from)) {
                                $errorResponse['weight_from'] = Text::_("COM_EASYSTORE_SETTINGS_FAILED_CANNOT_EMPTY");
                            }

                            if (!isset($weight->rate) || is_null($weight->rate)) {
                                $errorResponse['weight_rate'] = Text::_("COM_EASYSTORE_SETTINGS_FAILED_CANNOT_EMPTY");
                            }
                        }
                    }
                }
            }

            if (!empty($errorResponse)) {
                $this->sendResponse(['message' => Text::_("COM_EASYSTORE_SETTINGS_FAILED_CANNOT_EMPTY")], 400);
            }
        }

        $model  = new SettingsModel();
        $result = $model->createOrUpdate($settingsInfo);

        if ($result) {
            $this->sendResponse(['message' => Text::_("COM_EASYSTORE_UPDATE_SETTINGS_SUCCESS"), 'status' => true]);
        } else {
            $this->sendResponse(['message' => Text::_("COM_EASYSTORE_FAILED_TO_UPDATE_SETTINGS"), 500]);
        }
    }
}
