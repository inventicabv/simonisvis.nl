<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   Copyright (C) 2023 - 2025 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Component\EasyStore\Site\View\Collection;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use JoomShaper\Component\EasyStore\Administrator\Concerns\HasMetadata;
use JoomShaper\Component\EasyStore\Administrator\Constants\ProductListSource;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Site\Model\ProductsModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * HTML View class for the Easystore component
 *
 * @since   1.4.0
 */
class HtmlView extends BaseHtmlView
{
	use HasMetadata;

	/**
	 * The model state
	 *
	 * @var     object
	 * @since   1.4.0
	 */
	protected $state;

	/**
	 * The collection item
	 *
	 * @var     object
	 * @since   1.4.0
	 */
	protected $item;

	/**
	 * The product items associated with the collection
	 *
	 * @var array
	 * @since 1.4.0
	 */
	protected $items;

	/**
	 * The current user instance
	 *
	 * @var    \JUser|null
	 * @since   1.4.0
	 */
	protected $user = null;

	/**
	 * The page class suffix
	 *
	 * @var    string
	 * @since   1.4.0
	 */
	protected $pageclass_sfx = '';

	/**
	 * The page parameters
	 *
	 * @var    \Joomla\Registry\Registry|null
	 * @since   1.4.0
	 */
	protected $params;

	/**
	 * The page builder data
	 *
	 * @var    object|null
	 * @since  1.4.0
	 */
	protected $pageBuilderData = null;

	/**
	 * The collection page builder page
	 *
	 * @var    object|null The possible values are 'collection' and 'storefront'
	 * @since  1.4.0
	 */
	protected $collectionPage = null;

	/**
	 * Check if the collection or the storefront page configured or not.
	 * If the pages are created and published but did not add any content to them then this flag will be true.
	 *
	 * @var    boolean
	 * @since  1.4.0
	 */
	protected $isEmptyPage = false;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 *
	 * @since   1.4.0
	 */
	public function display($tpl = null)
	{
		// Retrieve data from SP Page Builder
		$collectionPage = EasyStoreHelper::getPageBuilderData('collection');
		$storeFrontPage = EasyStoreHelper::getPageBuilderData('storefront');

		$this->pageBuilderData = null;
		$this->item = $this->get('Item');

		/**
		 * If collection page is configured and published then use it, otherwise use the storefront page.
		 * If both are not available then use the default products page.
		 *
		 * @since 1.4.0
		 */
		if (!empty($collectionPage)) {
			$this->pageBuilderData = $collectionPage;
			$this->collectionPage = 'collection';
		} elseif (!empty($storeFrontPage)) {
			$this->pageBuilderData = $storeFrontPage;
			$this->collectionPage = 'storefront';
		}

		// Include application helper if page builder data is available
        if ($this->pageBuilderData) {

			if (empty($this->pageBuilderData->content)) {
				$this->isEmptyPage = true;
			}

			/**
			 * Populate the products model's state with the required attributes
			 * to get the products of a collection
			 *
			 * @since 1.4.0
			 */
			$input = Factory::getApplication()->input;
			$model = new ProductsModel();
			$model->setState(
				'attr',
				array_merge(
					$model->getState('attr', []),
					[
						'source' => ProductListSource::COLLECTION,
						'collection_id' => $input->getInt('id', 0)
					]
				)
			);

            $helperPath = JPATH_ROOT . '/components/com_sppagebuilder/helpers/helper.php';

            if (file_exists($helperPath) && !function_exists('SppagebuilderHelperSite')) {
                require_once $helperPath;
            }
        } else {
            $this->items      = $this->get('Item')->products ?? [];
            $this->pagination = $this->get('Pagination');
        }

        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->params = ComponentHelper::getParams('com_easystore');

        /** @var CMSApplication */
        $app          = Factory::getApplication();
        $active       = $app->getMenu()->getActive();

        if ($active && $active->component === 'com_easystore' && $active->query['view'] === 'collection') {
            $temp       = clone $this->params;
            $menuParams = $active->getParams();
            $temp->merge($menuParams);
            $this->params = $temp;
        }

		$this->params->merge($app->getParams());

		if (!is_null($this->collectionPage) && $this->isEmptyPage) {
			$this->setLayout('emptyState');
			parent::display($tpl);
			return;
		}

		// Finally link the template path to the products view
		$this->addTemplatePath(JPATH_ROOT . '/components/com_easystore/tmpl/products');
		$this->_prepareDocument();

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
        $this->implementMetadata($this->item, $this->params);
    }
}
