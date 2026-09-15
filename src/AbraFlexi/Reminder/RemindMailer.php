<?php

declare(strict_types=1);

/**
 * This file is part of the AbraFlexi Reminder package
 *
 * https://github.com/VitexSoftware/abraflexi-reminder
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AbraFlexi\Reminder;

use Ease\Html\BodyTag;
use Ease\Html\HtmlTag;
use Ease\Html\SimpleHeadTag;
use Ease\Html\TitleTag;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * AbraFlexi Reminder's Mailer.
 *
 * Builds & sends email using Symfony Mailer/Mime, configured via the
 * MAIL_DSN / MAIL_FROM environment variables (see the "Mail" credential
 * prototype provided by vitexsoftware/multiflexi-mail).
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2025 Vitex Software
 *
 * @no-named-arguments
 */
class RemindMailer extends \Ease\Sand
{
    /**
     * List off attachments to clean.
     *
     * @var array<string>
     */
    public array $attachments = [];

    /**
     * Sender's email address.
     */
    public string $emailAddress = '';

    /**
     * Subject of email.
     */
    public string $emailSubject = '';

    /**
     * Sender's email address.
     */
    public string $fromEmailAddress = '';

    /**
     * Show user information about sending a message?
     */
    public bool $notify = true;

    /**
     * Has the message already been sent?
     */
    public ?bool $sendResult = false;

    /**
     * Page object for rendering to email.
     */
    public HtmlTag $htmlDocument;
    public ?SimpleHeadTag $htmlHead = null;

    /**
     * Pointer to the BODY html document.
     */
    public BodyTag $htmlBody;

    /**
     * Mail Headers.
     *
     * @var array<string, string>
     */
    public array $mailHeaders = [];
    public bool $finalized = false;

    /**
     * Has the mail already been rendered/included ?
     */
    public bool $drawStatus = false;
    private Email $email;
    private SymfonyMailer $mailer;

    /**
     * Send Remind by mail.
     */
    public function __construct(string $sendTo = '', string $subject = '')
    {
        if (strtolower(\Ease\Shared::cfg('MUTE', 'false')) === 'true') {
            $sendTo = \Ease\Shared::cfg('EASE_EMAILTO', get_current_user().'@'.gethostname());
        }

        $this->fromEmailAddress = \Ease\Shared::cfg('REMIND_FROM', \Ease\Shared::cfg('MAIL_FROM', ''));

        $this->setMailHeaders([
            'To' => $sendTo,
            'From' => $this->fromEmailAddress,
            'Reply-To' => $this->fromEmailAddress,
            'Subject' => $subject,
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Transfer-Encoding' => '8bit',
        ]);

        if (\Ease\Shared::cfg('MAIL_CC')) {
            $this->setMailHeaders(['Cc' => \Ease\Shared::cfg('MAIL_CC')]);
        }

        $this->setObjectName();

        $this->htmlDocument = new HtmlTag(new SimpleHeadTag([
            new TitleTag($this->emailSubject),
            '<style>'.Upominka::$styles.'</style>',
        ]));
        $body = $this->htmlDocument->addItem(new BodyTag());
        $this->htmlBody = $body instanceof BodyTag ? $body : new BodyTag();

        $dsn = \Ease\Shared::cfg('MAIL_DSN', '');

        if (empty($dsn)) {
            $dsn = 'sendmail://default';
        }

        $transport = Transport::fromDsn($dsn);
        $this->mailer = new SymfonyMailer($transport);
        $this->email = new Email();
    }

    /**
     * Returns the contents of the mail header.
     *
     * @param string $headername header name
     *
     * @return string
     */
    public function getMailHeader(string $headername)
    {
        return \array_key_exists($headername, $this->mailHeaders) ? $this->mailHeaders[$headername] : '';
    }

    /**
     * Sets mail headers.
     *
     * @param array<string, string> $mailHeaders associative array of headers
     *
     * @return bool true if the headers have been set
     */
    public function setMailHeaders(array $mailHeaders): bool
    {
        $this->mailHeaders = array_merge($this->mailHeaders, $mailHeaders);

        if (isset($this->mailHeaders['To'])) {
            $this->emailAddress = $this->mailHeaders['To'];
        }

        if (isset($this->mailHeaders['From'])) {
            $this->fromEmailAddress = $this->mailHeaders['From'];
        }

        if (isset($this->mailHeaders['Subject'])) {
            $this->emailSubject = $this->mailHeaders['Subject'];
        }

        $this->finalized = false;

        return true;
    }

    /**
     * Adds an item to the body of the mail.
     *
     * @param mixed $item EaseObject or anything with the draw (); method
     *
     * @return mixed pointer to the inserted content
     */
    public function &addItem($item)
    {
        $added = $this->htmlBody->addItem($item);

        return $added;
    }

    /**
     * Gives you current Body.
     *
     * @return BodyTag
     */
    public function getContents()
    {
        return $this->htmlBody;
    }

    /**
     * Obtain item count.
     */
    public function getItemsCount(): int
    {
        return $this->htmlBody->getItemsCount();
    }

    /**
     * Is object empty ?
     */
    public function isEmpty(): bool
    {
        return $this->htmlBody->isEmpty();
    }

    /**
     * Empty container contents.
     */
    public function emptyContents(): void
    {
        $this->htmlBody->emptyContents();
    }

    /**
     * Attaches an attachment from a file to the mail.
     *
     * @param string $filename path / file name to attach
     * @param string $mimeType MIME attachment type
     *
     * @return bool file attachment successful
     */
    public function addFile(string $filename, string $mimeType = 'text/plain'): bool
    {
        if ($filename === '' || !file_exists($filename)) {
            return false;
        }

        $this->email->attachFromPath($filename, basename($filename), $mimeType ?: null);
        $this->attachments[] = $filename;
        $this->finalized = false;

        return true;
    }

    public function getCss(): void
    {
    }

    /**
     * Count current mail size.
     */
    public function getCurrentMailSize(): int
    {
        $this->finalize();
        $this->finalized = false;

        return \strlen($this->email->toString());
    }

    public function getSignature(): string
    {
        return '';
    }

    /**
     * Builds the body of the mail.
     */
    public function finalize(): void
    {
        $this->email->html($this->htmlDocument->getRendered());

        if (!empty($this->fromEmailAddress)) {
            $this->email->from(Address::create($this->fromEmailAddress));
        }

        if (!empty($this->emailAddress)) {
            foreach (explode(',', $this->emailAddress) as $address) {
                if (trim($address) !== '') {
                    $this->email->addTo(Address::create(trim($address)));
                }
            }
        }

        if (!empty($this->emailSubject)) {
            $this->email->subject($this->emailSubject);
        }

        if (isset($this->mailHeaders['Cc'])) {
            foreach (explode(',', $this->mailHeaders['Cc']) as $address) {
                if (trim($address) !== '') {
                    $this->email->addCc(Address::create(trim($address)));
                }
            }
        }

        if (isset($this->mailHeaders['Bcc'])) {
            foreach (explode(',', $this->mailHeaders['Bcc']) as $address) {
                if (trim($address) !== '') {
                    $this->email->addBcc(Address::create(trim($address)));
                }
            }
        }

        if (isset($this->mailHeaders['Reply-To'])) {
            $this->email->replyTo(Address::create($this->mailHeaders['Reply-To']));
        }

        $headers = $this->email->getHeaders();

        foreach ($this->mailHeaders as $headerName => $headerValue) {
            if (!\in_array(
                strtolower($headerName),
                ['to', 'from', 'subject', 'cc', 'bcc', 'reply-to', 'content-type', 'content-transfer-encoding', 'date'],
                true,
            )) {
                $headers->addTextHeader($headerName, $headerValue);
            }
        }

        $this->finalized = true;
    }

    /**
     * Do not draw mail included in page.
     */
    public function draw(): void
    {
        $this->drawStatus = true;
    }

    /**
     * Send mail.
     */
    public function send(): bool
    {
        if (!$this->finalized) {
            $this->finalize();
        }

        try {
            $this->mailer->send($this->email);
            $this->sendResult = true;
        } catch (\Exception $exc) {
            $this->sendResult = false;

            if ($this->notify === true) {
                $mailStripped = str_replace(['<', '>'], '', $this->emailAddress);
                $this->addStatusMessage(sprintf(
                    _('Message %s, for %s was not sent because of %s'),
                    $this->emailSubject,
                    $mailStripped,
                    $exc->getMessage(),
                ), 'warning');
            }
        }

        if ($this->sendResult === true && $this->notify === true) {
            $mailStripped = str_replace(['<', '>'], '', $this->emailAddress);
            $this->addStatusMessage(sprintf(_('Message %s was sent to %s'), $this->emailSubject, $mailStripped), 'success');
        }

        foreach ($this->attachments as $attachment) {
            if (file_exists($attachment)) {
                unlink($attachment);
            }
        }

        return $this->sendResult;
    }

    /**
     * Sets the user notification flag.
     *
     * @param bool $notify required notification status
     */
    public function setUserNotification($notify): void
    {
        $this->notify = (bool) $notify;
    }

    /**
     * Inserts another element after the existing one.
     *
     * @param mixed $pageItem value or EaseObject with draw () method
     *
     * @return mixed A link to the embedded object
     */
    public function &addNextTo($pageItem)
    {
        $itemPointer = null;
        $parent = $this->htmlBody->parentObject;

        if ($parent instanceof \Ease\Embedable) {
            $itemPointer = $parent->addItem($pageItem);
        }

        return $itemPointer;
    }
}
