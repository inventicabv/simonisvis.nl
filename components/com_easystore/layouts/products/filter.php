<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Site\Helper\FilterHelper;

extract($displayData);

$tempOutput = '';
$hasItems = false;

$filter_items = $settings->filter_items ?? [];

foreach ($filter_items as $filter_item) {

    if (property_exists($filter_item, 'type') === false) {
        continue;
    }
    
    $filter_type = $filter_item->type;
    $function = 'get' . ucfirst($filter_type);

    if (method_exists(FilterHelper::class, $function)) {

        if (property_exists($filter_item, 'ordering')) {
            $filter_ordering = $filter_item->ordering ?? 'ASC';

            if ($filter_ordering) {
                Factory::getApplication()->input->set('filter_ordering_'  . $filter_type , $filter_ordering);
            }
        }
        
        $options = FilterHelper::$function();

        if ($options) {
            $hasItems = true;

            if (isset($settings->range_separator_label) && $settings->range_separator_label) {
                $filter_item->range_separator_label = $settings->range_separator_label ?? ':';
            }

            $tempOutput .= EasyStoreHelper::loadLayout(
                'filter.' . $filter_type,
                [
                    'settings' => $filter_item,
                    'options' => $options
                ]
            );
        }
    }
}
?>

<?php if ($hasItems) : ?>
    <div class="easystore-filter-container">
        <?php echo $tempOutput; ?>
    </div>
<?php endif; ?>