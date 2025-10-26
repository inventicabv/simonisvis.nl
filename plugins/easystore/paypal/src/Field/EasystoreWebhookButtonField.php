<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Plugin\EasyStore\Paypal\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Application\CMSApplication;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * EasyStore Webhook Button field
 *
 * @since   2.0.0
 */
class EasystoreWebhookButtonField extends FormField
{
    /**
     * Field type
     *
     * @var     string  $type
     * @since   2.0.0
     */
    protected $type = 'EasystoreWebhookButton';

    /**
     * Override getInput function form FormField
     *
     * @return  string  Field HTML string
     * @since   2.0.0
     */
    protected function getInput()
    {
        Text::script('PLG_EASYSTORE_PAYPAL_WEBHOOK_ENDPOINT_EXISTS');
        Text::script('PLG_EASYSTORE_PAYPAL_WEBHOOK_ENDPOINT_CREATED');
        Text::script('PLG_EASYSTORE_PAYPAL_WEBHOOK_BUTTON_DESC');
        Text::script('PLG_EASYSTORE_PAYPAL_WEBHOOK_BUTTON');
        Text::script('PLG_EASYSTORE_PAYPAL_WEBHOOK_BUTTON_CREATE');

        /** @var CMSApplication */
        $app      = Factory::getApplication();
        $document = $app->getDocument();
        $document->addScriptOptions('easystore.base', rtrim(Uri::root(), '/'));

        $wa = $document->getWebAssetManager();
        $wa->registerAndUseScript('plg_easystore_stripe.webhook.button', 'media/plg_easystore_paypal/js/webhook-button.js', [], ['defer' => true]);

        $text = !empty($this->element['text']) ? $this->element['text'] : 'Button';

        return '<button type="submit" id="easystore-create-webhook-for-paypal" class="btn btn-success" >' . Text::_($text) . '</button>';
    }
}