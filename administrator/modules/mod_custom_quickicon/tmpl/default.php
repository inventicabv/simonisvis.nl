<?php
/*
 *  package: Custom-Quickicons
 *  copyright: Copyright (c) 2024. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 2 or later
 *  link: https://www.joomill-extensions.com
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomill\Module\Customquickicon\Administrator\Helper\CustomquickiconHelper;

// No direct access.
defined('_JEXEC') or die;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $app->getDocument()->getWebAssetManager();
$wa->useScript('core')
	->useScript('bootstrap.dropdown');
$wa->registerAndUseScript('mod_quickicon', 'mod_quickicon/quickicon.min.js', ['relative' => true, 'version' => 'auto'], ['type' => 'module']);
$wa->registerAndUseScript('mod_quickicon-es5', 'mod_quickicon/quickicon-es5.min.js', ['relative' => true, 'version' => 'auto'], ['nomodule' => true, 'defer' => true]);

if ($params->get('fakit')) {
    if ($params->get('fakit_code') == "js") {
        $wa->registerAndUseScript('fontawesomekit', 'https://kit.fontawesome.com/' . $params->get('fakit') . '.js', ['crossorigin=' => 'anonymous'], []);
    }
    if ($params->get('fakit_code') == "css") {
        $wa->registerAndUseStyle('fontawesomekit', 'https://kit.fontawesome.com/' . $params->get('fakit') . '.css', ['crossorigin=' => 'anonymous'], []);
    }
}
// Get the hue value
preg_match('#^hsla?\(([0-9]+)[\D]+([0-9]+)[\D]+([0-9]+)[\D]+([0-9](?:.\d+)?)?\)$#i', $params->get('hue', 'hsl(214, 63%, 20%)'), $matches);

if ($matches[1] != "214") {
    // Enable assets
    $wa->addInlineStyle(':root {
            --hue-' . $module->id . ': ' . $matches[1] . ';
        }
        .custom-quick-icons-' . $module->id . ' .quickicon {
            --text-color: hsl(var(--hue-' . $module->id . '),30%,40%);
            --bg-color: hsl(var(--hue-' . $module->id . '),60%,97%);
            --icon-color: hsl(var(--hue-' . $module->id . '),30%,40%);
            --bg-color-hvr: hsl(var(--hue-' . $module->id . '),40%,20%);
        }
        .custom-quick-icons-' . $module->id . ' .quickicon a .quickicon-amount {
            background: hsl(var(--hue-' . $module->id . '),50%,93%);
        }
        .custom-quick-icons-' . $module->id . ' .quickicon-linkadd a>* {
            color: hsl(var(--hue-' . $module->id . '),30%,40%);
        }
        .custom-quick-icons-' . $module->id . ' .quickicon-linkadd {
            background: hsl(var(--hue-' . $module->id . '),50%,93%);
        }
        .custom-quick-icons-' . $module->id . ' .quickicon-linkadd a:active,
        .custom-quick-icons-' . $module->id . ' .quickicon-linkadd a:focus,
        .custom-quick-icons-' . $module->id . ' .quickicon-linkadd a:hover {
            background: hsl(var(--hue-' . $module->id . '),40%,20%);
        }
        .quick-icons .quickicon a:hover .quickicon-icon, 
        .quick-icons .quickicon a:focus .quickicon-icon, 
        .quick-icons .quickicon a:active .quickicon-icon {
            color: var(--icon-color);
        }
        .quickicon a[target=_blank]:before {
            display: none;
        }
    ');
}
    $wa->addInlineStyle('
        .quickicon a[target=_blank]:before {
            display: none;
        }
    ');

$html = HTMLHelper::_('icons.buttons', $buttons);
?>
<?php if (!empty($html)) : ?>
    <nav class="quick-icons px-3 pb-3 custom-quick-icons-<?php echo $module->id;?>"
         aria-label="<?php echo Text::_('MOD_CUSTOM_QUICKICON_NAV_LABEL') . ' ' . $module->title; ?>">
        <ul class="nav flex-wrap">
            <?php echo $html; ?>
        </ul>
    </nav>
<?php endif; ?>
