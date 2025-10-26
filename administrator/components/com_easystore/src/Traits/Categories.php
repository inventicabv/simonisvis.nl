<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Traits;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Get category List
 * @since 1.3.0
 */
trait Categories
{
    /**
     * Get the category list
     *
     * @return void
     *
     * @since 1.3.0
     */
    public function getAllCategories()
    {
        $requestMethod = $this->getInputMethod();

        $this->checkNotAllowedMethods(['POST', 'PUT', 'DELETE', 'PATCH'], $requestMethod);

        if ($requestMethod === 'GET') {
            $this->getCategoryList();
        }
    }

    /**
     * Get Category List
     *
     * @return mixed
     *
     * @since 1.3.0
     */
    private function getCategoryList()
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('DISTINCT a.id, a.title, a.level, a.published, a.lft');
        $subQuery = $db->getQuery(true)
            ->select('id, title, level, published, parent_id, lft, rgt')
            ->from('#__easystore_categories')
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('id') . ' > 1');

        $query->from('(' . $subQuery->__toString() . ') AS a')
            ->join('LEFT', $db->quoteName('#__easystore_categories') . ' AS b ON a.lft > b.lft AND a.rgt < b.rgt');
        $query->order('a.lft ASC');

        $db->setQuery($query);
        $categories = $db->loadObjectList();

        $easystoreCategories = [];

        if (!empty($categories)) {
            foreach ($categories as $category) {
                $value = (object) [
                    'value' => $category->id,
                    'label' => $category->title,
                ];

                $easystoreCategories[] = $value;
            }
        }

        return $this->sendResponse($easystoreCategories);
    }
}
