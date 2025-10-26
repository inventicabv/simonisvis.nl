<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

$placeholder = $placeholder ?? Text::_('COM_EASYSTORE_SEARCH');
$uri = Uri::getInstance();
$filterQuery = $uri->getVar('filter_query', '');
$queries = $uri->getQuery(true) ?? [];

$filter = new InputFilter();
$filterQuery = $filter->clean($filterQuery);

if (isset($queries['filter_query'])) {
    unset($queries['filter_query']);
}

?>
<form data-easystore-search action="<?php echo Route::_('index.php?option=com_easystore&view=products'); ?>" method="get" role="search">
    <div class="easystore-search-container">
        <?php echo EasyStoreHelper::getIcon('search'); ?>
        <input type="search" name="filter_query" value="<?php echo $filterQuery; ?>" class="form-control" placeholder="<?php echo $this->escape($placeholder); ?>">
        <?php foreach ($queries as $name => $value) : ?>
            <input type="hidden" name="<?php echo $this->escape($name); ?>" value="<?php echo $filter->clean($value) ?>" />
        <?php endforeach; ?>
    </div>
</form>

<script defer>
    window.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('[data-easystore-search]');
        const queryElement = form.querySelector('[name=filter_query]');
        let previousValue = queryElement.value;
        
        queryElement.addEventListener('input', (event) => {
            if (event.target.value === '' && previousValue !== '') {
                form.submit();
            }
        });
    });
</script>