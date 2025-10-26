<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Site\View\Products;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View for the products list of the EasyStore component
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The list of products
     *
     * @var    mixed
     * @since  1.0.0
     */
    protected $items;

    /**
     * Pagination object
     *
     * @var    \Joomla\CMS\Pagination\Pagination
     * @since  1.0.0
     */
    protected $pagination;

    /**
     * @var    \Joomla\Registry\Registry
     * @since  1.2.0
     */
    protected $params;

    /**
     * Display the view
     *
     * @param   string  $template  The name of the layout file to parse.
     * @return  void
     */
    public function display($template = null)
    {
        /** @var CMSApplication */
        $app = Factory::getApplication();

        // Get some data from the models
        $this->items = [];

        $pagination = $this->get('Pagination');

        // Retrieve data from SP Page Builder
        $this->pageBuilderData = EasyStoreHelper::getPageBuilderData('storefront');

        // Include application helper if page builder data is available
        if ($this->pageBuilderData) {
            $helperPath = JPATH_ROOT . '/components/com_sppagebuilder/helpers/helper.php';

            if (file_exists($helperPath) && !function_exists('SppagebuilderHelperSite')) {
                require_once $helperPath;
            }

        } else {
            $this->items = $this->get('Items');
        }

        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->params = ComponentHelper::getParams('com_easystore');

        $active = $app->getMenu()->getActive();

        if ($active && $active->component === 'com_easystore' && $active->query['view'] === 'products') {
            $temp       = clone $this->params;
            $menuParams = $active->getParams();
            $temp->merge($menuParams);
            $this->params = $temp;
        }

        $this->params->merge($app->getParams());

        $pagination->hideEmptyLimitstart = true;
        $this->pagination                = &$pagination;

        foreach ($this->get('Filters') as $filter) {
            $value = $app->getInput()->get($filter, '', 'STRING');
            if (!empty($value)) {
                $this->pagination->setAdditionalUrlParam($filter, $value);
            }
        }

        $this->_prepareDocument();

        // Call the parent display method
        parent::display($template);
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
        $catid   = (int) $app->input->getInt('catid', 0, 'INT'); // @todo: find a better way
        $pathway = $app->getPathway();

        /**
         * Because the application sets a default page title,
         * we need to get it from the menu item itself
         */
        $menu = $app->getMenu()->getActive();

        // Get ID of the category from active menu item
        if (
            $menu && $menu->component == 'com_easystore' && isset($menu->query['view'])
            && in_array($menu->query['view'], ['products'])
        ) {
            $catid = $menu->query['catid'] ?? 0;
        } else {
            $catid = 0;
        }

        $categoryPathData = EasyStoreHelper::getCategoryPath($catid);

        // Check if detailedPath exists and is not an empty array
        if (!$catid) {
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

        if ($menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        } else {
            $this->params->def('page_heading');
        }

        $title = $this->params->def('page_title');

        $this->setDocumentTitle($title);

        if ($this->params->get('menu-meta_description')) {
            $this->getDocument()->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->params->get('menu-meta_keywords')) {
            $this->getDocument()->setMetaData('keywords', $this->params->get('menu-meta_keywords'));
        }

        if ($this->params->get('robots')) {
            $this->getDocument()->setMetaData('robots', $this->params->get('robots'));
        }
    }
}
