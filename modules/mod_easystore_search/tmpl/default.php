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

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

?>

<form action="<?php echo Route::_('index.php?option=com_easystore&view=products'); ?>" method="get" name="searchform" id="searchform">
    <input type="hidden" name="option" value="com_easystore" />
    <input type="hidden" name="view" value="products" />
    <input type="hidden" name="task" value="search" />
    <div class="easystore-search-module-container d-flex">
        <?php if ($params->get('show_category', 1)): ?>
        <div class="easystore-search-module-select">
            <select name="filter_categories" id="mod_easystore_category" class="form-select">
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['alias']; ?>"><?php echo $category['title']; ?></option>
            <?php endforeach;?>
            </select>
        </div>
        <?php endif;?>
        <div class="easystore-search-module-input">
            <input type="text" name="filter_query" id="mod_easystore_search" class="form-control" placeholder="<?php echo Text::_('MOD_EASYSTORE_SEARCH_PLACEHOLDER'); ?>" />
        </div>
        <?php if ($params->get('show_search_button', 1)): ?>
        <div class="easystore-search-module-button">
            <button type="submit" class="btn btn-primary"><?php echo Text::_('MOD_EASYSTORE_SEARCH_BUTTON'); ?></button>
        </div>
        <?php endif;?>
    </div>
</form>