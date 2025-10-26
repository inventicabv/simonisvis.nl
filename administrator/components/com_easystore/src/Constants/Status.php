<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Constants;

/**
 * The Joomla published field's status values.
 *
 * @since 1.3.0
 */
final class Status
{
    /**
     * The published status
     *
     * @var int
     */
    public const PUBLISHED = 1;

    /**
     * The unpublished status
     *
     * @var int
     */
    public const UNPUBLISHED = 0;

    /**
     * Trashed status
     *
     * @var int
     */
    public const TRASHED = -2;

    /**
     * Archived status
     *
     * @var int
     */
    public const ARCHIVED = 2;

    /**
     * Soft delete status
     *
     * @var int
     */
    public const SOFT_DELETED = -1;
}
