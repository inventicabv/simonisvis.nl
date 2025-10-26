<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

$app     = Factory::getApplication();
$initial = $initial ?? array_key_first((array) $options);
$default = $app->input->get('filter_' . $key, $initial, 'STRING');

// @todo: needs refactoring
?>
<div class="easystore-product-filter easystore-product-filters" data-easystore-filter-by="<?php echo $key; ?>">
    <div>
        <?php if (isset($title)) : ?>
            <div class="easystore-filter-header">
                <h4 class="easystore-filter-title easystore-h4"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h4>
                <span easystore-filter-reset><?php echo Text::_('COM_EASYSTORE_FILTER_RESET'); ?></span>
            </div>
        <?php endif;?>

        <details class="easystore-dropdown-wrapper">
            <summary>
                <span class="easystore-dropdown-title"><?php echo $options->$default->name; ?></span>
                <?php echo EasyStoreHelper::getIcon('caret'); ?>
            </summary>
            <ul class="easystore-dropdown" role="dropdown">
                <?php foreach ($options as $option) : ?>
                    <li class="easystore-dropdown-item">
                        <label class="easystore-checkbox">
                            <input type="radio" name="filter_<?php echo $key; ?>" class="easystore-visually-hidden" value="<?php echo $option->value; ?>" <?php echo $default == $option->value ? 'checked' : ''; ?>>
                            <span class="label"><?php echo $option->name; ?></span>
                        </label>
                    </li>
                <?php endforeach;?>
            </ul>
        </details>
    </div>
</div>