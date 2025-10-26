<?php

/**
 * @package     EasyStore.Site
 * @subpackage  EasyStore.Cheque
 *
 * @copyright   Copyright (C) 2023 - 2024 JoomShaper <https://www.joomshaper.com>. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace JoomShaper\Plugin\EasyStore\Cheque\Utils;

use Joomla\Registry\Registry;
use JoomShaper\Component\EasyStore\Administrator\Plugin\Constants;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class ChequeConstant extends Constants
{
    /**
     * Plugin parameters
     *
     * @var Registry
     */
    protected $params;

    /**
     * The payment plugin name
     *
     * @var string
     */
    protected $name = 'cheque';

    /**
     * The constructor method
     */
    public function __construct()
    {
        parent::__construct($this->name);
    }
}
