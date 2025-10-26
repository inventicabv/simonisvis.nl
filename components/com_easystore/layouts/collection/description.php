<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

$plainText = EasyStoreHelper::addLeadingSlashToImageSrc($content);
$originalText = $content;

?>


<div class="easystore-collection-description <?php echo $class . ' ' . $drop_cap_class; ?>">
    <div class="easystore-collection-description-content">
        <?php echo EasyStoreHelper::addLeadingSlashToImageSrc($content); ?>
    </div>
</div>