<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Plugin;

use Joomla\Event\Event;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\DispatcherInterface;
use Joomla\CMS\Application\CMSApplication;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


/**
 * MigrationPlugin is an abstract class that extends CMSPlugin for implementing different types of
 * migrations
 *
 * @since 1.0.0
 */

abstract class MigrationPlugin extends CMSPlugin
{
    /**
     * The application object
     *
     * @var CMSApplication
     *
     * @since 1.0.0
     */
    protected $app;

    /**
     * Constructor
     *
     * @param   DispatcherInterface  $dispatcher  The event dispatcher
     * @param   array                $config      An optional associative array of configuration settings.
     *                                            Recognized key values include 'name', 'group', 'params', 'language'
     *                                            (this list is not meant to be comprehensive).
     *
     * @since   1.0.0
     */
    public function __construct(DispatcherInterface $dispatcher, array $config = [])
    {
        parent::__construct($dispatcher, $config);
    }

    /**
     * Migrate Settings Event. This event is triggered to migrate the settings data to easystore.
     *
     * @param Event $event The application event we are handling.
     *
     * @return array JSON string
     *
     * @since 1.0.0
     */
    abstract public function migrateSettings(Event $event);
}
