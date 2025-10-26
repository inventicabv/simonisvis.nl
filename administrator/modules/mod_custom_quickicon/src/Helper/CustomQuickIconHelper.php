<?php
/*
 *  package: Custom-Quickicons
 *  copyright: Copyright (c) 2024. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 2 or later
 *  link: https://www.joomill-extensions.com
 */

namespace Joomill\Module\Customquickicon\Administrator\Helper;

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;


/**
 * Helper for mod_custom_quickicon
 *
 * @since  1.0.0
 */
class CustomQuickIconHelper
{
    /**
     * Stack to hold buttons
     *
     * @var     array[]
     * @since   1.0.0
     */
    protected $buttons = array();

    /**
     * Helper method to return button list.
     *
     * This method returns the array by reference so it can be
     * used to add custom buttons or remove default ones.
     *
     * @param Registry $params The module parameters
     * @param CMSApplication|null $application The application
     *
     * @return  array  An array of buttons
     *
     * @since   1.0.0
     */
    public function getButtons(Registry $params, ?CMSApplication $application = null)
    {
        if ($application == null) {
            $application = Factory::getApplication();
        }

        $key = (string)$params;
        $context = (string)$params->get('context', 'mod_custom_quickicon');

        if (!isset($this->buttons[$key])) {
            // Load mod_custom_quickicon language file in case this method is called before rendering the module
            $application->getLanguage()->load('mod_custom_quickicon');

            $this->buttons[$key] = [];

            if ($params->get('show_robots')) {
                $robotsstatus = ucwords($application->get('robots') ?? '');
                if ((empty($robotsstatus)) && ($params->get('show_robots') == 1)) {
                    $quickicon = [
                        'image' => 'icon-publish',
                        'link'  => 'index.php?option=com_config',
                        'text'  => 'Robots meta tag: <br/> Index, Follow',
                        'class' => 'quickicon-robots success',
                        'group' => $context,
                        ];
                    $this->buttons[$key][] = $quickicon;
                }

                if ((!empty($robotsstatus) && ($params->get('show_robots') != 3))) {
                    $quickicon =  [
                        'image' => 'icon-warning-2',
                        'link'  => 'index.php?option=com_config',
                        'text'  => 'Robots meta tag: <br/>' . $robotsstatus,
                        'class' => 'quickicon-robots danger',
                        'group' => $context,
                        ];
                    $this->buttons[$key][] = $quickicon;
                }

                if ((!empty($robotsstatus) && ($params->get('show_robots') == 3))) {
                    $alertdangertext = Text::sprintf('MOD_CUSTOM_QUICKICON_MESSAGE', $robotsstatus);
                    $application->enqueueMessage($alertdangertext, 'danger');
                }
            } 

            // JOOMLA DEFAULT QUICKICONS
            if ($params->get('show_users')) {
                $quickicon = [
                    'image'   => 'icon-users',
                    'link'    => Route::_('index.php?option=com_users&view=users'),
                    'linkadd' => Route::_('index.php?option=com_users&task=user.add'),
                    'name'    => 'MOD_CUSTOM_QUICKICON_USER_MANAGER',
                    'access'  => ['core.manage', 'com_users', 'core.create', 'com_users'],
                    'class'   => 'quickicon-users',
                    'group'   => $context,
                ];

                if ($params->get('show_users') == 2) {
                    $quickicon['ajaxurl'] = 'index.php?option=com_users&amp;task=users.getQuickiconContent&amp;format=json';
                }

                $this->buttons[$key][] = $quickicon;
            }

            if ($params->get('show_menuitems')) {
                $quickicon = [
                    'image'   => 'icon-list',
                    'link'    => Route::_('index.php?option=com_menus&view=items&menutype='),
                    'linkadd' => Route::_('index.php?option=com_menus&task=item.add'),
                    'name'    => 'MOD_CUSTOM_QUICKICON_MENUITEMS_MANAGER',
                    'access'  => ['core.manage', 'com_menus', 'core.create', 'com_menus'],
                    'class'   => 'quickicon-menus',
                    'group'   => $context,
                ];

                if ($params->get('show_menuitems') == 2) {
                    $quickicon['ajaxurl'] = 'index.php?option=com_menus&amp;task=items.getQuickiconContent&amp;format=json';
                }

                $this->buttons[$key][] = $quickicon;
            }

            if ($params->get('show_articles')) {
                $quickicon = [
                    'image'   => 'icon-file-alt',
                    'link'    => Route::_('index.php?option=com_content&view=articles'),
                    'linkadd' => Route::_('index.php?option=com_content&task=article.add'),
                    'name'    => 'MOD_CUSTOM_QUICKICON_ARTICLE_MANAGER',
                    'access'  => ['core.manage', 'com_content', 'core.create', 'com_content'],
                    'class'   => 'quickicon-articles',
                    'group'   => $context,
                ];

                if ($params->get('show_articles') == 2) {
                    $quickicon['ajaxurl'] = 'index.php?option=com_content&amp;task=articles.getQuickiconContent&amp;format=json';
                }

                $this->buttons[$key][] = $quickicon;
            }

	        if (ComponentHelper::isEnabled('com_tags') && $params->get('show_tags')) {
	            $quickicon = [
                    'image'   => 'icon-tag',
                    'link'    => Route::_('index.php?option=com_tags&view=tags'),
                    'linkadd' => Route::_('index.php?option=com_tags&task=tag.edit'),
                    'name'    => 'MOD_CUSTOM_QUICKICON_TAGS_MANAGER',
                    'access'  => ['core.manage', 'com_tags', 'core.create', 'com_tags'],
                    'class'   => 'quickicon-tags',
                    'group'   => $context,
                ];

                if ($params->get('show_tags') == 2) {
	                $quickicon['ajaxurl'] = 'index.php?option=com_tags&amp;task=tags.getQuickiconContent&amp;format=json';
                }

                $this->buttons[$key][] = $quickicon;
            }

            // ARTICLE QUICKICONS
            $items = $params->get('article_items', []);
            $items = (array)$items;

            foreach ($items as $item) {
                $db = Factory::getContainer()->get('DatabaseDriver');
                $query = $db->getQuery(true)
                    ->select($db->quoteName('title'))
                    ->from($db->quoteName('#__content'))
                    ->where($db->quoteName('id') . ' = ' . (int) $item->item_id);

                $db->setQuery($query);
                $title = $db->loadResult();

                $quickicon = [
                    'image'     => $item->item_icon,
                    'link'      => Route::_("index.php?option=com_content&view=article&task=article.edit&id=$item->item_id"),
                    'name'      => $title,
                    'access'    => ['core.manage', 'com_content'],
                    'class'     => 'quickicon-article quickicon-article-'. $item->item_id,
                    'group'     => $context,
                ];
                if ($item->item_return) {
                    $returnParam = '&return=' . urlencode(base64_encode($item->item_return));
                    $quickicon['link'] .= $returnParam;
                }

                $this->buttons[$key][] = $quickicon;
            }

            // CATEGORY QUICKICONS
            $items = $params->get('category_items', []);
            $items = (array)$items;

            foreach ($items as $item) {
                if ($item->article_category != "") {
                    $db = Factory::getContainer()->get('DatabaseDriver');
                    $query = $db->getQuery(true)
                        ->select($db->quoteName('title'))
                        ->from($db->quoteName('#__categories'))
                        ->where($db->quoteName('id') . ' = ' . (int) $item->article_category);

                    $db->setQuery($query);
                    $title = $db->loadResult();
                } else {
                    $title = 'MOD_CUSTOM_QUICKICON_FORM_ARTICLES_LABEL';
                }

                if ($item->article_author == "current") {
                    $title = 'MOD_CUSTOM_QUICKICON_FORM_MYARTICLES_LABEL';
                }

                if (isset($item->item_name) && $item->item_name != "") {
                    $title = $item->item_name;
                }

                $quickicon = [
                    'image'   => $item->item_icon,
                    'link'    => Route::_("index.php?option=com_content&view=articles"),
                    'linkadd' => Route::_("index.php?option=com_content&view=article&layout=edit"),
                    'name'    => $title,
                    'access'  => ['core.manage', 'com_content', 'core.create', 'com_content'],
                    'class'   => 'quickicon-category quickicon-category-' . ApplicationHelper::stringURLSafe($item->item_name),
                    'group'   => $context,
                ];

                if ($item->article_category) {
                    $quickicon['link'] .= '&filter[category_id]=' . $item->article_category;
                    $quickicon['linkadd'] .= '&catid=' . $item->article_category;
                    $quickicon['class'] .= ' quickicon-category-catid-' . $item->article_category;
                }
                if ($item->article_language != "*") {
                    $quickicon['link'] .= '&filter[language]=' . $item->article_language;
                    $quickicon['linkadd'] .= '&language=' . $item->article_language;
                    $quickicon['class'] .= ' quickicon-category-language-' . ApplicationHelper::stringURLSafe($item->article_language);
                }
                if ($item->article_author) {
                    if ($item->article_author == "current") {
                        $item->article_author = Factory::getApplication()->getIdentity()->id;
                    }
                    $quickicon['link'] .= '&filter[author_id]=' . $item->article_author;
                    $quickicon['class'] .= ' quickicon-category-authorid-' . $item->article_author;
                }
                if (isset($item->article_search) && $item->article_search != "") {
                    $quickicon['link'] .= '&filter[search]=' . $item->article_search;
                    $quickicon['class'] .= ' quickicon-category-' . $item->article_search;
                }
                if (isset($item->article_tag) && $item->article_tag != "") {
                    foreach ($item->article_tag as $tag){
                        $quickicon['link'] .= '&filter[tag][]=' . $tag;
                        $quickicon['class'] .= ' quickicon-category-tagid-' . $tag;
                    }
                } 
                

                $this->buttons[$key][] = $quickicon;
            }

            if ($params->get('show_categories')) {
                $quickicon = [
                    'image'   => 'icon-folder-open',
                    'link'    => Route::_('index.php?option=com_categories&view=categories&extension=com_content'),
                    'linkadd' => Route::_('index.php?option=com_categories&task=category.add'),
                    'name'    => 'MOD_CUSTOM_QUICKICON_CATEGORY_MANAGER',
                    'access'  => ['core.manage', 'com_content', 'core.create', 'com_content'],
                    'class'   => 'quickicon-categories',
                    'group'   => $context,
                ];

                if ($params->get('show_categories') == 2) {
                    $quickicon['ajaxurl'] = 'index.php?option=com_categories&amp;task=categories.getQuickiconContent&amp;format=json';
                }

                $this->buttons[$key][] = $quickicon;
            }

            if ($params->get('show_media')) {
                $this->buttons[$key][] = [
                    'image'  => 'icon-images',
                    'link'   => Route::_('index.php?option=com_media'),
                    'name'   => 'MOD_CUSTOM_QUICKICON_MEDIA_MANAGER',
                    'access' => ['core.manage', 'com_media'],
                    'class'  => 'quickicon-media',
                    'group'  => $context,
                ];
            }

            if ($params->get('show_modules')) {
                $quickicon = [
                    'image'   => 'icon-cube',
                    'link'    => Route::_('index.php?option=com_modules&view=modules&client_id=0'),
                    'linkadd' => Route::_('index.php?option=com_modules&view=select&client_id=0'),
                    'name'    => 'MOD_CUSTOM_QUICKICON_MODULE_MANAGER',
                    'access'  => ['core.manage', 'com_modules'],
                    'class'   => 'quickicon-modules',
                    'group'   => $context
                ];

                if ($params->get('show_modules') == 2) {
                    $quickicon['ajaxurl'] = 'index.php?option=com_modules&amp;task=modules.getQuickiconContent&amp;format=json';
                }

                $this->buttons[$key][] = $quickicon;
            }
            // MODULE QUICKICONS
            $items = $params->get('module_items', []);
            $items = (array)$items;

            foreach ($items as $item) {
                $db = Factory::getContainer()->get('DatabaseDriver');
                $query = $db->getQuery(true)
                    ->select($db->quoteName('title'))
                    ->from($db->quoteName('#__modules'))
                    ->where($db->quoteName('id') . ' = ' . (int) $item->item_id);

                $db->setQuery($query);
                $title = $db->loadResult();

                $quickicon = [
                    'image' => $item->item_icon,
                    'link'  => Route::_("index.php?option=com_modules&view=module&task=module.edit&id=$item->item_id"),
                    'name'  => $title,
                    'class' => 'quickicon-module quickicon-module-'.$item->item_id,
                    'group' => $context,
                ];
                if ($item->item_return) {
                    $quickicon['link'] .= '&return=' . urlencode(base64_encode($item->item_return));
                }

                $this->buttons[$key][] = $quickicon;
            }

            if ($params->get('show_plugins')) {
                $quickicon = [
                    'image'  => 'icon-plug',
                    'link'   => Route::_('index.php?option=com_plugins'),
                    'name'   => 'MOD_CUSTOM_QUICKICON_PLUGIN_MANAGER',
                    'access' => ['core.manage', 'com_plugins'],
                    'class'  => 'quickicon-plugins',
                    'group'  => $context
                ];

                if ($params->get('show_plugins') == 2) {
                    $quickicon['ajaxurl'] = 'index.php?option=com_plugins&amp;task=plugins.getQuickiconContent&amp;format=json';
                }

                $this->buttons[$key][] = $quickicon;
            }

            if ($params->get('show_extensions')) {
                $this->buttons[$key][] = [
                    'image'   => 'icon-puzzle-piece',
                    'link'    => Route::_('index.php?option=com_installer&view=manage'),
                    'linkadd' => Route::_('index.php?option=com_installer&view=install'),
                    'name'    => 'MOD_CUSTOM_QUICKICON_EXTENSIONS_MANAGER',
                    'access'  => ['core.manage', 'com_installer', 'core.admin', 'com_installer'],
                    'class'   => 'quickicon-extensions',
                    'group'   => $context
                ];
            }

            if ($params->get('show_template_styles')) {
                $this->buttons[$key][] = [
                    'image'  => 'icon-paint-brush',
                    'link'   => Route::_('index.php?option=com_templates&view=styles&client_id=0'),
                    'name'   => 'MOD_CUSTOM_QUICKICON_TEMPLATE_STYLES',
                    'access' => ['core.admin', 'com_templates'],
                    'class'  => 'quickicon-template-styles',
                    'group'  => $context
                ];
            }

            if ($params->get('show_template_code')) {
                $this->buttons[$key][] = [
                    'image'  => 'icon-code',
                    'link'   => Route::_('index.php?option=com_templates&view=templates&client_id=0'),
                    'name'   => 'MOD_CUSTOM_QUICKICON_TEMPLATE_CODE',
                    'access' => ['core.admin', 'com_templates'],
                    'class'  => 'quickicon-template-code',
                    'group'  => $context
                ];
            }

            if ($params->get('show_checkin')) {
                $quickicon = [
                    'image'  => 'icon-unlock-alt',
                    'link'   => Route::_('index.php?option=com_checkin'),
                    'name'   => 'MOD_CUSTOM_QUICKICON_CHECKINS',
                    'access' => ['core.admin', 'com_checkin'],
                    'class'  => 'quickicon-checkin',
                    'group'  => $context
                ];

                if ($params->get('show_checkin') == 2) {
                    $quickicon['ajaxurl'] = 'index.php?option=com_checkin&amp;task=getQuickiconContent&amp;format=json';
                }

                $this->buttons[$key][] = $quickicon;
            }

            if ($params->get('show_cache')) {
                $quickicon = [
                    'image'  => 'icon-cloud',
                    'link'   => Route::_('index.php?option=com_cache'),
                    'name'   => 'MOD_CUSTOM_QUICKICON_CACHE',
                    'access' => ['core.admin', 'com_cache'],
                    'class'  => 'quickicon-cache',
                    'group'  => $context
                ];

                if ($params->get('show_cache') == 2) {
                    $quickicon['ajaxurl'] = 'index.php?option=com_cache&amp;task=display.getQuickiconContent&amp;format=json';
                }

                $this->buttons[$key][] = $quickicon;
            }

            if ($params->get('show_global')) {
                $this->buttons[$key][] = [
                    'image'  => 'icon-cog',
                    'link'   => Route::_('index.php?option=com_config'),
                    'name'   => 'MOD_CUSTOM_QUICKICON_GLOBAL_CONFIGURATION',
                    'access' => ['core.manage', 'com_config', 'core.admin', 'com_config'],
                    'class'  => 'quickicon-config',
                    'group'  => $context,
                ];
            }

	        if ($params->get('show_featured')) {
		        $tmp = [
			        'image'  => 'icon-star featured',
			        'link'   => Route::_('index.php?option=com_content&view=featured'),
			        'name'   => 'MOD_CUSTOM_QUICKICON_FEATURED_MANAGER',
			        'access' => ['core.manage', 'com_content'],
                    'class'  => 'quickicon-featured',
			        'group'  => 'MOD_CUSTOM_QUICKICON_SITE',
		        ];

		        if ($params->get('show_featured') == 2) {
			        $tmp['ajaxurl'] = 'index.php?option=com_content&amp;task=featured.getQuickiconContent&amp;format=json';
		        }

		        $this->buttons[$key][] = $tmp;
	        }

	        if ($params->get('show_workflow')) {
		        $this->buttons[$key][] = [
			        'image'   => 'icon-file-alt contact',
			        'link'    => Route::_('index.php?option=com_workflow&view=workflows&extension=com_content.article'),
			        'linkadd' => Route::_('index.php?option=com_workflow&view=workflow&layout=edit&extension=com_content.article'),
			        'name'    => 'MOD_CUSTOM_QUICKICON_WORKFLOW_MANAGER',
			        'access'  => ['core.manage', 'com_workflow', 'core.create', 'com_workflow'],
                    'class'   => 'quickicon-workflows',
			        'group'   => 'MOD_CUSTOM_QUICKICON_SITE',
		        ];
	        }

	        if (ComponentHelper::isEnabled('com_banners') && $params->get('show_banners')) {
		        $tmp = [
			        'image'   => 'icon-bookmark banners',
			        'link'    => Route::_('index.php?option=com_banners&view=banners'),
			        'linkadd' => Route::_('index.php?option=com_banners&view=banner&layout=edit'),
			        'name'    => 'MOD_CUSTOM_QUICKICON_BANNER_MANAGER',
			        'access'  => ['core.manage', 'com_banners', 'core.create', 'com_banners'],
                    'class'   => 'quickicon-banners',
			        'group'   => 'MOD_CUSTOM_QUICKICON_SITE',
		        ];

		        if ($params->get('show_banners') == 2) {
			        $tmp['ajaxurl'] = 'index.php?option=com_banners&amp;task=banners.getQuickiconContent&amp;format=json';
		        }

		        $this->buttons[$key][] = $tmp;
	        }

	        if (ComponentHelper::isEnabled('com_contacts') && $params->get('show_contact')) {
		        $tmp = [
			        'image'   => 'icon-address-book contact',
			        'link'    => Route::_('index.php?option=com_contact&view=contacts'),
			        'linkadd' => Route::_('index.php?option=com_contact&view=contact&layout=edit'),
			        'name'    => 'MOD_CUSTOM_QUICKICON_CONTACT_MANAGER',
			        'access'  => ['core.manage', 'com_contact', 'core.create', 'com_contact'],
                    'class'   => 'quickicon-contacts',
			        'group'   => 'MOD_CUSTOM_QUICKICON_SITE',
		        ];

		        if ($params->get('show_contact') == 2) {
			        $tmp['ajaxurl'] = 'index.php?option=com_contact&amp;task=contacts.getQuickiconContent&amp;format=json';
		        }

		        $this->buttons[$key][] = $tmp;
	        }

	        if (ComponentHelper::isEnabled('com_newsfeeds') && $params->get('show_newsfeeds')) {
		        $tmp = [
			        'image'   => 'icon-rss newsfeeds',
			        'link'    => Route::_('index.php?option=com_newsfeeds&view=newsfeeds'),
			        'linkadd' => Route::_('index.php?option=com_newsfeeds&view=newsfeed&layout=edit'),
			        'name'    => 'MOD_CUSTOM_QUICKICON_NEWSFEEDS_MANAGER',
			        'access'  => ['core.manage', 'com_newsfeeds', 'core.create', 'com_newsfeeds'],
                    'class'   => 'quickicon-newsfeeds',
			        'group'   => 'MOD_CUSTOM_QUICKICON_SITE',
		        ];

		        if ($params->get('show_newsfeeds') == 2) {
			        $tmp['ajaxurl'] = 'index.php?option=com_newsfeeds&amp;task=newsfeeds.getQuickiconContent&amp;format=json';
		        }

		        $this->buttons[$key][] = $tmp;
	        }

	        if (ComponentHelper::isEnabled('com_redirect') && $params->get('show_redirect')) {
		        $this->buttons[$key][] = [
			        'image'   => 'icon-map-signs redirect',
			        'link'    => Route::_('index.php?option=com_redirect&view=links'),
			        'linkadd' => Route::_('index.php?option=com_redirect&view=link&layout=edit'),
			        'name'    => 'MOD_CUSTOM_QUICKICON_REDIRECT_MANAGER',
			        'access'  => ['core.manage', 'com_redirect', 'core.create', 'com_redirect'],
                    'class'   => 'quickicon-redirects',
			        'group'   => 'MOD_CUSTOM_QUICKICON_SITE',
		        ];
	        }

	        if (ComponentHelper::isEnabled('com_associations') && $params->get('show_associations')) {
		        $this->buttons[$key][] = [
			        'image'  => 'icon-language',
			        'link'   => Route::_('index.php?option=com_associations&view=associations'),
			        'name'   => 'MOD_CUSTOM_QUICKICON_ASSOCIATIONS_MANAGER',
			        'access' => ['core.manage', 'com_associations'],
                    'class'  => 'quickicon-associations',
			        'group'  => 'MOD_CUSTOM_QUICKICON_SITE',
		        ];
	        }

	        if (ComponentHelper::isEnabled('com_finder') && $params->get('show_finder')) {
		        $this->buttons[$key][] = [
			        'image'  => 'icon-search-plus finder',
			        'link'   => Route::_('index.php?option=com_finder&view=index'),
			        'name'   => 'MOD_CUSTOM_QUICKICON_FINDER_MANAGER',
			        'access' => ['core.manage', 'com_finder'],
                    'class'  => 'quickicon-finder',
			        'group'  => 'MOD_CUSTOM_QUICKICON_SITE',
		        ];
	        }

	        if ($params->get('show_languages')) {
		        $tmp = [
			        'image'   => 'icon-comments langmanager',
			        'link'    => Route::_('index.php?option=com_languages&view=languages'),
			        'linkadd' => Route::_('index.php?option=com_installer&view=languages'),
			        'name'    => 'MOD_CUSTOM_QUICKICON_LANGUAGES_MANAGER',
			        'access'  => ['core.manage', 'com_languages'],
                    'class'   => 'quickicon-languages',
			        'group'   => 'MOD_CUSTOM_QUICKICON_SITE',
		        ];

		        if ($params->get('show_languages') == 2) {
			        $tmp['ajaxurl'] = 'index.php?option=com_languages&amp;task=languages.getQuickiconContent&amp;format=json';
		        }

		        $this->buttons[$key][] = $tmp;
	        }


            // DJ-CATALOG QUICKICONS
            if ($params->get('ecommerce_component') == "DJ-Catalog") {
                if ($params->get('show_djcatalog_dashboard')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-cart',
                        'link'  => Route::_('index.php?option=com_djcatalog2&view=cpanel'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_DJCATALOG',
                        'access'  => ['core.manage', 'com_djcatalog2'],
                        'class'   => 'quickicon-djcatalog quickicon-djcatalog-dashboard',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_djcatalog_products')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-cubes',
                        'link'    => Route::_('index.php?option=com_djcatalog2&view=items'),
                        'linkadd' => Route::_('index.php?option=com_djcatalog2&task=item.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_PRODUCTS',
                        'access'  => ['core.manage', 'com_djcatalog2'],
                        'class'   => 'quickicon-djcatalog quickicon-djcatalog-products',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_djcatalog_categories')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-folder',
                        'link'    => Route::_('index.php?option=com_djcatalog2&view=categories'),
                        'linkadd' => Route::_('index.php?option=com_djcatalog2&task=category.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_CATEGORIES',
                        'access'  => ['core.manage', 'com_djcatalog2'],
                        'class'   => 'quickicon-djcatalog quickicon-djcatalog-categories',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_djcatalog_customers')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-user',
                        'link'  => Route::_('index.php?option=com_djcatalog2&view=customers'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_USERS',
                        'access'  => ['core.manage', 'com_djcatalog2'],
                        'class'   => 'quickicon-djcatalog quickicon-djcatalog-customers',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_djcatalog_orders')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-credit',
                        'link'    => Route::_('index.php?option=com_djcatalog2&view=orders'),
                        'linkadd' => Route::_('index.php?option=com_djcatalog2&task=order.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_ORDERS',
                        'access'  => ['core.manage', 'com_djcatalog2'],
                        'class'   => 'quickicon-djcatalog quickicon-djcatalog-orders',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_djcatalog_subscriptions')) {
                    $this->buttons[$key][] = [
                        'image'   => 'fas fa-file-invoice-dollar',
                        'link'    => Route::_('index.php?option=com_djcatalog2&view=subscriptions'),
                        'linkadd' => Route::_('index.php?option=com_djcatalog2&task=subscription.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_SUBSCRIPTIONS',
                        'access'  => ['core.manage', 'com_djcatalog2'],
                        'class'   => 'quickicon-djcatalog quickicon-djcatalog-subscriptions',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_djcatalog_pricerules')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-tag',
                        'link'    => Route::_('index.php?option=com_djcatalog2&view=pricerules'),
                        'linkadd' => Route::_('index.php?option=com_djcatalog2&task=pricerule.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_PRICERULES',
                        'access'  => ['core.manage', 'com_djcatalog2'],
                        'class'   => 'quickicon-djcatalog quickicon-djcatalog-pricerules',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_djcatalog_coupons')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-tags-2',
                        'link'    => Route::_('index.php?option=com_djcatalog2&view=coupons'),
                        'linkadd' => Route::_('index.php?option=com_djcatalog2&task=coupon.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_COUPONS',
                        'access'  => ['core.manage', 'com_djcatalog2'],
                        'class'   => 'quickicon-djcatalog quickicon-djcatalog-coupons',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_djcatalog_producers')) {
                    $this->buttons[$key][] = [
                        'image'   => 'fas fa-industry',
                        'link'    => Route::_('index.php?option=com_djcatalog2&view=producers'),
                        'linkadd' => Route::_('index.php?option=com_djcatalog2&task=producer.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_PRODUCERS',
                        'access'  => ['core.manage', 'com_djcatalog2'],
                        'class'   => 'quickicon-djcatalog quickicon-djcatalog-producers',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_djcatalog_vendors')) {
                    $this->buttons[$key][] = [
                        'image'   => 'fas fa-user-tag',
                        'link'    => Route::_('index.php?option=com_djcatalog2&view=vendors'),
                        'linkadd' => Route::_('index.php?option=com_djcatalog2&task=vendor.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_VENDORS',
                        'access'  => ['core.manage', 'com_djcatalog2'],
                        'class'   => 'quickicon-djcatalog quickicon-djcatalog-vendors',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_djcatalog_reviews')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-star',
                        'link'    => Route::_('index.php?option=com_djcatalog2&view=reviews'),
                        'linkadd' => Route::_('index.php?option=com_djcatalog2&task=review.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_REVIEWS',
                        'access'  => ['core.manage', 'com_djcatalog2'],
                        'class'   => 'quickicon-djcatalog quickicon-djcatalog-reviews',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_djcatalog_messages')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-envelope-opened',
                        'link'  => Route::_('index.php?option=com_djcatalog2&view=messages'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_MESSAGES',
                        'access'  => ['core.manage', 'com_djcatalog2'],
                        'class'   => 'quickicon-djcatalog quickicon-djcatalog-messages',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_djcatalog_pricesstock')) {
                    $this->buttons[$key][] = [
                        'image' => 'fas fa-barcode',
                        'link'  => Route::_('index.php?option=com_djcatalog2&view=prices'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_PRICESSTOCK',
                        'access'  => ['core.manage', 'com_djcatalog2'],
                        'class'   => 'quickicon-djcatalog quickicon-djcatalog-prices',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_djcatalog_config')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-wrench',
                        'link'  => Route::_('index.php?option=com_config&view=component&component=com_djcatalog2'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_CONFIGURATION',
                        'access'  => ['core.manage', 'com_config'],
                        'class'   => 'quickicon-djcatalog quickicon-djcatalog-config',
                        'group' => $context,
                    ];
                }
            }


            // ESHOP QUICKICONS
            if ($params->get('ecommerce_component') == "EShop") {
                if ($params->get('show_eshop_dashboard')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-cart',
                        'link'  => Route::_('index.php?option=com_eshop&view=dashboard'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_ESHOP',
                        'access'  => ['core.manage', 'com_eshop'],
                        'class'   => 'quickicon-eshop quickicon-eshop-dashboard',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_eshop_products')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-cubes',
                        'link'    => Route::_('index.php?option=com_eshop&view=products'),
                        'linkadd' => Route::_('index.php?option=com_eshop&task=product.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_PRODUCTS',
                        'access'  => ['core.manage', 'com_eshop'],
                        'class'   => 'quickicon-eshop quickicon-eshop-products',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_eshop_downloads')) {
                    $this->buttons[$key][] = [
                        'image'   => 'fas fa-download',
                        'link'    => Route::_('index.php?option=com_eshop&view=downloads'),
                        'linkadd' => Route::_('index.php?option=com_eshop&task=download.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_DOWNLOADS',
                        'access'  => ['core.manage', 'com_eshop'],
                        'class'   => 'quickicon-eshop quickicon-eshop-downloads',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_eshop_categories')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-folder',
                        'link'    => Route::_('index.php?option=com_eshop&view=categories'),
                        'linkadd' => Route::_('index.php?option=com_eshop&task=category.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_CATEGORIES',
                        'access'  => ['core.manage', 'com_eshop'],
                        'class'   => 'quickicon-eshop quickicon-eshop-categories',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_eshop_customers')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-user',
                        'link'    => Route::_('index.php?option=com_eshop&view=customers'),
                        'linkadd' => Route::_('index.php?option=com_eshop&task=customer.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_USERS',
                        'access'  => ['core.manage', 'com_eshop'],
                        'class'   => 'quickicon-eshop quickicon-eshop-customers',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_eshop_orders')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-credit',
                        'link'  => Route::_('index.php?option=com_eshop&view=orders'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_ORDERS',
                        'access'  => ['core.manage', 'com_eshop'],
                        'class'   => 'quickicon-eshop quickicon-eshop-orders',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_eshop_quotes')) {
                    $this->buttons[$key][] = [
                        'image' => 'fas fa-file-invoice',
                        'link'  => Route::_('index.php?option=com_eshop&view=quotes'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_QUOTES',
                        'access'  => ['core.manage', 'com_eshop'],
                        'class'   => 'quickicon-eshop quickicon-eshop-quotes',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_eshop_discounts')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-tag',
                        'link'    => Route::_('index.php?option=com_eshop&view=discounts'),
                        'linkadd' => Route::_('index.php?option=com_eshop&task=discount.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_DISCOUNTS',
                        'access'  => ['core.manage', 'com_eshop'],
                        'class'   => 'quickicon-eshop quickicon-eshop-discounts',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_eshop_coupons')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-tags-2',
                        'link'    => Route::_('index.php?option=com_eshop&view=coupons'),
                        'linkadd' => Route::_('index.php?option=com_eshop&task=coupon.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_COUPONS',
                        'access'  => ['core.manage', 'com_eshop'],
                        'class'   => 'quickicon-eshop quickicon-eshop-coupons',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_eshop_vouchers')) {
                    $this->buttons[$key][] = [
                        'image'   => 'fas fa-heart',
                        'link'    => Route::_('index.php?option=com_eshop&view=vouchers'),
                        'linkadd' => Route::_('index.php?option=com_eshop&task=voucher.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_VOUCHERS',
                        'access'  => ['core.manage', 'com_eshop'],
                        'class'   => 'quickicon-eshop quickicon-eshop-vouchers',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_eshop_manufacturers')) {
                    $this->buttons[$key][] = [
                        'image'   => 'fas fa-industry',
                        'link'    => Route::_('index.php?option=com_eshop&view=manufacturers'),
                        'linkadd' => Route::_('index.php?option=com_eshop&task=manufacturer.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_MANUFACTURERS',
                        'access'  => ['core.manage', 'com_eshop'],
                        'class'   => 'quickicon-eshop quickicon-eshop-manufacturers',
                        'group'   => $context,
                    ];
                }


                if ($params->get('show_eshop_reviews')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-star',
                        'link'    => Route::_('index.php?option=com_eshop&view=reviews'),
                        'linkadd' => Route::_('index.php?option=com_eshop&task=review.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_REVIEWS',
                        'access'  => ['core.manage', 'com_eshop'],
                        'class'   => 'quickicon-eshop quickicon-eshop-reviews',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_eshop_notify')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-envelope-opened',
                        'link'  => Route::_('index.php?option=com_eshop&view=notify'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_MESSAGES',
                        'access'  => ['core.manage', 'com_eshop'],
                        'class'   => 'quickicon-eshop quickicon-eshop-notify',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_eshop_config')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-wrench',
                        'link'  => Route::_('index.php?option=com_eshop&view=configuration'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_CONFIGURATION',
                        'access'  => ['core.manage', 'com_eshop'],
                        'class'   => 'quickicon-eshop quickicon-eshop-config',
                        'group' => $context,
                    ];
                }
            }


            // HIKASHOP QUICKICONS
            if ((empty($params->get('ecommerce_component'))) || ($params->get('ecommerce_component') == "HikaShop")) {
                if ($params->get('show_hikashop_dashboard')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-cart',
                        'link'  => Route::_('index.php?option=com_hikashop'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_HIKASHOP',
                        'access'  => ['core.manage', 'com_hikashop'],
                        'class'   => 'quickicon-hikashop quickicon-hikashop-dashboard',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_hikashop_products')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-cubes',
                        'link'    => Route::_('index.php?option=com_hikashop&ctrl=product'),
                        'linkadd' => Route::_('index.php?option=com_hikashop&ctrl=product&task=add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_PRODUCTS',
                        'access'  => ['core.manage', 'com_hikashop'],
                        'class'   => 'quickicon-hikashop quickicon-hikashop-products',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_hikashop_categories')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-folder',
                        'link'    => Route::_('index.php?option=com_hikashop&ctrl=category'),
                        'linkadd' => Route::_('index.php?option=com_hikashop&ctrl=category&task=add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_CATEGORIES',
                        'access'  => ['core.manage', 'com_hikashop'],
                        'class'   => 'quickicon-hikashop quickicon-hikashop-categories',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_hikashop_users')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-user',
                        'link'  => Route::_('index.php?option=com_hikashop&ctrl=user&filter_partner=0'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_USERS',
                        'access'  => ['core.manage', 'com_hikashop'],
                        'class'   => 'quickicon-hikashop quickicon-hikashop-users',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_hikashop_orders')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-credit',
                        'link'    => Route::_('index.php?option=com_hikashop&ctrl=order'),
                        'linkadd' => Route::_('index.php?option=com_hikashop&ctrl=order&task=neworder'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_ORDERS',
                        'access'  => ['core.manage', 'com_hikashop'],
                        'class'   => 'quickicon-hikashop quickicon-hikashop-orders',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_hikashop_discounts')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-tag',
                        'link'    => Route::_('index.php?option=com_hikashop&ctrl=discount&filter_type=discount'),
                        'linkadd' => Route::_('index.php?option=com_hikashop&ctrl=discount&discount_type=discount&task=add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_DISCOUNTS',
                        'access'  => ['core.manage', 'com_hikashop'],
                        'class'   => 'quickicon-hikashop quickicon-hikashop-discounts',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_hikashop_coupons')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-tags-2',
                        'link'    => Route::_('index.php?option=com_hikashop&ctrl=discount&filter_type=coupon'),
                        'linkadd' => Route::_('index.php?option=com_hikashop&ctrl=discount&discount_type=coupon&task=add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_COUPONS',
                        'access'  => ['core.manage', 'com_hikashop'],
                        'class'   => 'quickicon-hikashop quickicon-hikashop-coupons',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_hikashop_carts')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-basket',
                        'link' => Route::_('index.php?option=com_hikashop&ctrl=cart&cart_type=cart'),
                        'linkadd' => Route::_('index.php?option=com_hikashop&ctrl=cart&cart_type=cart&task=add'),
                        'name' => 'MOD_CUSTOM_QUICKICON_CARTS',
                        'access'  => ['core.manage', 'com_hikashop'],
                        'class'   => 'quickicon-hikashop quickicon-hikashop-carts',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_hikashop_wishlist')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-heart-2',
                        'link'    => Route::_('index.php?option=com_hikashop&ctrl=cart&cart_type=wishlist'),
                        'linkadd' => Route::_('index.php?option=com_hikashop&ctrl=cart&cart_type=wishlist&task=add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_WISHLIST',
                        'access'  => ['core.manage', 'com_hikashop'],
                        'class'   => 'quickicon-hikashop quickicon-hikashop-wishlist',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_hikashop_waitlist')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-clock',
                        'link' => Route::_('index.php?option=com_hikashop&ctrl=waitlist'),
                        'linkadd' => Route::_('index.php?option=com_hikashop&ctrl=waitlist&task=add'),
                        'name' => 'MOD_CUSTOM_QUICKICON_WAITLIST',
                        'access'  => ['core.manage', 'com_hikashop'],
                        'class'   => 'quickicon-hikashop quickicon-hikashop-waitlist',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_hikashop_emailhistory')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-envelope-opened',
                        'link'  => Route::_('index.php?option=com_hikashop&ctrl=email_history'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_EMAILHISTORY',
                        'access'  => ['core.manage', 'com_hikashop'],
                        'class'   => 'quickicon-hikashop quickicon-hikashop-emailhistory',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_hikashop_config')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-wrench',
                        'link'  => Route::_('index.php?option=com_hikashop&ctrl=config'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_CONFIGURATION',
                        'access'  => ['core.manage', 'com_hikashop'],
                        'class'   => 'quickicon-hikashop quickicon-hikashop-config',
                        'group' => $context,
                    ];
                }
            }

            // J2STORE QUICKICONS
            if ($params->get('ecommerce_component') == "J2Store") {
                if ($params->get('show_j2store_dashboard')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-cart',
                        'link'  => Route::_('index.php?option=com_j2store'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_J2STORE',
                        'access'  => ['core.manage', 'com_j2store'],
                        'class'   => 'quickicon-j2store quickicon-j2store-dashboard',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_j2store_products')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-cubes',
                        'link'    => Route::_('index.php?option=com_j2store&view=products'),
                        'linkadd' => Route::_('index.php?option=com_content&view=article&layout=edit'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_PRODUCTS',
                        'access'  => ['core.manage', 'com_j2store'],
                        'class'   => 'quickicon-j2store quickicon-j2store-products',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_j2store_inventory')) {
                    $this->buttons[$key][] = [
                        'image' => 'fas fa-barcode',
                        'link'  => Route::_('index.php?option=com_j2store&view=inventories'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_INVENTORY',
                        'access'  => ['core.manage', 'com_j2store'],
                        'class'   => 'quickicon-j2store quickicon-j2store-inventories',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_j2store_vendors')) {
                    $this->buttons[$key][] = [
                        'image'   => 'fas fa-user-tag',
                        'link'    => Route::_('index.php?option=com_j2store&view=vendors'),
                        'linkadd' => Route::_('index.php?option=com_j2store&view=vendors&task=add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_VENDORS',
                        'access'  => ['core.manage', 'com_j2store'],
                        'class'   => 'quickicon-j2store quickicon-j2store-vendors',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_j2store_manufacturers')) {
                    $this->buttons[$key][] = [
                        'image'   => 'fas fa-industry',
                        'link'    => Route::_('index.php?option=com_j2store&view=manufacturers'),
                        'linkadd' => Route::_('index.php?option=com_j2store&view=manufacturers&task=add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_MANUFACTURERS',
                        'access'  => ['core.manage', 'com_j2store'],
                        'class'   => 'quickicon-j2store quickicon-j2store-manufacturers',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_j2store_orders')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-credit',
                        'link'  => Route::_('index.php?option=com_j2store&view=orders'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_ORDERS',
                        'access'  => ['core.manage', 'com_j2store'],
                        'class'   => 'quickicon-j2store quickicon-j2store-orders',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_j2store_customers')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-user',
                        'link'  => Route::_('index.php?option=com_j2store&view=customers'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_USERS',
                        'access'  => ['core.manage', 'com_j2store'],
                        'class'   => 'quickicon-j2store quickicon-j2store-customers',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_j2store_coupons')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-tags-2',
                        'link'    => Route::_('index.php?option=com_j2store&view=coupons'),
                        'linkadd' => Route::_('index.php?option=com_j2store&view=coupons&task=add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_COUPONS',
                        'access'  => ['core.manage', 'com_j2store'],
                        'class'   => 'quickicon-j2store quickicon-j2store-coupons',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_j2store_vouchers')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-tag',
                        'link'    => Route::_('index.php?option=com_j2store&view=vouchers'),
                        'linkadd' => Route::_('index.php?option=com_j2store&view=vouchers&task=add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_VOUCHERS',
                        'access'  => ['core.manage', 'com_j2store'],
                        'class'   => 'quickicon-j2store quickicon-j2store-vouchers',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_j2store_reports')) {
                    $this->buttons[$key][] = [
                        'image' => 'far fa-chart-bar',
                        'link'  => Route::_('index.php?option=com_j2store&view=reports'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_REPORTS',
                        'access'  => ['core.manage', 'com_j2store'],
                        'class'   => 'quickicon-j2store quickicon-j2store-reports',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_j2store_config')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-wrench',
                        'link'  => Route::_('index.php?option=com_j2store&view=configuration'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_CONFIGURATION',
                        'access'  => ['core.manage', 'com_j2store'],
                        'class'   => 'quickicon-j2store quickicon-j2store-config',
                        'group' => $context,
                    ];
                }
            }

            // PHOCACART QUICKICONS
            if ($params->get('ecommerce_component') == "PhocaCart") {
                if ($params->get('show_phocacart_dashboard')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-cart',
                        'link'  => Route::_('index.php?option=com_phocacart&view=phocacartcp'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_PHOCACART',
                        'access'  => ['core.manage', 'com_phocacart'],
                        'class'   => 'quickicon-phocacart quickicon-phocacart-dashboard',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_phocacart_products')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-cubes',
                        'link'    => Route::_('index.php?option=com_phocacart&view=phocacartitems'),
                        'linkadd' => Route::_('index.php?option=com_phocacart&task="phocacartitem.add"'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_PRODUCTS',
                        'access'  => ['core.manage', 'com_phocacart'],
                        'class'   => 'quickicon-phocacart quickicon-phocacart-products',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_phocacart_categories')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-folder',
                        'link'    => Route::_('index.php?option=com_phocacart&view=phocacartcategories'),
                        'linkadd' => Route::_('index.php?option=com_phocacart&task=phocacartcategory.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_CATEGORIES',
                        'access'  => ['core.manage', 'com_phocacart'],
                        'class'   => 'quickicon-phocacart quickicon-phocacart-categories',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_phocacart_customers')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-user',
                        'link'    => Route::_('index.php?option=com_phocacart&view=phocacartusers'),
                        'linkadd' => Route::_('index.php?option=com_phocacart&task=phocacartuser.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_USERS',
                        'access'  => ['core.manage', 'com_phocacart'],
                        'class'   => 'quickicon-phocacart quickicon-phocacart-customers',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_phocacart_orders')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-credit',
                        'link'  => Route::_('index.php?option=com_phocacart&view=phocacartorders'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_ORDERS',
                        'access'  => ['core.manage', 'com_phocacart'],
                        'class'   => 'quickicon-phocacart quickicon-phocacart-orders',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_phocacart_wishlists')) {
                    $this->buttons[$key][] = [
                        'image'   => 'fas fa-heart',
                        'link'    => Route::_('index.php?option=com_phocacart&view=phocacartwishlists'),
                        'linkadd' => Route::_('index.php?option=com_phocacart&task=phocacartwishlist.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_WISHLIST',
                        'access'  => ['core.manage', 'com_phocacart'],
                        'class'   => 'quickicon-phocacart quickicon-phocacart-wishlists',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_phocacart_rewardspoints')) {
                    $this->buttons[$key][] = [
                        'image'   => 'fas fa-coins',
                        'link'    => Route::_('index.php?option=com_phocacart&view=phocacartrewards'),
                        'linkadd' => Route::_('index.php?option=com_phocacart&task=phocacartreward.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_REWARDS',
                        'access'  => ['core.manage', 'com_phocacart'],
                        'class'   => 'quickicon-phocacart quickicon-phocacart-rewards',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_phocacart_questions')) {
                    $this->buttons[$key][] = [
                        'image'   => 'fas fa-comments',
                        'link'    => Route::_('index.php?option=com_phocacart&view=phocacartquestions'),
                        'linkadd' => Route::_('index.php?option=com_phocacart&task=phocacartquestion.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_QUESTIONS',
                        'access'  => ['core.manage', 'com_phocacart'],
                        'class'   => 'quickicon-phocacart quickicon-phocacart-questions',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_phocacart_discounts')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-tag',
                        'link'    => Route::_('index.php?option=com_phocacart&view=phocacartdiscounts'),
                        'linkadd' => Route::_('index.php?option=com_phocacart&task=phocacartdiscount.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_DISCOUNTS',
                        'access'  => ['core.manage', 'com_phocacart'],
                        'class'   => 'quickicon-phocacart quickicon-phocacart-discounts',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_phocacart_coupons')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-tags-2',
                        'link'    => Route::_('index.php?option=com_phocacart&view=phocacartcoupons'),
                        'linkadd' => Route::_('index.php?option=com_phocacart&task=phocacartcoupon.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_COUPONS',
                        'access'  => ['core.manage', 'com_phocacart'],
                        'class'   => 'quickicon-phocacart quickicon-phocacart-coupons',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_phocacart_manufacturers')) {
                    $this->buttons[$key][] = [
                        'image' => 'fas fa-industry',
                        'link' => Route::_('index.php?option=com_phocacart&view=phocacartmanufacturers'),
                        'linkadd' => Route::_('index.php?option=com_phocacart&task=phocacartmanufacturer.add'),
                        'name' => 'MOD_CUSTOM_QUICKICON_MANUFACTURERS',
                        'access'  => ['core.manage', 'com_phocacart'],
                        'class'   => 'quickicon-phocacart quickicon-phocacart-manufacturers',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_phocacart_vendors')) {
                    $this->buttons[$key][] = [
                        'image'   => 'fas fa-user-tag',
                        'link'    => Route::_('index.php?option=com_phocacart&view=phocacartvendors'),
                        'linkadd' => Route::_('index.php?option=com_phocacart&task=phocacartvendor.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_VENDORS',
                        'access'  => ['core.manage', 'com_phocacart'],
                        'class'   => 'quickicon-phocacart quickicon-phocacart-vendors',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_phocacart_reviews')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-star',
                        'link'    => Route::_('index.php?option=com_phocacart&view=phocacartreviews'),
                        'linkadd' => Route::_('index.php?option=com_phocacart&task=phocacartreview.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_REVIEWS',
                        'access'  => ['core.manage', 'com_phocacart'],
                        'class'   => 'quickicon-phocacart quickicon-phocacart-reviews',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_phocacart_reports')) {
                    $this->buttons[$key][] = [
                        'image' => 'far fa-chart-bar',
                        'link'  => Route::_('index.php?option=com_phocacart&view=phocacartreports'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_REPORTS',
                        'access'  => ['core.manage', 'com_phocacart'],
                        'class'   => 'quickicon-phocacart quickicon-phocacart-reports',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_phocacart_openingtimes')) {
                    $this->buttons[$key][] = [
                        'image'   => 'far fa-clock',
                        'link'    => Route::_('index.php?option=com_phocacart&view=phocacarttimes'),
                        'linkadd' => Route::_('index.php?option=com_phocacart&task=phocacarttime.add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_OPENINGTIMES',
                        'access'  => ['core.manage', 'com_phocacart'],
                        'class'   => 'quickicon-phocacart quickicon-phocacart-openingtimes',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_phocacart_config')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-wrench',
                        'link'  => Route::_('index.php?option=com_config&view=component&component=com_phocacart'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_CONFIGURATION',
                        'access'  => ['core.manage', 'com_config'],
                        'class'   => 'quickicon-phocacart quickicon-phocacart-config',
                        'group' => $context,
                    ];
                }
            }

            // VIRTUEMART QUICKICONS
            if ($params->get('ecommerce_component') == "Virtuemart") {
                if ($params->get('show_virtuemart_products')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-cubes',
                        'link'    => Route::_('index.php?option=com_virtuemart&view=product'),
                        'linkadd' => Route::_('index.php?option=com_virtuemart&view=product&task=add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_PRODUCTS',
                        'access'  => ['core.manage', 'com_virtuemart'],
                        'class'   => 'quickicon-vm quickicon-vm-dashboard',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_virtuemart_categories')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-folder',
                        'link'    => Route::_('index.php?option=com_virtuemart&view=category'),
                        'linkadd' => Route::_('index.php?option=com_virtuemart&view=category&task=add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_CATEGORIES',
                        'access'  => ['core.manage', 'com_virtuemart'],
                        'class'   => 'quickicon-vm quickicon-vm-categories',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_virtuemart_shoppers')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-user',
                        'link'  => Route::_('index.php?option=com_virtuemart&view=user'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_USERS',
                        'access'  => ['core.manage', 'com_virtuemart'],
                        'class'   => 'quickicon-vm quickicon-vm-shoppers',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_virtuemart_orders')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-credit',
                        'link'  => Route::_('index.php?option=com_virtuemart&view=orders'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_ORDERS',
                        'access'  => ['core.manage', 'com_virtuemart'],
                        'class'   => 'quickicon-vm quickicon-vm-orders',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_virtuemart_coupons')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-tags-2',
                        'link'    => Route::_('index.php?option=com_virtuemart&view=coupon'),
                        'linkadd' => Route::_('index.php?option=com_virtuemart&view=coupon&task=add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_COUPONS',
                        'access'  => ['core.manage', 'com_virtuemart'],
                        'class'   => 'quickicon-vm quickicon-vm-coupons',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_virtuemart_reviews')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-star',
                        'link'  => Route::_('index.php?option=com_virtuemart&view=ratings'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_REVIEWS',
                        'access'  => ['core.manage', 'com_virtuemart'],
                        'class'   => 'quickicon-vm quickicon-vm-reviews',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_virtuemart_manufacturers')) {
                    $this->buttons[$key][] = [
                        'image'   => 'fas fa-industry',
                        'link'    => Route::_('index.php?option=com_virtuemart&view=manufacturer'),
                        'linkadd' => Route::_('index.php?option=com_virtuemart&view=manufacturer&task=add'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_MANUFACTURERS',
                        'access'  => ['core.manage', 'com_virtuemart'],
                        'class'   => 'quickicon-vm quickicon-vm-manufacturers',
                        'group'   => $context,
                    ];
                }

                if ($params->get('show_virtuemart_salesreport')) {
                    $this->buttons[$key][] = [
                        'image' => 'far fa-chart-bar',
                        'link'  => Route::_('index.php?option=com_virtuemart&view=report'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_SALESREPORT',
                        'access'  => ['core.manage', 'com_virtuemart'],
                        'class'   => 'quickicon-vm quickicon-vm-salesreport',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_virtuemart_inventory')) {
                    $this->buttons[$key][] = [
                        'image' => 'fas fa-barcode',
                        'link'  => Route::_('index.php?option=com_virtuemart&view=inventory'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_INVENTORY',
                        'access'  => ['core.manage', 'com_virtuemart'],
                        'class'   => 'quickicon-vm quickicon-vm-inventory',
                        'group' => $context,
                    ];
                }

                if ($params->get('show_virtuemart_config')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-wrench',
                        'link'  => Route::_('index.php?option=com_virtuemart&view=config'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_CONFIGURATION',
                        'access'  => ['core.manage', 'com_virtuemart'],
                        'class'   => 'quickicon-vm quickicon-vm-config',
                        'group' => $context,
                    ];
                }
            }
            
            // JOOMSHOPPING QUICKICONS
            if ($params->get('ecommerce_component') == "Jshopping") {
                
                if ($params->get('show_jshopping_orders')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-credit',
                        'link'  => Route::_('index.php?option=com_jshopping&controller=orders'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_ORDERS',
                        'access'  => ['core.manage', 'com_jshopping'],
                        'class'   => 'quickicon-js quickicon-js-orders',
                        'group' => $context,
                    ];
                }
            
                if ($params->get('show_jshopping_products')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-cubes',
                        'link'    => Route::_('index.php?option=com_jshopping&controller=products&category_id=0'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_PRODUCTS',
                        'access'  => ['core.manage', 'com_jshopping'],
                        'class'   => 'quickicon-js quickicon-js-products',
                        'group'   => $context,
                    ];
                }
            
                if ($params->get('show_jshopping_categories')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-folder',
                        'link'    => Route::_('index.php?option=com_jshopping&controller=categories&catid=0'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_CATEGORIES',
                        'access'  => ['core.manage', 'com_jshopping'],
                        'class'   => 'quickicon-js quickicon-js-categories',
                        'group'   => $context,
                    ];
                }
                        
                if ($params->get('show_jshopping_users')) {
                    $this->buttons[$key][] = [
                        'image' => 'icon-user',
                        'link'  => Route::_('index.php?option=com_jshopping&controller=users'),
                        'name'  => 'MOD_CUSTOM_QUICKICON_USERS',
                        'access'  => ['core.manage', 'com_jshopping'],
                        'class'   => 'quickicon-js quickicon-js-users',
                        'group' => $context,
                    ];
                }
            
                if ($params->get('show_jshopping_manufacturers')) {
                    $this->buttons[$key][] = [
                        'image'   => 'fas fa-industry',
                        'link'    => Route::_('index.php?option=com_jshopping&controller=manufacturers'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_MANUFACTURERS',
                        'access'  => ['core.manage', 'com_jshopping'],
                        'class'   => 'quickicon-js quickicon-js-manufacturers',
                        'group'   => $context,
                    ];
                }
                                                                
                if ($params->get('show_jshopping_coupons')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-tags-2',
                        'link'    => Route::_('index.php?option=com_jshopping&controller=coupons'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_COUPONS',
                        'access'  => ['core.manage', 'com_jshopping'],
                        'class'   => 'quickicon-js quickicon-js-coupons',
                        'group'   => $context,
                    ];
                }
                if ($params->get('show_jshopping_options')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-wrench',
                        'link'    => Route::_('index.php?option=com_jshopping&controller=other'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_OPTIONS',
                        'access'  => ['core.manage', 'com_jshopping'],
                        'class'   => 'quickicon-js quickicon-js-options',
                        'group'   => $context,
                    ];
                }
              if ($params->get('show_jshopping_config')) {
                    $this->buttons[$key][] = [
                        'image'   => 'icon-cog',
                        'link'    => Route::_('index.php?option=com_jshopping&controller=config'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_CONFIG',
                        'access'  => ['core.manage', 'com_jshopping'],
                        'class'   => 'quickicon-js quickicon-js-options',
                        'group'   => $context,
                    ];
                }
                
            if (file_exists(JPATH_SITE."/components/com_jshopping/addons/addon_jstat/config.xml")) {

                if ($params->get('show_jshopping_statistic')) {
                    $this->buttons[$key][] = [
                        'image'   => 'far fa-chart-bar',
                        'link'    => Route::_('index.php?option=com_jshopping&controller=addon_jstat&view=orders'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_STATISTIC',
                        'access'  => ['core.manage', 'com_jshopping'],
                        'class'   => 'quickicon-js quickicon-js-statistic',
                        'group'   => $context,
                    ];
                }
            }else{
                if ($params->get('show_jshopping_statistic')) {
                    $this->buttons[$key][] = [
                        'image'   => 'far fa-chart-bar',
                        'link'    => Route::_('index.php?option=com_jshopping&controller=statistic'),
                        'name'    => 'MOD_CUSTOM_QUICKICON_STATISTIC',
                        'access'  => ['core.manage', 'com_jshopping'],
                        'class'   => 'quickicon-js quickicon-js-statistic',
                        'group'   => $context,
                    ];
                }
            }
            }


            // CUSTOM QUICKICONS
            $items = $params->get('custom_items', []);
            $items = (array)$items;

            foreach ($items as $item) {
                $quickicon = [
                    'image' => $item->item_icon,
                    'name' => $item->item_name,
                    'group' => $context,
                    'class'   => 'quickicon-custom quickicon-' . ApplicationHelper::stringURLSafe($item->item_name),
                ];

                if ($item->item_link_target) {
                    $quickicon['target'] = $item->item_link_target;
                }

                if ($item->item_link_menuitem == "custom") {
                    $quickicon['link'] = Route::_($item->item_link);
                } else {
                    $quickicon['link'] = Route::_($item->item_link_menuitem);
                }
                
                if ($item->item_link_add) {
                    $quickicon['linkadd'] = Route::_($item->item_link_add);
                }     
                
                $link = parse_url($quickicon['link'], PHP_URL_QUERY);
                if ($link) {
                    parse_str($link, $linkParameters);
                    $component = $linkParameters['option'];
					if ($component != 'com_cpanel') {
						$quickicon['access'] = ['core.manage', $component];
					}
                }

                $this->buttons[$key][] = $quickicon;
            }

        }

        return $this->buttons[$key];
    }
}