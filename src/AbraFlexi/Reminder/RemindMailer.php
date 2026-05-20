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

/**
 * AbraFlexi Reminder's Mailer.
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2025 Vitex Software
 *
 * @no-named-arguments
 */
class RemindMailer extends \Ease\HtmlMailer
{
    /**
     * List off attachments to clean.
     *
     * @var array<string>
     */
    public array $attachments = [];

    /**
     * Send Remind by mail.
     */
    public function __construct(string $sendTo = '', string $subject = '')
    {
        if (strtolower(\Ease\Shared::cfg('MUTE', 'false')) === 'true') {
            $sendTo = \Ease\Shared::cfg('EASE_EMAILTO', get_current_user().'@'.gethostname());
        }

        parent::__construct($sendTo, $subject, '', ['From' => \Ease\Shared::cfg('REMIND_FROM')]);

        if (\Ease\Shared::cfg('MAIL_CC')) {
            $this->setMailHeaders(['Cc' => \Ease\Shared::cfg('MAIL_CC')]);
        }

        $this->setObjectName();

        $this->htmlDocument = new HtmlTag(new SimpleHeadTag([
            new TitleTag($this->emailSubject),
            '<style>'.Upominka::$styles.'</style>',
        ]));
        $this->htmlBody = $this->htmlDocument->addItem(new BodyTag());
    }

    /**
     * {@inheritDoc}
     */
    public function addFile(string $filename, string $mimeType = 'text/plain'): bool
    {
        $added = parent::addFile($filename, $mimeType);

        if ($added) {
            $this->attachments[] = $filename;
        }

        return $added;
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

        return \strlen($this->mailBody);
    }

    public function getSignature(): string
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function send(): bool
    {
        try {
            $result = parent::send();
        } catch (\Exception $exc) {
            $result = false;
        }

        foreach ($this->attachments as $attachment) {
            if (file_exists($attachment)) {
                unlink($attachment);
            }
        }

        return $result;
    }
}
