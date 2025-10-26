<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\Filesystem\Path;

/**
 * EasyStore Media helper.
 *
 * @since  1.0.0
 */

class EasyStoreMediaHelper
{
    /**
     * Actionable folder function
     *
     * @param string $folder
     * @return bool
     */
    public static function isActionableFolder(string $folder)
    {
        $folder = strtolower(Path::clean($folder));
        $parts  = explode(DIRECTORY_SEPARATOR, $folder);
        $parts  = array_filter($parts, function ($part) {
            return !empty($part);
        });
        $parts = array_values($parts);

        if (empty($parts) || !is_array($parts) || count($parts) < 2 || $parts[0] !== 'images') {
            return false;
        }

        return true;
    }

    /**
     * Get Path function
     *
     * @param string $path
     * @return bool
     */
    public static function isGetablePath(string $path)
    {
        $path      = strtolower(Path::clean($path));
        $pathArray = explode(DIRECTORY_SEPARATOR, $path);
        $pathArray = array_filter($pathArray, function ($part) {
            return !empty($part);
        });

        $pathArray = array_values($pathArray);

        if (empty($pathArray) || !is_array($pathArray) || count($pathArray) < 1 || $pathArray[0] !== 'images') {
            return false;
        }

        return true;
    }

    /**
     * Check media function
     *
     * @param string $path
     * @return void
     */
    public static function checkForMediaActionBoundary(string $path)
    {
        try {
            $cleanedPath = Path::check($path);
        } catch (Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return $cleanedPath;
    }
}
