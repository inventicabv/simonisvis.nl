<?php

/**
 * @package     EasyStore.Plugin
 * @subpackage  System.EasyStoreAdminMail
 *
 * @copyright   Copyright (C) 2024. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

namespace JoomShaper\Plugin\System\EasyStoreAdminMail\Extension;

use Joomla\CMS\Log\Log;
use Joomla\Event\Event;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use JoomShaper\Component\EasyStore\Site\Lib\Email;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;

\defined('_JEXEC') or die;

/**
 * EasyStore Admin Mail plugin - Zorgt dat administrator maar 1 mail ontvangt met bestelling + betalingsstatus
 *
 * @since  1.0.0
 */
final class EasyStoreAdminMail extends CMSPlugin implements SubscriberInterface
{
    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return array
     *
     * @since   1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onSuccessfulPayment' => ['onSuccessfulPayment', 999], // Hoge prioriteit
            'onFailedPayment'     => ['onFailedPayment', 999],
            'onOrderPlaced'       => ['onOrderPlaced', 999], // Hoge prioriteit om eerst te worden aangeroepen
        ];
    }

    /**
     * Haalt het admin email adres op uit plugin instellingen of store settings
     *
     * @return string Het admin email adres
     *
     * @since   1.0.0
     */
    private function getAdminEmail(): string
    {
        // Check of er een specifiek admin email is ingesteld in plugin settings
        $adminEmail = $this->params->get('admin_email', '');
        
        // Als er geen specifiek email is ingesteld EN use_store_email is aan, gebruik store email
        if (empty($adminEmail) && $this->params->get('use_store_email', 1)) {
            $settings = SettingsHelper::getSettings();
            $adminEmail = $settings->get('general.storeEmail', '');
        }
        
        return $adminEmail;
    }

    /**
     * Controleert of de plugin is ingeschakeld
     *
     * @return bool
     *
     * @since   1.0.0
     */
    private function isEnabled(): bool
    {
        return (bool) $this->params->get('enabled', 1);
    }

    /**
     * Blokkeert admin mail bij order plaatsing (onOrderPlaced event)
     * Voor manual payments wordt de admin mail direct verstuurd
     * Voor online payments wordt de admin mail later verstuurd via onSuccessfulPayment of onFailedPayment
     *
     * @param Event $event The event object
     *
     * @return void
     *
     * @since   1.0.0
     */
    public function onOrderPlaced(Event $event)
    {
        if (!$this->isEnabled()) {
            return;
        }

        $arguments = $event->getArguments();
        $data     = $arguments['subject'] ?? null;

        if (!$data) {
            return;
        }

        // Blokkeer alleen de admin mail, klant mail mag doorgaan
        if (isset($data->type) && $data->type === 'order_confirmation_admin') {
            // Check of dit een manual payment is
            $isManualPayment = false;
            if (isset($data->variables['payment_method'])) {
                $paymentMethod = $data->variables['payment_method'];
                $manualPaymentLists = EasyStoreHelper::getManualPaymentLists();
                $isManualPayment = in_array($paymentMethod, $manualPaymentLists);
            }

            // Stop event propagation zodat de standaard easystoremail plugin deze niet verwerkt
            $event->stopPropagation();

            // Als het een manual payment is EN send_on_manual_payment is aan, verstuur direct de admin mail
            if ($isManualPayment && $this->params->get('send_on_manual_payment', 1)) {
                $adminEmail = $this->getAdminEmail();

                if (!empty($adminEmail) && isset($data->variables)) {
                    // Voor manual payments versturen we order_confirmation_admin met betalingsstatus 'unpaid'
                    $this->deliverEmail($adminEmail, 'order_confirmation_admin', $data->variables);
                    Log::add('EasyStoreAdminMail: Admin mail direct verstuurd voor manual payment naar ' . $adminEmail, Log::INFO, 'email.easystore.adminmail');
                } else {
                    Log::add('EasyStoreAdminMail: Geen admin email adres ingesteld voor manual payment', Log::WARNING, 'email.easystore.adminmail');
                }
            } else {
                Log::add('EasyStoreAdminMail: Admin mail bij order plaatsing geblokkeerd (wordt later verstuurd met betalingsstatus)', Log::INFO, 'email.easystore.adminmail');
            }
        }
    }

    /**
     * Verstuurt admin mail bij succesvolle betaling
     *
     * @param Event $event The event object
     *
     * @return void
     *
     * @since   1.0.0
     */
    public function onSuccessfulPayment(Event $event)
    {
        if (!$this->isEnabled()) {
            return;
        }

        // Check of we moeten versturen bij succesvolle betaling
        if (!$this->params->get('send_on_payment_success', 1)) {
            return;
        }

        $arguments = $event->getArguments();
        $data     = $arguments['subject'] ?? null;

        if (!$data) {
            return;
        }

        // Verstuur admin mail met betalingsstatus
        if (isset($data->type) && $data->type === 'payment_success_admin') {
            $adminEmail = $this->getAdminEmail();

            if (!empty($adminEmail)) {
                $this->deliverEmail($adminEmail, 'payment_success_admin', $data->variables);
                Log::add('EasyStoreAdminMail: Admin mail verstuurd bij succesvolle betaling naar ' . $adminEmail, Log::INFO, 'email.easystore.adminmail');
            } else {
                Log::add('EasyStoreAdminMail: Geen admin email adres ingesteld voor succesvolle betaling', Log::WARNING, 'email.easystore.adminmail');
            }
        }
    }

    /**
     * Verstuurt admin mail bij gefaalde betaling
     *
     * @param Event $event The event object
     *
     * @return void
     *
     * @since   1.0.0
     */
    public function onFailedPayment(Event $event)
    {
        if (!$this->isEnabled()) {
            return;
        }

        // Check of we moeten versturen bij gefaalde betaling
        if (!$this->params->get('send_on_payment_failed', 1)) {
            return;
        }

        $arguments = $event->getArguments();
        $data     = $arguments['subject'] ?? null;

        if (!$data) {
            return;
        }

        $adminEmail = $this->getAdminEmail();

        if (!empty($adminEmail) && isset($data->variables)) {
            // Verstuur order_confirmation_admin met betalingsstatus (unpaid/failed)
            $this->deliverEmail($adminEmail, 'order_confirmation_admin', $data->variables);
            Log::add('EasyStoreAdminMail: Admin mail verstuurd bij gefaalde betaling naar ' . $adminEmail, Log::INFO, 'email.easystore.adminmail');
        } else {
            Log::add('EasyStoreAdminMail: Geen admin email adres ingesteld voor gefaalde betaling', Log::WARNING, 'email.easystore.adminmail');
        }
    }

    /**
     * Verstuurt een email met de opgegeven details.
     *
     * @param  string $email   Het email adres om naar te versturen.
     * @param  string $type    Het type email om te versturen.
     * @param  array  $contents De inhoud om te binden aan de email.
     *
     * @return void
     *
     * @since  1.0.0
     */
    private function deliverEmail($email, $type, $contents)
    {
        try {
            $emailObj = new Email($email, $type);
            $emailObj->bind($contents)->send();
            Log::add('EasyStoreAdminMail: Email succesvol verstuurd - ' . $type . ' naar ' . $email, Log::INFO, 'email.easystore.adminmail');
        } catch (\Exception $e) {
            Log::add('EasyStoreAdminMail: Email versturen gefaald - ' . $type . ': ' . $e->getMessage(), Log::ERROR, 'email.easystore.adminmail');
        }
    }
}

