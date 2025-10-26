<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Concerns;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;

trait HasAsset
{
    protected function _getAssetName()
    {
        $key = $this->_tbl_key;

        return $this->context . '.' . (int) $this->$key;
    }

    protected function _getAssetTitle()
    {
        return $this->title;
    }

    protected function _getAssetParentId(?Table $table = null, $id = null)
    {
        $parts     = explode('.', $this->context);
        $component = $parts[0];
        $assetId   = $this->getAssetId($component);

        if (!empty($assetId)) {
            return $assetId;
        }

        return parent::_getAssetParentId($table, $id);
    }

    private function getAssetId(string $name)
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select($db->quoteName('id'))
            ->from($db->quoteName('#__assets'))
            ->where($db->quoteName('name') . ' = :name')
            ->bind(':name', $name);

        $db->setQuery($query);

        $result = $db->loadResult();

        return !empty($result) ? (int) $result : false;
    }
}
