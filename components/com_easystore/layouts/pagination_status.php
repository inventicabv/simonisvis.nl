<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

function parseShortCode($code)
{
    switch ($code) {
        case '{{R}}':
            return '<span x-text="pagination.range"></span>';
        case '{{S}}':
            return '<span x-text="pagination.range_start"></span>';
        case '{{E}}':
            return '<span x-text="pagination.range_end"></span>';
        case '{{T}}':
            return '<span x-text="pagination.total"></span>';
        case '{{CP}}':
            return '<span x-text="pagination.page"></span>';
        case '{{TP}}':
            return '<span x-text="pagination.total_pages"></span>';
        default:
            return '';
    }
}

function parsePattern($pattern)
{
    $codes = ['{{R}}', '{{S}}', '{{E}}', '{{T}}', '{{CP}}', '{{TP}}'];

    foreach ($codes as $code) {
        $pattern = str_replace($code, parseShortCode($code), $pattern);
    }

    return $pattern;
}

?>

<p class="easystore-pagination-status" x-show="pagination.loaded" x-cloak>
    <?php if ($pattern === 'pattern1') : ?>
        <?php echo Text::_('COM_EASYSTORE_ADDON_PAGINATION_STATUS_PATTERN1'); ?>
    <?php elseif ($pattern === 'pattern2') : ?>
        <?php echo Text::_('COM_EASYSTORE_ADDON_PAGINATION_STATUS_PATTERN2'); ?>
    <?php elseif ($pattern === 'pattern3') : ?>
        <?php echo Text::_('COM_EASYSTORE_ADDON_PAGINATION_STATUS_PATTERN3'); ?>
    <?php elseif ($pattern === 'custom') : ?>
        <?php echo parsePattern($custom_pattern); ?>
    <?php endif; ?>
</p>

