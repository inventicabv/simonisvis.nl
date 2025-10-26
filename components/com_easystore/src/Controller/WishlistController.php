<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Controller;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Controller\BaseController;
use JoomShaper\Component\EasyStore\Site\Traits\Api;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreDatabaseOrm;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Profile Controller of EasyStore component
 *
 * @since  1.0.0
 */
class WishlistController extends BaseController
{
    use Api;

    /**
     * Function to add/remove product wish list
     *
     * @since 1.0.0
     * @return void
     */
    public function addOrRemoveWishList()
    {
        $data         = new \stdClass();
        $input        = $this->app->getInput();
        $user         = $this->app->getIdentity();
        $loginUserId  = $user->id;
        $productId    = $input->post->get('productId', '', 'INT');
        $wishListId   = $input->post->get('wishListId', 0, 'INT');
        $action       = $input->post->get('action', '', 'STRING');
        $return       = $input->post->get('return', '', 'STRING');


        /** @var CMSApplication */
        $app  = Factory::getApplication();
        $user = $app->getIdentity();

        if ($user->guest) {
            $loginUrl = Route::_('index.php?option=com_users&view=login&return=' . base64_encode($return), false);

            $this->sendResponse(['redirect' => $loginUrl], 303);
        }

        $extraCondition = [
            [
                'key'      => 'user_id',
                'operator' => '=',
                'value'    => $loginUserId,
            ],
        ];

        try {
            if ($action === 'remove') {
                EasyStoreDatabaseOrm::removeByIds('#__easystore_wishlist', [$productId], 'product_id', $extraCondition);

                $data->text        = Text::_('COM_EASYSTORE_PRODUCT_ADD_TO_WISHLIST');
                $data->icon        = EasyStoreHelper::getIcon('heart-o');
                $data->activeClass = '';
            } else {
                EasyStoreDatabaseOrm::updateOrCreate('#__easystore_wishlist', (object)['id' => $wishListId,'product_id' => $productId, 'user_id' => $loginUserId]);

                $data->text        = Text::_('COM_EASYSTORE_PRODUCT_ADDED_TO_WISHLIST');
                $data->icon        = EasyStoreHelper::getIcon('heart');
                $data->activeClass = ' active';
            }

            $this->sendResponse($data);
        } catch (Exception $error) {
            $this->sendResponse(['message' => $error->getMessage()], 500);
        }
    }
}
