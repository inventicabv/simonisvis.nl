<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_easystore.admin')
    ->useScript('keepalive')
    ->useScript('form.validate');

// Fieldsets to not automatically render by /layouts/joomla/edit/params.php
$this->ignore_fieldsets = ['jmetadata'];
$this->useCoreUI = true;
?>

<form action="<?php echo Route::_('index.php?option=com_easystore&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="item-form" aria-label="<?php echo Text::_('COM_EASYSTORE_MANAGER_REVIEWS' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>" class="form-vertical form-validate">
    <div class="easystore-container">
        <div class="row gx-lg-5">
            <div class="col-lg-8">
                <div class="easystore-card mb-4">
                    <div class="easystore-card-body">

                        <?php if (!empty($this->item->product)) :  ?>
                        <div class="control-group">                           
                            <div class="controls">
                                <?php if (!empty($this->item->product->thumbnail)) : ?>
                                    <?php
                                    $isVideoFile = EasyStoreHelper::validateFileType($this->item->product->thumbnail);
                                    ?>
                                    <span class="me-4">
                                        <?php if ($isVideoFile) : ?>
                                            <video src="<?php echo $this->item->product->thumbnail; ?>" alt="<?php echo $this->escape($this->item->product->title); ?>" height="100"></video>
                                        <?php else : ?>
                                            <img src="<?php echo $this->item->product->thumbnail; ?>" alt="<?php echo $this->escape($this->item->product->title); ?>" height="100" >
                                        <?php endif;?>
                                    </span>
                                <?php endif;?>
                                <b><a href="<?php echo Route::_(Uri::root() . 'index.php?option=com_easystore&view=product&id=' . $this->item->product->id . '&catid=' . $this->item->product->catid); ?>" target="_blank"><?php echo $this->item->product->title; ?></a></b>
                            </div>                          
                        </div>
                        <?php endif; ?>

                        <?php echo $this->form->renderFieldset('review'); ?>

                    </div>
                </div>
            </div>
    
            <div class="col-lg-4">
                <?php echo $this->form->renderFieldset('basic'); ?>
                
                <div class="easystore-card">
                    <div class="easystore-card-body">
                        <?php echo $this->form->renderFieldset('publishing'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>