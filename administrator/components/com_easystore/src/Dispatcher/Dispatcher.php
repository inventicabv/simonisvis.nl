<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Dispatcher;

use Joomla\CMS\Dispatcher\ComponentDispatcher;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * ComponentDispatcher class for com_easystore
 *
 * @since  1.0.3
 */
class Dispatcher extends ComponentDispatcher
{
    /**
     * Method to check component access permission
     *
     * @return  void
     *
     * @since 1.0.3
     */
    protected function checkAccess()
    {
    }
}
