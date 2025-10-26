<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Controller\BaseController;
use JoomShaper\Component\EasyStore\Site\Traits\Api;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Default Controller of EasyStore component
 *
 * @since  1.0.0
 */
class DisplayController extends BaseController
{
    use Api;

    /**
     * Displays the view with optional caching and URL parameters.
     *
     * This method handles displaying the view based on the given cachable status and URL parameters.
     * It also checks if the payment gateway status is 'failed' and clears the current cart if so.
     *
     * @param bool $cachable   Optional. Indicates whether the view can be cached (default: false).
     * @param array $urlparams Optional. Additional parameters for the URL (default: empty array).
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function display($cachable = false, $urlparams = [])
    {
        $status = $this->app->input->get('status', '', 'STRING');

        // Check the payment gateway status. if failed clear the current cart.
        if ($status === 'failed') {
            /**
             * @var CartController $cartController Instance of the CartController used to manage cart operations.
             */
            $cartController = $this->factory->createController('Cart', 'Site', [], $this->app, $this->input);

            // Remove cart data
            $cartController->removeCartData();
        }

        /** @var CMSApplication */
        $app = Factory::getApplication();
        $document = $app->getDocument();
        $document->addScriptDeclaration("
            Joomla.Text.sprintf = function (text, ...args) {
                return Joomla.Text._(text).replace(/%s|%d/g, () => args.shift());
            }
        ");


        parent::display($cachable, $urlparams);
    }

    /**
     * Creates and sends a modal response.
     *
     * This method renders a modal using the 'system.modal' layout template and sends the rendered
     * content as a response.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function createModal()
    {
        $this->sendResponse(LayoutHelper::render('system.modal', null));
    }

    /**
     * Creates and sends a drawer response.
     *
     * This method renders a drawer using the 'system.cart_drawer' layout template and sends the rendered
     * content as a response.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function createDrawer()
    {
        $this->sendResponse(LayoutHelper::render('system.cart_drawer', null));
    }
}
