<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Controller;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Controller\BaseController;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Site\Traits\Api;
use JoomShaper\Component\EasyStore\Site\Traits\Cart;
use JoomShaper\Component\EasyStore\Site\Traits\Token;
use JoomShaper\Component\EasyStore\Site\Model\CartModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Class that handle all of the EasyStore component cart functionality.
 *
 * @since  1.0.0
 */
class CartController extends BaseController
{
    use Api;
    use Token;
    use Cart;

    /**
     * Method to get all the cart items.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function getCartData()
    {

        /** @var CartModel $model */
        $model = $this->getModel('Cart');
        list($country, $state) = EasyStoreHelper::getCountryAndState();

        $item  = $model->getItem(null, $country, $state);

        if (!empty($item)) {
            $item->sub_total_with_taxable_amount = $item->sub_total + $item->taxable_amount;
            $item->sub_total_with_taxable_amount = (float) bcdiv($item->sub_total_with_taxable_amount, 1, 2);
            $item->sub_total_with_taxable_amount_with_currency = EasyStoreHelper::formatCurrency($item->sub_total_with_taxable_amount);
        }

        $this->sendResponse($item);
    }

    /**
     * Method to get cart content for drawer.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function getCartDrawerContent()
    {
        /** @var CartModel $model */
        $model = $this->getModel('Cart');
        list($country, $state) = EasyStoreHelper::getCountryAndState();
        $item        = $model->getItem(null, $country, $state);
        $output      = LayoutHelper::render('cart.mini', ['items' => $item ?? []]);
        $token       = $this->getToken();
        $checkoutUrl = Route::_('index.php?option=com_easystore&view=checkout&cart_token=' . $token, false);

        $this->sendResponse(['content' => $output, 'checkout_url' => $checkoutUrl]);
    }

    /**
     * Method to remove the cart item data.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function removeCartData()
    {
        /**
         * @var CartModel $model
         */
        $model = $this->getModel('Cart');

        try {
            $model->removeCartData();
        } catch (\Exception $e) {
            $this->sendResponse(["message" => $e->getMessage()], 403);
        }
    }

    /**
     * Method to clear the cart.
     *
     * @return void
     *
     * @since 1.5.0
     */
    public function clearCart()
    {
        /**
         * @var CartModel $model
         */
        $model = $this->getModel('Cart');

        try {
            $model->removeCartData();
            $this->sendResponse(["message" => 'Cart cleared successfully', 'status' => 200]);
        } catch (\Exception $e) {
            $this->sendResponse(["message" => $e->getMessage(), 'status' => 403]);
        }
    }
}
