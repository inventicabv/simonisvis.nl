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

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Field\ListField;

/**
 * EasystoreModal field.
 *
 * @since  1.0.0
 */
class EasystoreModalField extends ListField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'EasystoreModal';

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   1.6
     */
    protected function getInput()
    {
        $view       = (string) $this->element['view'];
        $layout     = (string) $this->element['layout'];
        $modalTitle = (string) $this->element['modal_title'];
        $html       = [];
        $link       = Route::_('index.php?option=com_easystore&view=' . $view . '&tmpl=component' . '&layout=' . $layout);
        $html[]     = '<button type="button" data-bs-target="#typePopupModal" class="btn btn-primary" data-bs-toggle="modal">'
            . '<span class="icon-list icon-white" aria-hidden="true"></span> '
            . Text::_('JSELECT') . '</button>';
        $html[]     = HTMLHelper::_(
            'bootstrap.renderModal',
            'typePopupModal',
            [
                'url'        => $link,
                'title'      => $modalTitle,
                'width'      => '800px',
                'height'     => '300px',
                'modalWidth' => 80,
                'bodyHeight' => 70,
                'footer'     => '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'
                        . Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>',
            ]
        );

        // This hidden field has an ID so it can be used for showon attributes
        $html[]     = '<input type="hidden" name="' . $this->name . '" value="'
            . htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '" id="' . $this->id . '_val">';

        return implode("\n", $html);
    }
}
