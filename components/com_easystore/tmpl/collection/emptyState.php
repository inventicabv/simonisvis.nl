<?php
/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2024 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
$image2x  = Uri::root(true) . '/media/com_easystore/images/migration-failed-2x.webp';
$image1x  = Uri::root(true) . '/media/com_easystore/images/migration-failed.webp';
?>
<div class="com-easystore view-collection view-singular-details <?php echo $this->pageclass_sfx; ?>" style="margin-block: 4rem;">
	<h1 class="easystore-view-singular-title">Error!</h2>
    <p>It appears that you are using SP Page Builder to display the collection page, but the page has not been properly configured yet.</p>
    <p>To resolve this issue, please follow these steps:</p>
    <ol>
        <li>Navigate to <code style="background-color: #f0f0f0; padding: 2px 4px; border-radius: 4px; font-family: monospace; color: #333;">Components > SP Page Builder Pro > EasyStore > Collection</code> in your Joomla administrator panel.</li>
        <li>If you want to use the storefront page as the collection page, configure <code style="background-color: #f0f0f0; padding: 2px 4px; border-radius: 4px; font-family: monospace; color: #333;">Components > SP Page Builder Pro > EasyStore > Storefront</code> and then unpublish the Collections page.</li>
        <li>Edit the Collection page.</li>
        <li>Add the <code style="background-color: #f0f0f0; padding: 2px 4px; border-radius: 4px; font-family: monospace; color: #333;">Product List</code> addon to the page to display your collection items.</li>
        <li>Configure the addon settings as needed for your collection display.</li>
        <li>Save and publish the page.</li>
    </ol>
    <p>If you need more detailed instructions, please refer to our <a href="https://www.joomshaper.com/documentation/easystore/collections" target="_blank" rel="noopener noreferrer">comprehensive documentation</a>.</p>
    <p>
        <a href="<?php echo Uri::root(); ?>" class="btn btn-primary btn-xs" style="margin-left: -16px; padding: 4px 8px;">
            <i class="fas fa-arrow-left"></i>
            <?php echo Text::_('COM_EASYSTORE_BACK_TO_HOMEPAGE'); ?>
        </a>
    </p>
</div>
