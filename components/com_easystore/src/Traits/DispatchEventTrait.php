<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Site\Traits;

use Joomla\CMS\Factory;
use Joomla\Event\Event;

/**
 * Trait for dispatching events in EasyStore components
 */
trait DispatchEventTrait
{
    /**
     * Dispatch a custom event
     *
     * @param   string  $eventName  The name of the event to dispatch
     * @param   array   $arguments  Optional arguments to pass to the event
     * @return  object  The modified subject
     * 
     * @since   1.7.0
     */
    protected function dispatchEasyStoreEvent($eventName, array $arguments = [])
    {
        $arguments = array_merge(['subject' => $this], $arguments);
        $event = new Event($eventName, $arguments);

        try {
            $dispatcher = Factory::getApplication()->getDispatcher();
            $dispatcher->dispatch($eventName, $event);
            return $event;
        } catch (\Throwable $th) {
            Factory::getApplication()->enqueueMessage($th->getMessage());
            return $this;
        }
    }
}