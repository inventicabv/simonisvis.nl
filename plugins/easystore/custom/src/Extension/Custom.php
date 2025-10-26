<?php

/**
 * @package     EasyStore.Site
 * @subpackage  EasyStore.Custom
 *
 * @copyright   Copyright (C) 2023 - 2024 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Plugin\EasyStore\Custom\Extension;

use Joomla\Event\Event;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use JoomShaper\Plugin\EasyStore\Custom\Utils\CustomConstant;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class Custom extends CMSPlugin implements SubscriberInterface
{
    /**
     * function for getSubscribedEvents : new Joomla 4 feature
     *
     * @return array
     *
     * @since   1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforePayment' => 'onBeforePayment'
        ];
    }

    /**
     * Check if all the required fields for the plugin are filled.
     *
     * @return void The result of the check, indicating whether the required fields are filled.
     * @since  1.0.0
     */
    public function onBeforePayment(Event $event)
    {
        $constants              = new CustomConstant();
        $isRequiredFieldsFilled = !empty($constants->getAdditionalInformation());

        $event->setArgument('result', $isRequiredFieldsFilled);
    }
}
