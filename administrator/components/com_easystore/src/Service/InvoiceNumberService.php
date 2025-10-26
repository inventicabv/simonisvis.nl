<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Service;

use Joomla\CMS\Date\Date;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;

class InvoiceNumberService
{
    public function generateInvoiceNumber(int $lastNumber = 0): string
    {
        $settings   = $this->getInvoiceSettings();
        $today      = Date::getInstance();
        $resetDate  = $this->getResetDate($settings);

        // Reset if needed
        if ($this->shouldResetCounter($resetDate, $today)) {
            $lastNumber = 0;
        }

        $nextNumber = $lastNumber + 1;
        return $this->formatInvoiceNumber($settings, $nextNumber);
    }

    private function getInvoiceSettings(): array
    {
        $settings = SettingsHelper::getSettings();

        return [
            'base'         => $settings->get('general.customInvoiceIdBase') ?? '000000',
            'resetOption'  => $settings->get('general.customInvoiceIdResetOption') ?? 'never',
            'resetDate'    => $settings->get('general.customInvoiceIdCustomResetDate') ?? '',
        ];
    }

    private function getResetDate(array $settings): ?Date
    {
        if ($settings['resetOption'] === 'never') {
            return null;
        }

        if (!empty($settings['resetDate'])) {
            return Date::getInstance($settings['resetDate']);
        }

        return null;
    }

    private function formatInvoiceNumber(array $settings, int $number): string
    {
        $formatted = str_pad($number, strlen($settings['base']), '0', STR_PAD_LEFT);
        return $formatted;
    }

    private function shouldResetCounter(?Date $resetDate, Date $today): bool
    {
        if (!$resetDate) {
            return false;
        }
        return $today->getTimestamp() >= $resetDate->getTimestamp();
    }
}
