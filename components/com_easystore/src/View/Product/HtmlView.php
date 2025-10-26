<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Site\View\Product;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use JoomShaper\Component\EasyStore\Administrator\Concerns\HasMetadata;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Site\Traits\ProductStructuredData;

/**
 * View for the product of the EasyStore component
 */
class HtmlView extends BaseHtmlView
{
    use ProductStructuredData;
    use HasMetadata;

    /**
     * The product object
     *
     * @var  \stdClass
     */
    protected $item;

    /**
     * The page parameters
     *
     * @var    \Joomla\Registry\Registry
     *
     * @since  1.2.0
     */
    protected $params;

    /**
     * The model state
     *
     * @var   \Joomla\CMS\Object\CMSObject
     */
    protected $state;

    /**
     * The page class suffix
     *
     * @var    string
     *
     * @since  4.0.0
     */
    protected $pageclass_sfx = '';

    /**
     * Display the view
     *
     * @param   string  $tpl  The name of the layout file to parse.
     * @return  void
     */
    public function display($tpl = null)
    {
        // Get the application and current user
        $app = Factory::getApplication();

        // Assign various data to the object properties
        $this->item  = $this->get('Item');
        $this->print = $app->getInput()->getBool('print', false);
        $this->state = $this->get('State');

        // Check for errors and throw an exception if found
        if (is_null($this->item)) {
            throw new \Exception(Text::_('COM_EASYSTORE_PRODUCT_NO_PRODUCT_FOUND'), 404);
        }

        // Retrieve data from SP Page Builder
        $this->pageBuilderData = EasyStoreHelper::getPageBuilderData('single');

        // Include application helper if page builder data is available
        if ($this->pageBuilderData) {
            $helperPath = JPATH_ROOT . '/components/com_sppagebuilder/helpers/helper.php';

            if (!class_exists('SppagebuilderHelperSite')) {
                require_once $helperPath;
            }
        }

        // Check for errors and throw an exception if found
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        // Prepare product data for loadmodule and active menu params
        EasyStoreHelper::prepareProductData($this->item);

        $this->params = ComponentHelper::getParams('com_easystore');

        $this->_prepareDocument();

        // Product Structured Data
        $this->generateProductStructuredData($this->item);

        // Call the parent display method
        parent::display($tpl);
    }

    /**
     * Prepares the document.
     *
     * @return  void
     */
    protected function _prepareDocument()
    {
        /** @var CMSApplication */
        $app     = Factory::getApplication();
        $pathway = $app->getPathway();

        /**
         * Because the application sets a default page title,
         * we need to get it from the menu item itself
         */
        $menu = $app->getMenu()->getActive();

        // Get ID of the category from active menu item
        if (
            $menu && $menu->component == 'com_easystore' && isset($menu->query['view'])
            && in_array($menu->query['view'], ['products', 'product'])
        ) {
            $catid = $menu->query['catid'];
        } else {
            $catid = 0;
        }

        $categoryPathData = EasyStoreHelper::getCategoryPath($this->item->catid);

        if (!$catid) {
            // Check if detailedPath exists and is not an empty array
            if (isset($categoryPathData['detailedPath']) && !empty($categoryPathData['detailedPath'])) {
                $categoryPath = array_reverse($categoryPathData['detailedPath'], true);

                foreach ($categoryPath as $categoryId => $category) {
                    $pathway->addItem(
                        $category['title'],
                        'index.php?option=com_easystore&view=products&catid=' . $categoryId
                    );
                }
            }
        }

        $this->implementMetadata($this->item, $this->params);
    }
}
