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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use JoomShaper\Component\EasyStore\Site\Lib\Email;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Site\Helper\OrderHelper;
use JoomShaper\Component\EasyStore\Administrator\Model\OrderModel;
use JoomShaper\Component\EasyStore\Administrator\Email\EmailManager;
use JoomShaper\Component\EasyStore\Administrator\Email\EmailService;
use JoomShaper\Component\EasyStore\Administrator\Email\OrderLinkGenerator;
use JoomShaper\Component\EasyStore\Administrator\Email\CustomerNameProvider;
use JoomShaper\Component\EasyStore\Administrator\Checkout\OrderManager;
use Joomla\CMS\Layout\LayoutHelper;

\defined('_JEXEC') or die;

/**
 * EasyStore Admin Mail plugin - Zorgt dat administrator maar 1 mail ontvangt met bestelling + betalingsstatus
 *
 * @since  1.0.0
 */
final class EasyStoreAdminMail extends CMSPlugin implements SubscriberInterface
{
    /**
     * Flag om bij te houden of we zelf een admin mail versturen
     * Dit voorkomt dat onze plugin de mail blokkeert wanneer we deze zelf versturen
     *
     * @var bool
     * @since   1.0.0
     */
    private static $isSendingAdminMail = false;
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
            'onEasystorePaymentSuccessRender' => ['onEasystorePaymentSuccessRender', 999], // Bedankt pagina event
            'onEasystorePaymentComplete' => ['onEasystorePaymentComplete', 999], // Bedankt pagina event
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
     * Voor Mollie/online payments wordt de admin mail direct verstuurd wanneer iemand op de bedankt pagina komt
     * Voor andere online payments wordt de admin mail later verstuurd via onSuccessfulPayment of onFailedPayment
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
        // Maar niet als we zelf de mail versturen (via EmailManager)
        if (isset($data->type) && $data->type === 'order_confirmation_admin' && !self::$isSendingAdminMail) {
            // Check of dit een manual payment is
            $isManualPayment = false;
            $isMolliePayment = false;
            $isOnlinePayment = false;
            
            if (isset($data->variables['payment_method'])) {
                $paymentMethod = $data->variables['payment_method'];
                $manualPaymentLists = EasyStoreHelper::getManualPaymentLists();
                $isManualPayment = in_array($paymentMethod, $manualPaymentLists);
                
                // Check of het een Mollie betaling is
                if (stripos($paymentMethod, 'mollie') !== false) {
                    $isMolliePayment = true;
                    $isOnlinePayment = true;
                } elseif (!$isManualPayment) {
                    // Als het geen manual payment is, is het waarschijnlijk een online payment
                    $isOnlinePayment = true;
                }
            }

            // Stop event propagation zodat de standaard easystoremail plugin deze niet verwerkt
            $event->stopPropagation();

            $adminEmail = $this->getAdminEmail();

            // Als het een manual payment is EN send_on_manual_payment is aan, verstuur direct de admin mail
            if ($isManualPayment && $this->params->get('send_on_manual_payment', 1)) {
                Log::add('EasyStoreAdminMail: Manual payment gedetecteerd, start verwerking', Log::INFO, 'email.easystore.adminmail');
                
                if (!empty($adminEmail) && isset($data->variables)) {
                    // Debug: log beschikbare variabelen
                    $availableKeys = array_keys($data->variables);
                    Log::add('EasyStoreAdminMail: Beschikbare variabelen keys: ' . implode(', ', $availableKeys), Log::INFO, 'email.easystore.adminmail');
                    
                    // Haal order ID op uit variabelen
                    $orderId = null;
                    
                    // Probeer verschillende manieren om het order ID te vinden
                    if (isset($data->variables['id']) && is_numeric($data->variables['id'])) {
                        $orderId = (int) $data->variables['id'];
                        Log::add('EasyStoreAdminMail: Order ID gevonden via variables[id]: ' . $orderId, Log::INFO, 'email.easystore.adminmail');
                    } elseif (isset($data->variables['order_id'])) {
                        $orderIdStr = $data->variables['order_id'];
                        Log::add('EasyStoreAdminMail: order_id gevonden: ' . $orderIdStr, Log::INFO, 'email.easystore.adminmail');
                        
                        // Als het een nummer is, gebruik het direct
                        if (is_numeric($orderIdStr)) {
                            $orderId = (int) $orderIdStr;
                            Log::add('EasyStoreAdminMail: Order ID is numeriek: ' . $orderId, Log::INFO, 'email.easystore.adminmail');
                        } else {
                            // order_id kan een geformatteerd nummer zijn (bijv. F-2025-001051)
                            // Probeer het ID te extraheren
                            $orderId = $this->getOrderIdFromOrderNumber($orderIdStr);
                            if ($orderId) {
                                Log::add('EasyStoreAdminMail: Order ID geëxtraheerd uit order number: ' . $orderId, Log::INFO, 'email.easystore.adminmail');
                            }
                        }
                    }
                    
                    // Als we nog steeds geen order ID hebben, probeer het te vinden via de database
                    if (!$orderId && isset($data->variables['order_id'])) {
                        Log::add('EasyStoreAdminMail: Probeer order ID te vinden via database', Log::INFO, 'email.easystore.adminmail');
                        $orderId = $this->findOrderIdByOrderNumber($data->variables['order_id']);
                        if ($orderId) {
                            Log::add('EasyStoreAdminMail: Order ID gevonden via database: ' . $orderId, Log::INFO, 'email.easystore.adminmail');
                        }
                    }
                    
                    if ($orderId) {
                        Log::add('EasyStoreAdminMail: Order ID gevonden voor manual payment: ' . $orderId, Log::INFO, 'email.easystore.adminmail');
                        // Gebruik dezelfde methode als voor Mollie - haal volledig order object op en gebruik EmailManager
                        $this->sendAdminMailForOrder($orderId, 'manual payment (onOrderPlaced)');
                    } else {
                        // Fallback naar oude methode als we geen order ID kunnen vinden
                        Log::add('EasyStoreAdminMail: Geen order ID gevonden in variabelen. Beschikbare keys: ' . json_encode($availableKeys), Log::WARNING, 'email.easystore.adminmail');
                        Log::add('EasyStoreAdminMail: Variabelen content (gedeeltelijk): ' . json_encode(array_slice($data->variables, 0, 5)), Log::WARNING, 'email.easystore.adminmail');
                        $this->deliverEmail($adminEmail, 'order_confirmation_admin', $data->variables);
                        Log::add('EasyStoreAdminMail: Admin mail direct verstuurd voor manual payment (fallback) naar ' . $adminEmail, Log::INFO, 'email.easystore.adminmail');
                    }
                } else {
                    Log::add('EasyStoreAdminMail: Geen admin email adres of variabelen beschikbaar voor manual payment', Log::WARNING, 'email.easystore.adminmail');
                }
            }
            // Als het een Mollie betaling is, check of iemand op de bedankt pagina komt
            // Dit kunnen we detecteren door te checken of er een payment status is of een callback parameter
            elseif ($isMolliePayment && !empty($adminEmail) && isset($data->variables)) {
                // Check of dit een callback is (bedankt pagina) door te kijken naar payment status of callback indicatoren
                $isCallback = false;
                
                // Check op verschillende indicatoren dat dit een callback/bedankt pagina is
                if (isset($data->variables['payment_status']) || 
                    isset($data->variables['status']) ||
                    isset($data->variables['callback']) ||
                    isset($data->variables['return_url']) ||
                    (isset($data->variables['order_id']) && isset($data->variables['payment_method']))) {
                    $isCallback = true;
                }
                
                // Als het een callback is (bedankt pagina), verstuur direct de admin mail
                // Of de betaling succesvol is of niet, maakt niet uit - verstuur altijd
                if ($isCallback) {
                    // Zorg ervoor dat de betalingsstatus wordt meegestuurd
                    if (!isset($data->variables['payment_status'])) {
                        if (isset($data->variables['status'])) {
                            $data->variables['payment_status'] = $data->variables['status'];
                        } else {
                            // Standaard: onbekend, maar verstuur toch de mail
                            $data->variables['payment_status'] = 'unknown';
                        }
                    }
                    
                    $this->deliverEmail($adminEmail, 'order_confirmation_admin', $data->variables);
                    $orderId = $data->variables['order_id'] ?? $data->variables['id'] ?? 'onbekend';
                    Log::add('EasyStoreAdminMail: Admin mail direct verstuurd voor Mollie betaling (bedankt pagina) naar ' . $adminEmail . ' voor order: ' . $orderId, Log::INFO, 'email.easystore.adminmail');
                } else {
                    // Eerste keer order plaatsing, blokkeer de mail (wordt later verstuurd)
                    Log::add('EasyStoreAdminMail: Admin mail bij Mollie order plaatsing geblokkeerd (wordt later verstuurd bij bedankt pagina)', Log::INFO, 'email.easystore.adminmail');
                }
            }
            // Voor andere online payments, blokkeer de mail (wordt later verstuurd via onSuccessfulPayment of onFailedPayment)
            else {
                Log::add('EasyStoreAdminMail: Admin mail bij order plaatsing geblokkeerd (wordt later verstuurd met betalingsstatus)', Log::INFO, 'email.easystore.adminmail');
            }
        }
    }

    /**
     * Verstuurt admin mail bij succesvolle betaling
     * Werkt ook voor Mollie betalingen wanneer iemand op de bedankt pagina komt
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

        $adminEmail = $this->getAdminEmail();

        if (empty($adminEmail)) {
            Log::add('EasyStoreAdminMail: Geen admin email adres ingesteld voor succesvolle betaling', Log::WARNING, 'email.easystore.adminmail');
            return;
        }

        // Check of dit een payment_success_admin type is (standaard geval)
        if (isset($data->type) && $data->type === 'payment_success_admin' && isset($data->variables)) {
            $this->deliverEmail($adminEmail, 'payment_success_admin', $data->variables);
            Log::add('EasyStoreAdminMail: Admin mail verstuurd bij succesvolle betaling (payment_success_admin) naar ' . $adminEmail, Log::INFO, 'email.easystore.adminmail');
            return;
        }

        // Voor Mollie en andere online betalingen: check of er order informatie beschikbaar is
        // Dit gebeurt wanneer de webhook de betalingsstatus heeft bijgewerkt
        if (isset($data->variables) && is_array($data->variables)) {
            // Check of er order informatie is (order_id, order_number, etc.)
            $hasOrderInfo = false;
            $orderId = null;
            
            if (isset($data->variables['order_id'])) {
                $orderId = $data->variables['order_id'];
                $hasOrderInfo = true;
            } elseif (isset($data->variables['id'])) {
                $orderId = $data->variables['id'];
                $hasOrderInfo = true;
            } elseif (isset($data->variables['order_number'])) {
                $hasOrderInfo = true;
            }

            // Als er order informatie is, verstuur de admin mail
            // Gebruik order_confirmation_admin zodat alle order details worden meegestuurd
            if ($hasOrderInfo) {
                // Zorg ervoor dat de betalingsstatus wordt meegestuurd
                if (!isset($data->variables['payment_status'])) {
                    // Probeer betalingsstatus te bepalen
                    if (isset($data->variables['status'])) {
                        $data->variables['payment_status'] = $data->variables['status'];
                    } else {
                        // Standaard: als onSuccessfulPayment wordt aangeroepen, is de betaling waarschijnlijk succesvol
                        $data->variables['payment_status'] = 'paid';
                    }
                }
                
                // Voor Mollie: gebruik sendAdminMailForOrder om volledig order object op te halen met correcte betalingsstatus
                // Dit is belangrijk omdat de webhook de status heeft bijgewerkt
                $paymentMethod = $data->variables['payment_method'] ?? '';
                if (!empty($orderId) && stripos($paymentMethod, 'mollie') !== false) {
                    Log::add('EasyStoreAdminMail: Mollie betaling - verstuur admin mail via webhook trigger (onSuccessfulPayment)', Log::INFO, 'email.easystore.adminmail');
                    $this->sendAdminMailForOrder($orderId, 'webhook (onSuccessfulPayment) - Mollie');
                } else {
                    // Voor andere betalingen: gebruik de oude methode
                    $this->deliverEmail($adminEmail, 'order_confirmation_admin', $data->variables);
                    Log::add('EasyStoreAdminMail: Admin mail verstuurd bij online betaling naar ' . $adminEmail . ' voor order: ' . ($orderId ?? 'onbekend'), Log::INFO, 'email.easystore.adminmail');
                }
            }
        }
    }

    /**
     * Verstuurt admin mail bij gefaalde betaling
     * Werkt ook voor Mollie betalingen wanneer iemand op de bedankt pagina komt met gefaalde betaling
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

        if (empty($adminEmail)) {
            Log::add('EasyStoreAdminMail: Geen admin email adres ingesteld voor gefaalde betaling', Log::WARNING, 'email.easystore.adminmail');
            return;
        }

        // Verstuur admin mail met betalingsstatus (unpaid/failed)
        if (isset($data->variables) && is_array($data->variables)) {
            // Zorg ervoor dat de betalingsstatus wordt meegestuurd
            if (!isset($data->variables['payment_status'])) {
                // Probeer betalingsstatus te bepalen
                if (isset($data->variables['status'])) {
                    $data->variables['payment_status'] = $data->variables['status'];
                } else {
                    // Standaard: als onFailedPayment wordt aangeroepen, is de betaling gefaald
                    $data->variables['payment_status'] = 'failed';
                }
            }
            
            $orderId = $data->variables['order_id'] ?? $data->variables['id'] ?? null;
            
            // Voor Mollie: gebruik sendAdminMailForOrder om volledig order object op te halen met correcte betalingsstatus
            // Dit is belangrijk omdat de webhook de status heeft bijgewerkt
            $paymentMethod = $data->variables['payment_method'] ?? '';
            if (!empty($orderId) && stripos($paymentMethod, 'mollie') !== false) {
                Log::add('EasyStoreAdminMail: Mollie betaling - verstuur admin mail via webhook trigger (onFailedPayment)', Log::INFO, 'email.easystore.adminmail');
                $this->sendAdminMailForOrder($orderId, 'webhook (onFailedPayment) - Mollie');
            } else {
                // Voor andere betalingen: gebruik de oude methode
                $this->deliverEmail($adminEmail, 'order_confirmation_admin', $data->variables);
                Log::add('EasyStoreAdminMail: Admin mail verstuurd bij gefaalde betaling naar ' . $adminEmail . ' voor order: ' . ($orderId ?? 'onbekend'), Log::INFO, 'email.easystore.adminmail');
            }
        }
    }

    /**
     * Verstuurt admin mail wanneer iemand op de bedankt pagina komt (onEasystorePaymentSuccessRender event)
     * Dit event wordt getriggerd wanneer iemand via Mollie of andere online betalingen op de bedankt pagina komt
     * 
     * NOTE: We gebruiken alleen onEasystorePaymentComplete om dubbele mails te voorkomen
     *
     * @param Event $event The event object
     *
     * @return void
     *
     * @since   1.0.0
     */
    public function onEasystorePaymentSuccessRender(Event $event)
    {
        // Negeer dit event - we gebruiken alleen onEasystorePaymentComplete om dubbele mails te voorkomen
        return;
    }

    /**
     * Verstuurt admin mail wanneer iemand op de bedankt pagina komt (onEasystorePaymentComplete event)
     * Dit event wordt getriggerd wanneer iemand via Mollie of andere online betalingen op de bedankt pagina komt
     *
     * @param Event $event The event object
     *
     * @return void
     *
     * @since   1.0.0
     */
    public function onEasystorePaymentComplete(Event $event)
    {
        Log::add('EasyStoreAdminMail: onEasystorePaymentComplete event ontvangen', Log::INFO, 'email.easystore.adminmail');
        
        if (!$this->isEnabled()) {
            Log::add('EasyStoreAdminMail: Plugin is niet ingeschakeld', Log::WARNING, 'email.easystore.adminmail');
            return;
        }

        $arguments = $event->getArguments();
        $orderId = $arguments['orderId'] ?? null;
        $paymentType = $arguments['paymentType'] ?? '';

        Log::add('EasyStoreAdminMail: orderId=' . ($orderId ?? 'NULL') . ', paymentType=' . ($paymentType ?? 'NULL'), Log::INFO, 'email.easystore.adminmail');

        if (empty($orderId)) {
            Log::add('EasyStoreAdminMail: orderId is leeg, stop verwerking', Log::WARNING, 'email.easystore.adminmail');
            return;
        }

        // Check of dit een manual payment is
        $isManualPayment = false;
        $isMolliePayment = false;
        if (!empty($paymentType)) {
            $manualPaymentLists = EasyStoreHelper::getManualPaymentLists();
            $isManualPayment = in_array($paymentType, $manualPaymentLists);
            
            // Check of het een Mollie betaling is
            if (stripos($paymentType, 'mollie') !== false) {
                $isMolliePayment = true;
            }
            
            Log::add('EasyStoreAdminMail: isManualPayment=' . ($isManualPayment ? 'true' : 'false') . ', isMolliePayment=' . ($isMolliePayment ? 'true' : 'false'), Log::INFO, 'email.easystore.adminmail');
        }

        // Voor Mollie betalingen: NIET versturen bij bedankt pagina
        // De webhook heeft de betalingsstatus nog niet bijgewerkt op dit moment
        // De mail wordt later verstuurd via onSuccessfulPayment of onFailedPayment (webhook triggers)
        if ($isMolliePayment) {
            Log::add('EasyStoreAdminMail: Mollie betaling gedetecteerd - admin mail wordt NIET verstuurd bij bedankt pagina (wordt verstuurd via webhook)', Log::INFO, 'email.easystore.adminmail');
            return;
        }

        // Voor ALLE andere betalingen (manual en andere online), verstuur direct de admin mail
        // Dit is belangrijk omdat de winkeleigenaar altijd de bestelling moet ontvangen
        // Voor manual payments wordt dit event getriggerd wanneer iemand op de bedankt pagina komt
        Log::add('EasyStoreAdminMail: Start versturen admin mail voor ' . ($isManualPayment ? 'manual' : 'online') . ' betaling', Log::INFO, 'email.easystore.adminmail');
        $this->sendAdminMailForOrder($orderId, 'bedankt pagina (onEasystorePaymentComplete) - ' . ($isManualPayment ? 'manual payment' : 'online payment'));
    }

    /**
     * Haalt order informatie op en verstuurt admin mail
     *
     * @param int $orderId Het order ID
     * @param string $context De context waarom de mail wordt verstuurd (voor logging)
     *
     * @return void
     *
     * @since   1.0.0
     */
    private function sendAdminMailForOrder($orderId, $context = '')
    {
        Log::add('EasyStoreAdminMail: sendAdminMailForOrder aangeroepen - orderId=' . $orderId . ', context=' . $context, Log::INFO, 'email.easystore.adminmail');
        
        $adminEmail = $this->getAdminEmail();

        if (empty($adminEmail)) {
            Log::add('EasyStoreAdminMail: Geen admin email adres ingesteld voor ' . $context, Log::WARNING, 'email.easystore.adminmail');
            return;
        }

        Log::add('EasyStoreAdminMail: Admin email adres: ' . $adminEmail, Log::INFO, 'email.easystore.adminmail');

        try {
            // Zorg ervoor dat orderId een integer is
            $orderId = (int) $orderId;
            
            // Haal order op uit database
            $order = $this->getOrderById($orderId);

            if (!$order) {
                Log::add('EasyStoreAdminMail: Order niet gevonden voor order ID: ' . $orderId, Log::WARNING, 'email.easystore.adminmail');
                return;
            }

            Log::add('EasyStoreAdminMail: Order gevonden - ID: ' . ($order->id ?? 'N/A'), Log::INFO, 'email.easystore.adminmail');

            // Bereid email variabelen voor
            $variables = $this->prepareEmailVariables($order);
            
            Log::add('EasyStoreAdminMail: Email variabelen voorbereid - aantal variabelen: ' . count($variables), Log::INFO, 'email.easystore.adminmail');

            // Verstuur de admin mail via EmailManager (zoals in Notifiable trait)
            // Dit zorgt ervoor dat alle variabelen correct worden voorbereid
            try {
                // Zet flag zodat onze plugin deze mail niet blokkeert
                self::$isSendingAdminMail = true;
                
                $emailManager = new EmailManager(
                    $order,
                    new EmailService(),
                    new OrderLinkGenerator(),
                    new CustomerNameProvider()
                );
                
                // Gebruik onOrderPlaced event - onze plugin blokkeert dit niet omdat de flag is gezet
                $emailManager->sendEmail('order_confirmation_admin', 'order_confirmation_admin', $adminEmail, 'onOrderPlaced');
                
                // Reset flag
                self::$isSendingAdminMail = false;
                
                Log::add('EasyStoreAdminMail: Admin mail verstuurd via EmailManager voor ' . $context . ' naar ' . $adminEmail . ' voor order: ' . $orderId, Log::INFO, 'email.easystore.adminmail');
            } catch (\Exception $emailException) {
                // Reset flag bij fout
                self::$isSendingAdminMail = false;
                // Fallback naar directe Email class als EmailManager faalt
                Log::add('EasyStoreAdminMail: EmailManager faalde, gebruik fallback - ' . $emailException->getMessage(), Log::WARNING, 'email.easystore.adminmail');
                $this->deliverEmail($adminEmail, 'order_confirmation_admin', $variables);
                Log::add('EasyStoreAdminMail: Admin mail verstuurd via fallback voor ' . $context . ' naar ' . $adminEmail . ' voor order: ' . $orderId, Log::INFO, 'email.easystore.adminmail');
            }
        } catch (\Exception $e) {
            Log::add('EasyStoreAdminMail: Fout bij versturen admin mail voor ' . $context . ' - Order ID: ' . $orderId . ' - Fout: ' . $e->getMessage() . ' - Stack trace: ' . $e->getTraceAsString(), Log::ERROR, 'email.easystore.adminmail');
        }
    }

    /**
     * Haalt order ID op uit order number (bijv. F-2025-001051)
     * Probeert het ID te extraheren uit het geformatteerde nummer
     *
     * @param string $orderNumber Het order number
     *
     * @return int|null Het order ID of null als niet gevonden
     *
     * @since   1.0.0
     */
    private function getOrderIdFromOrderNumber($orderNumber)
    {
        try {
            // Als het al een nummer is, retourneer het direct
            if (is_numeric($orderNumber)) {
                return (int) $orderNumber;
            }
            
            // Probeer het laatste deel van het order number (na de laatste -)
            // Bijvoorbeeld: F-2025-001051 -> 001051 -> 1051
            $parts = explode('-', $orderNumber);
            if (!empty($parts)) {
                $lastPart = end($parts);
                // Verwijder leading zeros en probeer het als ID
                $lastPart = ltrim($lastPart, '0');
                if (is_numeric($lastPart) && $lastPart > 0) {
                    return (int) $lastPart;
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::add('EasyStoreAdminMail: Fout bij ophalen order ID uit order number - ' . $e->getMessage(), Log::WARNING, 'email.easystore.adminmail');
            return null;
        }
    }

    /**
     * Zoekt order ID op basis van order number in de database
     *
     * @param string $orderNumber Het order number (geformatteerd)
     *
     * @return int|null Het order ID of null als niet gevonden
     *
     * @since   1.0.0
     */
    private function findOrderIdByOrderNumber($orderNumber)
    {
        try {
            // Probeer eerst getOrderIdFromOrderNumber
            $orderId = $this->getOrderIdFromOrderNumber($orderNumber);
            if ($orderId) {
                // Verifieer dat het order bestaat
                $db = Factory::getContainer()->get(DatabaseInterface::class);
                $query = $db->getQuery(true);
                $query->select('id')
                    ->from($db->quoteName('#__easystore_orders'))
                    ->where($db->quoteName('id') . ' = ' . (int) $orderId)
                    ->where($db->quoteName('published') . ' = 1');
                $db->setQuery($query);
                $result = $db->loadResult();
                if ($result) {
                    return (int) $result;
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::add('EasyStoreAdminMail: Fout bij zoeken order ID in database - ' . $e->getMessage(), Log::WARNING, 'email.easystore.adminmail');
            return null;
        }
    }

    /**
     * Haalt volledig order object op via OrderManager (zoals in Notifiable trait)
     * Dit geeft het volledige order object met products, totals, etc.
     *
     * @param int $orderId Het order ID
     *
     * @return object|null Het volledige order object of null als niet gevonden
     *
     * @since   1.0.0
     */
    private function getOrderById($orderId)
    {
        try {
            // Haal eerst basis order op om customer_id te krijgen
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);
            $query->select('customer_id, order_token, is_guest_order')
                ->from($db->quoteName('#__easystore_orders'))
                ->where($db->quoteName('id') . ' = ' . (int) $orderId)
                ->where($db->quoteName('published') . ' = 1');
            
            $db->setQuery($query);
            $orderData = $db->loadObject();

            if (!$orderData) {
                Log::add('EasyStoreAdminMail: Order niet gevonden in database - Order ID: ' . $orderId, Log::WARNING, 'email.easystore.adminmail');
                return null;
            }

            // Haal customer op
            $customer = EasyStoreHelper::getCustomerById($orderData->customer_id ?? 0);
            $token = $orderData->order_token ?? null;

            // Gebruik OrderManager om volledig order object op te halen (zoals in Notifiable trait)
            $orderManager = OrderManager::createWith($orderId, $customer, $token);
            $order = $orderManager->getOrderItemWithCalculation();

            if (!$order) {
                Log::add('EasyStoreAdminMail: OrderManager kon order niet ophalen - Order ID: ' . $orderId, Log::WARNING, 'email.easystore.adminmail');
                return null;
            }

            // Voeg order_number toe
            $order->order_number = OrderHelper::formatOrderNumber($order->id);

            Log::add('EasyStoreAdminMail: Volledig order object opgehaald - Order ID: ' . $orderId . ', Products: ' . (isset($order->products) ? count($order->products) : 0), Log::INFO, 'email.easystore.adminmail');

            return $order;
        } catch (\Exception $e) {
            Log::add('EasyStoreAdminMail: Fout bij ophalen order - Order ID: ' . $orderId . ' - Fout: ' . $e->getMessage() . ' - Stack: ' . $e->getTraceAsString(), Log::ERROR, 'email.easystore.adminmail');
            return null;
        }
    }

    /**
     * Bereidt email variabelen voor op basis van order informatie
     * 
     * NOTE: Deze methode wordt alleen gebruikt als fallback. Normaal gebruikt EmailManager prepareEmailVariables()
     *
     * @param object $order Het order object
     *
     * @return array De email variabelen
     *
     * @since   1.0.0
     */
    private function prepareEmailVariables($order)
    {
        // Basis variabelen (fallback - EmailManager doet dit beter)
        $variables = [
            'order_id' => OrderHelper::formatOrderNumber($order->id),
            'id' => $order->id,
            'order_date' => HTMLHelper::_('date', $order->creation_date, Text::_("DATE_FORMAT_LC2")),
            'payment_status' => EasyStoreHelper::getPaymentStatusString($order->payment_status ?? 'unpaid'),
            'payment_method' => EasyStoreHelper::getPaymentMethodString($order->payment_method ?? ''),
            'shipping_method' => EasyStoreHelper::getShippingMethodString($order->shipping ?? null),
            'customer_email' => $order->customer_email ?? '',
            'company_name' => $order->company_name ?? '',
            'vat_information' => $order->vat_information ?? '',
            'customer_note' => $order->customer_note ?? '',
            'store_name' => SettingsHelper::getSettings()->get('general.storeName', ''),
            'store_email' => SettingsHelper::getSettings()->get('general.storeEmail', ''),
            'store_phone' => SettingsHelper::getSettings()->get('general.storePhone', ''),
            'seller_tax_id' => SettingsHelper::getSellerTaxId(),
        ];

        // Voeg order summary toe (vereist voor email template)
        try {
            // Probeer de order summary layout te renderen
            $variables['order_summary'] = LayoutHelper::render('emails.order.summary', (array) $order);
        } catch (\Exception $e) {
            // Als het renderen faalt, gebruik een simpele versie
            $variables['order_summary'] = 'Order #' . $variables['order_id'];
            Log::add('EasyStoreAdminMail: Kon order summary niet renderen - ' . $e->getMessage(), Log::WARNING, 'email.easystore.adminmail');
        }

        // Voeg shipping address toe
        try {
            if (isset($order->shipping_address)) {
                $variables['shipping_address'] = LayoutHelper::render('emails.address', (array) $order->shipping_address);
            }
        } catch (\Exception $e) {
            // Als het renderen faalt, gebruik het object direct
            $variables['shipping_address'] = $order->shipping_address ?? '';
        }

        // Voeg store address toe
        try {
            $storeAddress = SettingsHelper::getAddress();
            if (!empty($storeAddress)) {
                $variables['store_address'] = LayoutHelper::render('emails.address', $storeAddress);
            }
        } catch (\Exception $e) {
            // Negeer fout
        }

        // Voeg customer name toe
        try {
            $customer = EasyStoreHelper::getCustomerById($order->customer_id ?? 0);
            if ($customer) {
                $variables['customer_name'] = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
            }
        } catch (\Exception $e) {
            // Negeer fout
        }

        // Voeg order link toe
        try {
            $variables['order_link'] = Route::_('index.php?option=com_easystore&view=order&id=' . $order->id, false, Route::TLS_IGNORE, true);
        } catch (\Exception $e) {
            // Negeer fout
        }

        return $variables;
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

