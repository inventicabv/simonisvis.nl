<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_articles_latest
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

if (!$list)
{
	return;
}
?>
<ul class="recent_post_item <?php echo $moduleclass_sfx ?? ''; ?>">
<?php foreach ($list as $item) : ?>
	<li class="single-recent-post-item">
	<div class="thumb">
	<img src="<?php echo json_decode($item->images)->image_intro; ?>"/>
	</div>
	<div class="content">	
	<span class="time"><i class="far fa-calendar-alt"></i><?php echo JHtml::_('date', $item->created, JText::_('DATE_FORMAT_LC3')); ?></span>
	<h5 class="sidebar-title"><a href="<?php echo $item->link; ?>" itemprop="url"> <?php echo $item->title; ?> </a></h5>
	</div>
	</li>
<?php endforeach; ?>
</ul>
