<?php
/**
 * @package Helix Ultimate Framework
 * @author JoomShaper https://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or Later
*/

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

// Add strings for translations in Javascript.
Text::script('JGLOBAL_EXPAND_CATEGORIES');
Text::script('JGLOBAL_COLLAPSE_CATEGORIES');

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('com_categories');
$wa->usePreset('com_categories.shared-categories-accordion');

?>
<div class="com-content-categories categories-list">
    <?php
        echo LayoutHelper::render('joomla.content.categories_default', $this);
        echo $this->loadTemplate('items');
    ?>
</div>
