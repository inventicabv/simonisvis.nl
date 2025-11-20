<?php

/**
 * @package     EasyStore.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Email;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Email\EmailServiceInterface;
use JoomShaper\Component\EasyStore\Administrator\Email\LinkGeneratorInterface;
use JoomShaper\Component\EasyStore\Administrator\Email\CustomerNameProviderInterface;
use JoomShaper\Component\EasyStore\Site\Helper\OrderHelper;

/**
 * Class EmailManager
 *
 * Manages the sending of different types of emails for orders.
 *
 * @since 1.3.0
 */
class EmailManager
{
    /**
     * @var object $order The order object containing order details.
     * @since 1.3.0
     */
    protected $order;

    /**
     * @var EmailServiceInterface $emailService The email service to send emails.
     * @since 1.3.0
     */
    protected $emailService;

    /**
     * @var LinkGeneratorInterface $orderLinkGenerator Generates a link to the order.
     * @since 1.3.0
     */
    protected $orderLinkGenerator;

    /**
     * @var CustomerNameProviderInterface $customerNameProvider Provides customer name information.
     * @since 1.3.0
     */
    protected $customerNameProvider;

    /**
     * @var array $storeAddress Stores the shop/store address information.
     * @since 1.3.0
     */
    protected $storeAddress;

    /**
     * @var array $additionalData Additional data to be passed to the email template.
     * @since 1.4.4
     */
    protected $additionalData = [];

    /**
     * Constructor for the EmailManager class
     *
     * @param object $order The order object to work with.
     * @param EmailServiceInterface $emailService The email service used to send the emails.
     * @param LinkGeneratorInterface $orderLinkGenerator Service to generate the order link.
     * @param CustomerNameProviderInterface $customerNameProvider Service to provide customer name details.
     *
     * @since 1.3.0
     */
    public function __construct(
        $order,
        EmailServiceInterface $emailService,
        LinkGeneratorInterface $orderLinkGenerator,
        CustomerNameProviderInterface $customerNameProvider
    ) {
        $this->order = $order;
        $this->emailService = $emailService;
        $this->orderLinkGenerator = $orderLinkGenerator;
        $this->customerNameProvider = $customerNameProvider;
        $this->storeAddress = SettingsHelper::getAddress();
    }

    /**
     * Send any type of email by specifying the type and template key
     *
     * @param string $templateKey The key to identify the email template (e.g. 'order_confirmation').
     * @param string $emailType The type of email to be sent (e.g. 'order_confirmation').
     * @param string $customerEmail The email address of the customer.
     * @param string $onEvent The event on email send.
     *
     * @return void
     * @since 1.3.0
     */
    public function sendEmail(string $templateKey, string $emailType, string $customerEmail, string $onEvent)
    {
        if ($this->isEmailEnabled($templateKey)) {
            $variables = $this->prepareEmailVariables();
            $this->emailService->send($variables, $emailType, $customerEmail, $onEvent);
        }
    }

    /**
     * Check if the email is enabled based on the template key.
     *
     * @param string $templateKey The key of the email template to check (e.g. 'order_confirmation').
     *
     * @return bool True if the email template is enabled, false otherwise.
     * @since 1.3.0
     */
    protected function isEmailEnabled(string $templateKey): bool
    {
        return SettingsHelper::isEmailTemplateEnabled($templateKey);
    }

    /**
     * Set additional data for the email template
     *
     * @param array $additionalData An associative array of additional data to be passed to the email template.
     *
     * @return $this
     * @since 1.4.4
     */
    public function setAdditionalData(array $additionalData)
    {
        $this->additionalData = $additionalData;

        return $this;
    }

    /**
     * Prepare common variables for any email template
     *
     * @return array An associative array of variables to be passed to the email template.
     * @since 1.3.0
     */
    protected function prepareEmailVariables(): array
    {
        // Base variables common across all emails
        $baseVariables = [
            'order_id'         => OrderHelper::formatOrderNumber($this->order->id),
            'order_date'       => HTMLHelper::_('date', $this->order->creation_date, Text::_("DATE_FORMAT_LC2")),
            'order_summary'    => LayoutHelper::render('emails.order.summary', (array) $this->order),
            'payment_status'   => EasyStoreHelper::getPaymentStatusString($this->order->payment_status),
            'payment_method'   => EasyStoreHelper::getPaymentMethodString($this->order->payment_method),
            'shipping_method'  => EasyStoreHelper::getShippingMethodString($this->order->shipping ?? null),
            'pickup_date'      => !empty($this->order->pickup_date) ? $this->formatPickupDate($this->order->pickup_date) : '',
            'shipping_address' => LayoutHelper::render('emails.address', (array)$this->order->shipping_address),
            'order_link'       => $this->orderLinkGenerator->generateLink($this->order),
            'customer_name'    => $this->customerNameProvider->getCustomerName($this->order),
            'company_name'     => $this->order->company_name ?? "",
            'customer_email'   => $this->order->customer_email ?? "",
            'company_id'       => $this->order->company_id ?? "",
            'vat_information'  => $this->order->vat_information ?? "",
            'customer_note'    => $this->order->customer_note ?? "",
            'store_name'       => $this->storeAddress['name'],
            'store_email'      => $this->storeAddress['email'],
            'store_phone'      => $this->storeAddress['phone'],
            'store_address'    => LayoutHelper::render('emails.address', $this->storeAddress),
            'seller_tax_id'    => SettingsHelper::getSellerTaxId(),
        ];

        // Merge additional data with base variables (additional data can override base variables)
        return array_merge($baseVariables, $this->additionalData);
    }

    /**
     * Format pickup date without time
     *
     * @param string $date The date string to format
     *
     * @return string Formatted date without time
     * @since 1.0.0
     */
    private function formatPickupDate($date)
    {
        if (empty($date)) {
            return '';
        }

        // Extract only the date part (YYYY-MM-DD) if time is included in the input
        $dateOnly = explode(' ', $date)[0];
        
        // Format using DATE_FORMAT_LC2 (which includes time) and then remove the time part
        $formatted = HTMLHelper::_('date', $dateOnly, Text::_("DATE_FORMAT_LC2"));
        
        // Remove time component (everything from the first occurrence of a time pattern like "01:00" or "1:00")
        // Split by space and keep only parts that don't contain a colon (which indicates time)
        $parts = explode(' ', $formatted);
        $dateParts = [];
        
        foreach ($parts as $part) {
            // If part contains a colon, it's likely a time component, so stop here
            if (strpos($part, ':') !== false) {
                break;
            }
            $dateParts[] = $part;
        }
        
        return implode(' ', $dateParts);
    }
}
