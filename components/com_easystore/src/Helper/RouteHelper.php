<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Helper;

use Joomla\CMS\Language\Multilanguage;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Easy Store Component Route Helper
 *
 * @since  1.5
 */
abstract class RouteHelper
{
    /**
     * getProductRoute
     *
     * @param   int  $id        menu itemid
     * @param   int  $catid     category id
     * @param   int  $language  language
     *
     * @return string
     */
    public static function getProductRoute($id, $language = 0)
    {
        // Create the link
        $link = 'index.php?option=com_easystore&view=product&id=' . $id;

        if ($language && $language !== '*' && Multilanguage::isEnabled()) {
            $link .= '&lang=' . $language;
        }

        return $link;
    }
}
