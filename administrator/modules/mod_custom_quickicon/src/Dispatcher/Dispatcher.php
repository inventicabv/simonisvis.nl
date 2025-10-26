<?php
/*
 *  package: Custom-Quickicons
 *  copyright: Copyright (c) 2024. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 2 or later
 *  link: https://www.joomill-extensions.com
 */

namespace Joomill\Module\Customquickicon\Administrator\Dispatcher;

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;

/**
 * Dispatcher class for mod_custom_quickicon
 *
 * @since  1.0.0
 */
class Dispatcher extends AbstractModuleDispatcher
{
    /**
     * Returns the layout data.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    protected function getLayoutData()
    {
        $data = parent::getLayoutData();

        $helper = $this->app->bootModule('mod_custom_quickicon', 'administrator')->getHelper('CustomQuickIconHelper');
        $data['buttons'] = $helper->getButtons($data['params'], $this->getApplication());
        return $data;
    }
}
