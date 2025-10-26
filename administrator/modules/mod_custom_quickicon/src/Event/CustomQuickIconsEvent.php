<?php
/*
 *  package: Custom-Quickicons
 *  copyright: Copyright (c) 2024. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 2 or later
 *  link: https://www.joomill-extensions.com
 */

namespace Joomill\Module\Customquickicon\Administrator\Event;

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Event\AbstractEvent;

/**
 * Event object for retrieving pluggable quick icons
 *
 * @since  1.0.0
 */
class CustomQuickIconsEvent extends AbstractEvent
{
	/**
	 * The event context
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	private $context;

    /**
     * Get the event context
     *
     * @return  string
     *
     * @since   1.0.0
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set the event context
     *
     * @param string $context The event context
     *
     * @return  string
     *
     * @since   1.0.0
     */
    public function setContext($context)
    {
        $this->context = $context;

        return $context;
    }
}
