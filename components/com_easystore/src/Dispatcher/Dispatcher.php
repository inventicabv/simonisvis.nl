<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Dispatcher;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Dispatcher\ComponentDispatcher;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * ComponentDispatcher class for com_easystore
 *
 * @since  1.0.0
 */
class Dispatcher extends ComponentDispatcher
{
    /**
     * Dispatch a controller task. Redirecting the user if appropriate.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function dispatch()
    {
        EasyStoreHelper::wa()->useStyle('com_easystore.cart.drawer.site')
            ->useStyle('com_easystore.site')
            ->useStyle('com_easystore.cart.site')
            ->useScript('com_easystore.alpine.site');

        /** Load the SP Page Builder classes if the page builder is installed and enabled. */
        if (ComponentHelper::isEnabled('com_sppagebuilder')) {
            if (!class_exists('BuilderAutoload')) {
                require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/autoload.php';
            }

            \BuilderAutoload::loadClasses();
            \BuilderAutoload::loadHelperClasses();
            \BuilderAutoload::loadGlobalAssets();
        }

        $input = Factory::getApplication()->input;

        if ($input->get('view') === 'payment' && $input->get('type') === 'authorizenet' && !in_array($input->get('layout'), ['success','cancel'])) {
            $input->set('task', 'payment.onPaymentNotify');
        }

        parent::dispatch();
    }
}
