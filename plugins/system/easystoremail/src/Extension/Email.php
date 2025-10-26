<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Plugin\System\EasyStoreMail\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Throwable;
use Joomla\CMS\Factory;
use Joomla\CMS\Mail\MailerFactoryInterface;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;

class Email
{
    /**
     * Receiver email address
     *
     * @var string
     */
    protected $receiver;

    /**
     * Email subject
     *
     * @var string
     */
    protected $subject;

    /**
     * Email body content
     *
     * @var string
     */
    protected $body;

    /**
     * The email template type
     *
     * @var string
     */
    protected $type;

    /**
     * The variables key values
     *
     * @var array
     */
    protected $variables;

    /**
     * Whether the email template is enabled or not
     *
     * @var bool
     */
    protected $isEnabled = true;

    /**
     * The email constructor method
     *
     * @param string $receiver
     * @param string|null $type
     */
    public function __construct(string $receiver, $type = null)
    {
        $this->receiver  = $receiver;
        $this->subject   = '';
        $this->type      = $type;
        $this->variables = [];
        $this->isEnabled = true;

        if (!empty($type)) {
            $this->getTemplateContent();
        }
    }

    /**
     * Set the email body content
     *
     * @param string $body
     * @return self
     */
    public function setBody(string $body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Set the email subject
     *
     * @param string $subject
     * @return self
     */
    public function setSubject(string $subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get the email template contents
     *
     * @return void
     */
    protected function getTemplateContent()
    {
        $template = $this->getTemplate();

        if (is_null($template)) {
            $this->body = '';

            return;
        }

        $this->subject   = $template->subject;
        $this->body      = $template->body;
        $this->isEnabled = (bool) $template->is_enabled;
    }

    protected function getTemplates($groups)
    {
        $templates = [];

        foreach ($groups as $group) {
            if (!empty($group->templates)) {
                foreach ($group->templates as $template) {
                    $templates[$template->type] = $template;
                }
            }
        }

        return $templates;
    }

    protected function getTemplate()
    {
        $settings = SettingsHelper::getSettings();
        $groups   = $settings->get('email_templates');

        if (empty($groups)) {
            return null;
        }

        $templates = $this->getTemplates($groups);

        return $templates[$this->type] ?? null;
    }

    /**
     * Bind the variables
     *
     * @param array $variables
     * @return self
     */
    public function bind(array $variables)
    {
        $this->variables = $variables;

        return $this;
    }

    /**
     * Parse and get the emil body by replacing the variables with its original values
     *
     * @return string
     */
    protected function parseContent($content)
    {
        $pattern = "@\{([^}]*)\}@";
        $matches = [];

        preg_match_all($pattern, $content, $matches);

        $contentVariables = [];

        if (!empty($matches[1])) {
            $contentVariables = $matches[1];
        }

        $contentValues = [];

        foreach ($contentVariables as $variableName) {
            if (isset($this->variables[$variableName])) {
                $contentValues[$variableName] = $this->variables[$variableName];
            }
        }

        if (empty($contentValues)) {
            return $content;
        }

        $parsedContent = $content;

        foreach ($contentValues as $key => $value) {
            $searchPattern = "@\{" . $key . "\}@";
            $replaceValue  = str_replace('$', '\$', $value);

            $parsedContent = preg_replace($searchPattern, $replaceValue, $parsedContent);
        }

        return $parsedContent;
    }

    /**
     * Send the email to the receiver
     *
     * @return mixed
     */
    public function send()
    {
        // If the email template is not enabled then skip sending the email.
        if (!$this->isEnabled) {
            return;
        }

        /** @var CMSApplication */
        $app    = Factory::getApplication();
        $config = $app->getConfig();

        $senderEmail = $config->get('mailfrom');
        $senderName  = $config->get('fromname');

        if (empty($senderEmail) || empty($senderName)) {
            throw new Exception('Invalid email configuration', 400);
        }

        $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
        $mailer->isHTML(true);
        $mailer->setSender([$senderEmail, $senderName]);
        $mailer->addRecipient($this->receiver);

        $mailer->setSubject($this->parseContent($this->subject));
        $mailer->setBody($this->parseContent($this->body));

        try {
            return $mailer->Send();
        } catch (Throwable $error) {
            throw $error;
            return false;
        }
    }
}
