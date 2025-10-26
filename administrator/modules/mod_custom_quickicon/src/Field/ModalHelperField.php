<?php
/*
 *  package: Custom-Quickicons
 *  copyright: Copyright (c) 2024. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 2 or later
 *  link: https://www.joomill-extensions.com
 */

namespace Joomill\Module\Customquickicon\Administrator\Field;

// No direct access.
\defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;


class ModalHelperField extends FormField {

    protected $type = 'ModalHelper';

    public function getInput() {
        $html = IconSelectorField::build('modal', null);
        return $html;
    }
}