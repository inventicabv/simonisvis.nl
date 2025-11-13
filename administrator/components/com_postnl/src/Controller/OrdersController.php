<?php

/**
 * @package     COM_POSTNL
 * @subpackage  Controller
 *
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace Simonisvis\Component\PostNL\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

/**
 * Orders controller class for PostNL component.
 *
 * @since  1.0.0
 */
class OrdersController extends BaseController
{
    /**
     * Refresh the orders list view
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function refresh(): void
    {
        // Simply redirect back to the orders view
        $this->setRedirect(
            Route::_('index.php?option=com_postnl&view=orders', false)
        );
    }
}

