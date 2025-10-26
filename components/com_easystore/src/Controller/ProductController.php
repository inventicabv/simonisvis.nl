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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Controller\BaseController;
use JoomShaper\Component\EasyStore\Site\Traits\Api;
use JoomShaper\Component\EasyStore\Site\Model\ProductModel;
use JoomShaper\Component\EasyStore\Site\Model\ProductsModel;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper as SiteHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Default Controller of EasyStore component
 *
 * @since  1.0.0
 */
class ProductController extends BaseController
{
    use Api;

    /**
     * Add Review to database
     * @since  1.0.0
     * @return void
     */
    public function addReview()
    {
        $input     = Factory::getApplication()->input;
        $response  = ['status' => 'error', 'user' => false];
        $userID    = Factory::getApplication()->getIdentity()->id;
        $productID = $input->post->get('productId', '', 'INT');

        if ($userID) {
            $data = [
                'rating'     => $input->post->get('easystore-ratings', '', 'INT'),
                'subject'    => $input->post->get('easystore-title', '', 'STRING'),
                'review'     => $input->post->get('easystore-message', '', 'STRING'),
                'product_id' => $productID,
                'created_by' => $userID,
            ];

            $model  = new ProductModel();

            $orderFullfillment = $model->getOrderFullfillment($productID, $userID);
            $isOrderFullfilled = !is_null($orderFullfillment) ? in_array('fulfilled', array_column($orderFullfillment, 'fulfilment')) : false;

            $data['published'] = ($isOrderFullfilled) ? 1 : 0;

            $result = $model->insertDataToDB($data);

            $response = ($result) ? ['status' => 'success', 'user' => true] : ['status' => 'error', 'user' => true];

            EasyStoreHelper::setFlash(Text::_('COM_EASYSTORE_PRODUCT_REVIEW_ADDED'), $response['status']);
        }

        echo json_encode($response);
        die;
    }

    /**
     * Method to view the quick cart modal.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function quickAddModal()
    {
        $input   = Factory::getApplication()->input;
        $id      = $input->get('id', 0, 'INT');
        $model   = new ProductModel();
        $product = $model->getItem($id);

        $this->sendResponse(LayoutHelper::render('cart.quick', ['product' => $product, 'origin' => 'quick-cart']));
    }

    /**
     * Method to get the product variant data for quick modal.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function contentByVariant()
    {
        /** @var CMSApplication */
        $app   = Factory::getApplication();
        $input = $app->input;

        $sectionId = $input->get('section_id', '', 'STRING');
        $productId = $input->get('product_id', null, 'INT');
        $variantId = $input->get('variant_id', null, 'INT');

        $model         = new ProductModel();
        $productsModel = new ProductsModel();
        $model->setState('variant_id', $variantId);
        $productsModel->setState('product_id', $productId);
        $product = $model->getItem($productId);

        if ($sectionId === 'quick-modal') {
            $this->sendResponse([
                'section' => '.modal-content',
                'content' => SiteHelper::loadLayout('cart.quick', ['product' => $product, 'origin' => 'quick-cart']),
            ]);
        }

        // Prepare product data for loadmodule and active menu params
        SiteHelper::prepareProductData($product);

        $page = SiteHelper::getPageBuilderData('single');

        if ($page) {
            $helperPath = JPATH_ROOT . '/components/com_sppagebuilder/helpers/helper.php';
            $parserPath = JPATH_ROOT . '/components/com_sppagebuilder/parser/addon-parer.php';

            if (!class_exists('SppagebuilderHelperSite')) {
                require_once $helperPath;
            }

            if (!class_exists('AddonParser')) {
                require_once $parserPath;
            }

            $pageContent = \SppagebuilderHelperSite::initView($page);
            $content     = \AddonParser::viewAddons($pageContent, 0, 'page-' . $page->id, 1, true, ['easystoreItem' => $product, 'easystoreList' => []]);

            $this->sendResponse([
                'section' => '#easystore-product-detail-sppb',
                'content' => $content,
            ]);
        } else {
            $this->sendResponse([
                'section' => '#easystore-product-detail-default',
                'content' => SiteHelper::loadLayout('product.default', ['item' => $product]),
            ]);
        }
    }
}
