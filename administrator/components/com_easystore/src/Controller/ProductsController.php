<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Folder;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use JoomShaper\Component\EasyStore\Administrator\Traits\Export;
use JoomShaper\Component\EasyStore\Administrator\Traits\Import;
use JoomShaper\Component\EasyStore\Administrator\Model\ProductModel;
use JoomShaper\Component\EasyStore\Administrator\Supports\AccessControl;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreMediaHelper;

/**
 * Products list controller class.
 *
 * @since  1.0.0
 */
class ProductsController extends AdminController
{
    use Export;
    use Import;

    /**
     * The Joomla application instance
     *
     * @var CMSApplication
     */
    protected $app;

    public function __construct($config = [], ?MVCFactoryInterface $factory = null, $app = null, $input = null)
    {
        parent::__construct($config, $factory, $app, $input);

        $this->registerTask('unfeatured', 'featured');
    }

    /**
     * Proxy for getModel.
     *
     * @param   string  $name    The name of the model.
     * @param   string  $prefix  The prefix for the PHP class name.
     * @param   array   $config  Array of configuration parameters.
     *
     * @return  ProductModel
     *
     * @since   1.0.0
     */
    public function getModel($name = 'Product', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function featured()
    {
        // Check for request forgeries
        $this->checkToken();

        $user        = $this->app->getIdentity();
        $ids         = (array) $this->input->get('cid', [], 'int');
        $values      = ['featured' => 1, 'unfeatured' => 0];
        $task        = $this->getTask();
        $value       = ArrayHelper::getValue($values, $task, 0, 'int');
        $redirectUrl = 'index.php?option=com_easystore&view=' . $this->view_list . $this->getRedirectToListAppend();

        foreach ($ids as $i => $id) {
            if ($id === 0) {
                unset($ids[$i]);

                continue;
            }
        }

        if (empty($ids)) {
            $this->app->enqueueMessage(Text::_('JERROR_NO_ITEMS_SELECTED'), 'error');

            $this->setRedirect(Route::_($redirectUrl, false));

            return;
        }

        // Get the model.
        /** @var ProductModel */
        $model = $this->getModel();

        // Publish the items.
        if (!$model->featured($ids, $value)) {
            $this->setRedirect(Route::_($redirectUrl, false), $model->getError(), 'error');

            return;
        }

        if ($value == 1) {
            $message = Text::plural('COM_EASYSTORE_N_ITEMS_FEATURED', count($ids));
        } else {
            $message = Text::plural('COM_EASYSTORE_N_ITEMS_UNFEATURED', count($ids));
        }

        $this->setRedirect(Route::_($redirectUrl, false), $message);
    }

    /**
     * Removes an item.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function delete()
    {
        // Check for request forgeries
        $this->checkToken();
        $acl = AccessControl::create();

        if (!$acl->canDelete()) {
            Factory::getApplication()->enqueueMessage(Text::_('JERROR_CORE_DELETE_NOT_PERMITTED'), 'error');
            return;
        }

        // Get items to remove from the request.
        $cid = (array) $this->input->get('cid', [], 'int');

        // Remove zero values resulting from input filter
        $cid = array_filter($cid);

        if (empty($cid)) {
            $this->app->getLogger()->warning(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), ['category' => 'jerror']);
            return;
        }

        $model = $this->getModel();

        // Remove the items.
        if ($model->delete($cid)) {
            $baseFolder = EasyStoreMediaHelper::checkForMediaActionBoundary(JPATH_ROOT . '/images/easystore/product-');
            if (is_array($cid)) {
                foreach ($cid as $id) {
                    $folderName = $baseFolder . $id;
                    if (is_dir($folderName)) {
                        Folder::delete($folderName);
                    }
                }
            } else {
                $folderName = $baseFolder . $cid;
                if (is_dir($folderName)) {
                    Folder::delete($folderName);
                }
            }

            $this->setMessage(Text::plural($this->text_prefix . '_N_ITEMS_DELETED', \count($cid)));
        } else {
            $this->setMessage($model->getError(), 'error');
        }

        // Invoke the postDelete method to allow for the child class to access the model.
        $this->postDeleteHook($model, $cid);
        $this->setRedirect(Route::_("index.php?option={$this->option}&view={$this->view_list}{$this->getRedirectToListAppend()}", false));
    }

    /**
     * Override the publish function to trash product
     * @return void
     */
    public function publish()
    {
        $task = $this->input->get('task', '', 'string');

        if ($task === 'trash') {
            $cid                 = (array) $this->input->get('cid', [], 'int');
            $canDelete           = true;
            $deleteCount         = count($cid);
            $orderedProductCount = 0;
            $newOrderIds         = [];
            $orderedIdsString    = "";

            foreach ($cid as $id) {
                if (ProductModel::isProductExistsInOrder($id)) {
                    $orderedProductCount++;
                    $orderedIdsString .= $id . ',';
                } else {
                    $newOrderIds[] = $id;
                }
            }

            if ($deleteCount == $orderedProductCount) {
                $canDelete = false;
            }

            if ($canDelete) {
                if ($orderedProductCount > 0) {
                    $this->input->set('cid', $newOrderIds); // Set the cid again
                }

                parent::publish();

                if ($orderedProductCount > 0) {
                    $this->app->getLogger()->warning(Text::plural('COM_EASYSTORE_N_PRODUCTS_WITH_ORDER_CANNOT_DELETE', $orderedProductCount, rtrim($orderedIdsString, ',')), ['category' => 'jerror']);
                }
            } else {
                $this->app->getLogger()->warning(Text::_('COM_EASYSTORE_PRODUCTS_WITH_ORDER_CANNOT_DELETE'), ['category' => 'jerror']);
                $this->setRedirect(
                    Route::_(
                        'index.php?option=' . $this->option . '&view=' . $this->view_list
                        . $this->getRedirectToListAppend(),
                        false
                    )
                );
            }
        } else {
            parent::publish();
        }
    }
}
