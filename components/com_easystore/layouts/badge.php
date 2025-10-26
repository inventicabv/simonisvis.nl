<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

use Joomla\CMS\Language\Text;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper as AdminEasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

extract($displayData);

$text = '';

if ($item->has_sale) {
    $number = ($item->discount_type === 'percent')
        ? $item->discount_value . '%'
        : AdminEasyStoreHelper::getCurrencySymbol() . $item->discount_value;

    $text = strtoupper('COM_EASYSTORE_ADDON_BADGE_' . $badge_text);

    if ($badge_text === 'off') {
        $text = Text::sprintf($text, $number);
    } elseif ($badge_text === 'custom' && !empty($custom_text)) {
        $text = $custom_text;
    } else {
        $text = Text::_($text);
    }
}

$isFeatured = !empty($item->featured) ? true : false;

?>
<div class="easystore-badge-wrapper">
    <?php if ($sale && !empty($text)) : ?>
        <span class="easystore-badge is-sale"><?php echo htmlspecialchars($text); ?></span>
    <?php endif; ?>

    <?php if ($isFeatured) : ?>
        <?php if ($featured && !empty($featured_text)) : ?>
            <span class="easystore-badge is-featured"><?php echo htmlspecialchars($featured_text); ?></span>
        <?php endif; ?>
    <?php endif; ?>
</div>