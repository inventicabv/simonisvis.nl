<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Field;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Field\ListField;
use Joomla\Database\DatabaseInterface;

/**
 * EasystoreCategory field.
 *
 * @since  1.0.0
 */
class EasystoreCategoryField extends ListField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'EasystoreCategory';

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     *
     * @since   1.0.0
     */
    public function getOptions()
    {
        $options = [];
        $db      = Factory::getContainer()->get(DatabaseInterface::class);
        $query   = $db->getQuery(true);

        $query->select('id, title, parent_id, level')
            ->from('#__easystore_categories')
            ->where($db->quoteName('alias') . ' != ' . $db->quote('root'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('lft') . ' ASC');

        $db->setQuery($query);
        $categories = $db->loadObjectList();

        foreach ($categories as $category) {
            $options[] = HTMLHelper::_('select.option', $category->id, str_repeat('- ', $category->level) . $category->title);
        }

        $options = array_merge(parent::getOptions(), $options);
        return $options;
    }
}
