<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


use Joomla\CMS\MVC\Controller\FormController;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;

/**
 * Controller for a single review
 *
 * @since  1.0.0
 */
class ReviewController extends FormController
{
    /**
     * Method to check if you can add a new record.
     *
     * @param   array  $data  An array of input data.
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    protected function allowAdd($data = [])
    {
        $acl = AccessControl::create();
        return $acl->canCreate();
    }

    /**
     * Method to check if you can edit a record.
     *
     * @param   array   $data  An array of input data.
     * @param   string  $key   The name of the key for the primary key.
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    protected function allowEdit($data = [], $key = 'id')
    {
        $acl = AccessControl::create();
        return $acl->canEdit() || $acl->setContext('review')->canEditOwn($data[$key]);
    }
}
