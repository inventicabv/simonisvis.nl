<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

// No direct access
defined ('_JEXEC') or die ('Restricted access');

if (file_exists(JPATH_ROOT . '/administrator/components/com_sppagebuilder/vendor/autoload.php')) {
    require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/vendor/autoload.php';
}

if (file_exists(JPATH_ROOT . '/administrator/components/com_sppagebuilder/dynamic-content/helper.php')) {
	require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/dynamic-content/helper.php';
}

use Joomla\CMS\Factory;
use Joomla\CMS\Version;
use Joomla\CMS\Filter\OutputFilter;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\FieldTypes;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Collection;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionField;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItem;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItemValue;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Page;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\CollectionIds;

class SppagebuilderRouterBase
{
	public static function buildRoute(&$query)
	{
		$segments = array();
		/** @var CMSApplication */
		$app = Factory::getApplication();
		$menu = $app->getMenu();

		// We need a menu item.  Either the one specified in the query, or the current active one if none specified
		if (empty($query['Itemid']))
		{
			$menuItem = $menu->getActive();
		}
		else
		{
			$menuItem = $menu->getItem($query['Itemid']);
		}

		$menuItemGiven = !empty($query['Itemid']);

		// Check again
		if ($menuItemGiven && isset($menuItem) && $menuItem->component !== 'com_sppagebuilder')
		{
			$menuItemGiven = false;
			unset($query['Itemid']);
		}

		if (isset($query['view']) && $query['view'])
		{
			$view = $query['view'];
		}
		else
		{
			// We need to have a view in the query or it is an invalid URL
			return $segments;
		}

		if (($menuItem instanceof stdClass) && $menuItem->query['view'] === $query['view'] && isset($query['id']) && (int) $menuItem->query['id'] === (int) $query['id'])
		{
			unset($query['view']);
			unset($query['id']);

			return $segments;
		}

		if ($query['view'] === "page")
		{
			if (!$menuItemGiven) {
				$segments[] = $view;
				$segments[] = $query['id'] ?? 0;
			}
			unset($query['view']);
			unset($query['id']);
		}

		if ($view === 'dynamic')
		{
			$collectionItemId = $query['collection_item_id'] ?? [];

			if (!is_array($collectionItemId)) {
				$collectionItemId = [$collectionItemId];
			}

			$collectionType = $query['collection_type'] ?? 'normal-source';

			unset($query['collection_item_id']);
			unset($query['collection_type']); // Remove collection_type parameter

			// Always generate alias for SEF URLs, regardless of menu item
			$alias = static::getSlugsByCollectionItemIds($collectionItemId, $collectionType);
			if (!empty($alias)) {
				$segments = array_merge($segments, $alias);
			}
		}

		if (isset($query['view']) && $query['view'])
		{
			unset($query['view']);
		}

		if (isset($query['id']) && $query['id'])
		{
			$id = $query['id'];
			unset($query['id']);
		}

		if(isset($query['tmpl']) && $query['tmpl'])
		{
			unset($query['tmpl']);
		}

		if(isset($query['layout']) && $query['layout'])
		{
			$segments[] = $query['layout'];
			if(isset($id)) {
				$segments[] = $id;
			}
			unset($query['layout']);
		}

		return $segments;
	}

	private static function getCollectionTypeFromAlias($alias)
	{
		if (!\class_exists('SppagebuilderHelperArticles')) {
			require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/articles.php';
		}

		$articlesCount = \SppagebuilderHelperArticles::getArticlesCount();
		$articles = \SppagebuilderHelperArticles::getArticles($articlesCount);
		foreach ($articles as $article) {
			if ($article->alias === $alias) {
				return 'articles';
			}
		}
		
		$db = \Joomla\CMS\Factory::getDbo();
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from('#__tags')
			->where('alias = ' . $db->quote($alias))
			->where('published = 1');
		$db->setQuery($query);
		$tagCount = $db->loadResult();
		
		if ($tagCount > 0) {
			return 'tags';
		}
		
		return 'normal-source';
	}

	// Parse
	public static function parseRoute(&$segments)
	{
		/** @var CMSApplication */
		$app = Factory::getApplication();
		$menu = $app->getMenu();
		$item = $menu->getActive();
		$vars = array();

		// Page
		if (count($segments) === 2 && $segments[0] === 'page')
		{
			$vars['view'] = $segments[0];
			$vars['id'] = (int) $segments[1];

			return $vars;
		}

		// Form
		if (count($segments) === 2 && $segments[0] === 'edit')
		{
			$vars['view'] = 'form';
			$vars['id'] = (int) $segments[1];
			$vars['tmpl'] = 'component';
			$vars['layout'] = 'edit';

			return $vars;
		}

		// determine collection type based on alias
		$collectionType = static::getCollectionTypeFromAlias($segments[0]);

		$collectionItemIds = static::getCollectionItemIdsFromSlugs($segments);

		$isValidCollectionPage = false;

		if (isset($collectionItemIds)) {
			$collectionArray = Arr::make($collectionItemIds);

			$isValidCollectionPage = $collectionArray->every(function ($element) {
				return !empty($element);
			});
		}

		// Dynamic Content
		if (!empty($item) && $item->query['option'] === 'com_sppagebuilder' && $item->query['view'] === 'page' && $isValidCollectionPage) {
			$vars['view'] = 'dynamic';
			if (!empty($collectionItemIds)) {
				foreach ($collectionItemIds as $collectionItemId) {
					$vars['collection_item_id'][] = $collectionItemId;
				}
			}

			$vars['collection_type'] = $collectionType;

			return $vars;
		}

		return $vars;
	}

	private static function getCollectionItemIdsFromSlugs($slugs)
	{
		if (empty($slugs)) {
			return [];
		}

		return Arr::make($slugs)->map(function ($slug) {
			return static::getCollectionItemIdFromSlug($slug);
		})->toArray();
	}

	private static function getCollectionItemIdFromSlug($slug)
	{
		if (!\class_exists('SppagebuilderHelperArticles')) {
			require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/articles.php';
		}

		try {
			$articlesCount = \SppagebuilderHelperArticles::getArticlesCount();
			$articles = \SppagebuilderHelperArticles::getArticles($articlesCount);
			foreach ($articles as $article) {
				$articleAlias = !empty($article->alias) ? $article->alias : OutputFilter::stringURLSafe($article->title);
				if ($articleAlias === $slug) {
					return $article->id;
				}
			}
		} catch (\Exception $e) {
		}

		if (preg_match('/^article-(\d+)$/', $slug, $matches)) {
			return (int) $matches[1];
		}
		
		$db = \Joomla\CMS\Factory::getDbo();
		$query = $db->getQuery(true)
			->select('id')
			->from('#__tags')
			->where('alias = ' . $db->quote($slug))
			->where('published = 1');
		$db->setQuery($query);
		$tagId = $db->loadResult();
		
		if ($tagId) {
			return (int) $tagId;
		}
		
		if (preg_match('/^tag-(\d+)$/', $slug, $matches)) {
			return (int) $matches[1];
		}
				
		$aliasFields = CollectionField::where('type', FieldTypes::ALIAS)->get(['id']);
		$aliasFieldIds = Arr::make($aliasFields)->pluck('id')->toArray();
		
		if (!empty($aliasFieldIds)) {
			$aliasField = CollectionItemValue::whereIn('field_id', $aliasFieldIds)
				->where('value', $slug)
				->first(['item_id']);
			
			if (!$aliasField->isEmpty()) {
				return $aliasField->item_id ?? null;
			}
		}

		return null;
	}

	private static function getSlugsByCollectionItemIds($collectionItemIds, $collectionType = 'normal-source')
	{
		if (empty($collectionItemIds)) {
			return [];
		}

		return Arr::make($collectionItemIds)->map(function ($id) use ($collectionType) {
			return static::getItemAliasByCollectionItemId(static::getCollectionIdFromItemId($id, $collectionType), $id);
		})->toArray();
	}

	private static function getCollectionIdFromItemId($itemId, $collectionType = 'normal-source')
	{
		if ($collectionType === 'articles') {
			return CollectionIds::ARTICLES_COLLECTION_ID;
		}
		
		if ($collectionType === 'tags') {
			return CollectionIds::TAGS_COLLECTION_ID;
		}
		
		$collectionItem = CollectionItem::where('id', $itemId)->first(['collection_id']);

		if (!$collectionItem->isEmpty()) {
			return $collectionItem->collection_id;
		}

		if (!\class_exists('SppagebuilderHelperArticles')) {
			require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/articles.php';
		}

		try {
			$articlesCount = \SppagebuilderHelperArticles::getArticlesCount();
			$articles = \SppagebuilderHelperArticles::getArticles($articlesCount);
			foreach ($articles as $article) {
				if ($article->id == $itemId) {
					return CollectionIds::ARTICLES_COLLECTION_ID;
				}
			}
		} catch (\Exception $e) {
		}

		$db = \Joomla\CMS\Factory::getDbo();
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from('#__tags')
			->where('id = ' . (int) $itemId)
			->where('published = 1');
		$db->setQuery($query);
		$tagCount = $db->loadResult();
		
		if ($tagCount > 0) {
			return CollectionIds::TAGS_COLLECTION_ID;
		}

		return null;
	}

	private static function getItemAliasByCollectionItemId($collectionId, $collectionItemId)
	{
		if (empty($collectionId) || empty($collectionItemId)) {
			return null;
		}

		if ($collectionId === CollectionIds::ARTICLES_COLLECTION_ID) {
			if (!\class_exists('SppagebuilderHelperArticles')) {
				require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/articles.php';
			}

			try {
				$articlesCount = \SppagebuilderHelperArticles::getArticlesCount();
				$articles = \SppagebuilderHelperArticles::getArticles($articlesCount);
				foreach ($articles as $article) {
					if ($article->id == $collectionItemId) {
						$alias = !empty($article->alias) ? $article->alias : OutputFilter::stringURLSafe($article->title);
						return $alias;
					}
				}
			} catch (\Exception $e) {
				return 'article-' . $collectionItemId;
			}

			return 'article-' . $collectionItemId;
		}

		if ($collectionId === CollectionIds::TAGS_COLLECTION_ID) {
			try {
				$db = \Joomla\CMS\Factory::getDbo();
				$query = $db->getQuery(true)
					->select('alias, title')
					->from('#__tags')
					->where('id = ' . (int) $collectionItemId)
					->where('published = 1');
				$db->setQuery($query);
				$tag = $db->loadObject();
				
				if ($tag) {
					$alias = !empty($tag->alias) ? $tag->alias : OutputFilter::stringURLSafe($tag->title);
					return $alias;
				}
			} catch (\Exception $e) {
				return 'tag-' . $collectionItemId;
			}

			return 'tag-' . $collectionItemId;
		}

		$aliasField = CollectionField::where('collection_id', $collectionId)
			->where('type', FieldTypes::ALIAS)
			->first(['id']);

		if ($aliasField->isEmpty()) {
			return null;
		}

		$alias = CollectionItemValue::where('item_id', $collectionItemId)
			->where('field_id', $aliasField->id)
			->first(['value']);

		if ($alias->isEmpty()) {
			return null;
		}

		return $alias->value;
	}

	private static function getCollectionItemIdByAlias($collectionId, $alias)
	{
		if ($collectionId === CollectionIds::ARTICLES_COLLECTION_ID) {
			if (!\class_exists('SppagebuilderHelperArticles')) {
				require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/articles.php';
			}

			try {
				$articlesCount = \SppagebuilderHelperArticles::getArticlesCount();
				$articles = \SppagebuilderHelperArticles::getArticles($articlesCount);
				foreach ($articles as $article) {
					$articleAlias = !empty($article->alias) ? $article->alias : OutputFilter::stringURLSafe($article->title);
					if ($articleAlias === $alias) {
						return $article->id;
					}
				}
			} catch (\Exception $e) {
				return null;
			}

			return null;
		}

		if ($collectionId === CollectionIds::TAGS_COLLECTION_ID) {
			try {
				$db = \Joomla\CMS\Factory::getDbo();
				$query = $db->getQuery(true)
					->select('id, alias, title')
					->from('#__tags')
					->where('published = 1');
				$db->setQuery($query);
				$tags = $db->loadObjectList();
				
				foreach ($tags as $tag) {
					$tagAlias = !empty($tag->alias) ? $tag->alias : OutputFilter::stringURLSafe($tag->title);
					if ($tagAlias === $alias) {
						return $tag->id;
					}
				}
			} catch (\Exception $e) {
				return null;
			}

			return null;
		}

		$aliasField = CollectionField::where('collection_id', $collectionId)
			->where('type', FieldTypes::ALIAS)
			->first(['id']);
		
		if ($aliasField->isEmpty()) {
			return null;
		}

		$collectionItem = CollectionItemValue::where('field_id', $aliasField->id)
			->where('value', $alias)
			->first(['item_id']);

		if ($collectionItem->isEmpty()) {
			return null;
		}

		return $collectionItem->item_id;
	}

	private static function getDetailPageIdByCollectionId($collectionId)
	{
		if (empty($collectionId)) {
			return null;
		}

		$page = Page::where('view_id', $collectionId)
			->where('extension_view', 'dynamic_content:detail')
			->first(['id']);

		if ($page->isEmpty()) {
			return null;
		}

		return $page->id;
	}

	private static function getCollectionAlias($collectionId)
	{
		$collection = Collection::where('id', $collectionId)->first(['alias']);

		if ($collection->isEmpty()) {
			return null;
		}

		return $collection->alias;
	}

	private static function getCollectionIdByAlias($alias)
	{
		$collection = Collection::where('alias', $alias)->first(['id']);

		if ($collection->isEmpty()) {
			return null;
		}

		return $collection->id;
	}
}

$version = new Version();
$JoomlaVersion = (float) $version->getShortVersion();

if ($JoomlaVersion >= 4)
{
	class SppagebuilderRouter extends Joomla\CMS\Component\Router\RouterBase
	{
		public function build(&$query)
		{
			$segments = SppagebuilderRouterBase::buildRoute($query);
			return $segments;
		}

		public function parse(&$segments)
		{
			$vars = SppagebuilderRouterBase::parseRoute($segments);

			if (count($vars))
			{
				$segments = array();
			}

			return $vars;
		}
	}
}

/**
 * Build the route for the com_banners component
 *
 * This function is a proxy for the new router interface
 * for old SEF extensions.
 *
 * @param   array  &$query  An array of URL arguments
 *
 * @return  array  The URL arguments to use to assemble the subsequent URL.
 *
 * @since   3.3
 * @deprecated  4.0  Use Class based routers instead
 */
function SppagebuilderBuildRoute(&$query)
{
	$segments = SppagebuilderRouterBase::buildRoute($query);

	return $segments;
}

/**
 * Parse the segments of a URL.
 *
 * This function is a proxy for the new router interface
 * for old SEF extensions.
 *
 * @param   array  $segments  The segments of the URL to parse.
 *
 * @return  array  The URL attributes to be used by the application.
 *
 * @since   3.3
 * @deprecated  4.0  Use Class based routers instead
 */
function SppagebuilderParseRoute(&$segments)
{
	$vars = SppagebuilderRouterBase::parseRoute($segments);

	return $vars;
}