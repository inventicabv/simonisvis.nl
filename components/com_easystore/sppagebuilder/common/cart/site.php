<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2024 JoomShaper <https://www.joomshaper.com> . All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

class SppagebuilderAddonEasystoreCommonCart extends SppagebuilderAddons
{
    public function render()
    {
        return EasyStoreHelper::loadLayout('cart.icon');
    }
}
