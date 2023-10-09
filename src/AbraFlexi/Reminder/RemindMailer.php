<?php

namespace AbraFlexi\Reminder;

use Ease\Container;
use Ease\Functions;
use Ease\Html\BodyTag;
use Ease\Html\HtmlTag;
use Ease\Html\SimpleHeadTag;
use Ease\Html\TitleTag;
use Ease\HtmlMailer;
use Ease\Shared;

/**
 * AbraFlexi Reminder Mailer
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2023 Vitex Software
 */
class RemindMailer extends HtmlMailer
{
    /**
     * Send Remind by mail
     *
     * @param string $sendTo
     * @param string $subject
     */
    public function __construct($sendTo, $subject)
    {
        $this->fromEmailAddress = \Ease\Shared::cfg('REMIND_FROM');
        if (strtolower(\Ease\Shared::cfg('MUTE')) == 'true') {
            $sendTo = \Ease\Shared::cfg('EASE_EMAILTO');
        }
        parent::__construct($sendTo, $subject);
        if (\Ease\Shared::cfg('MAIL_CC')) {
            $this->setMailHeaders(['Cc' => \Ease\Shared::cfg('MAIL_CC')]);
        }
        $this->setObjectName();

        $this->htmlDocument = new HtmlTag(new SimpleHeadTag([
                    new TitleTag($this->emailSubject),
                    '<style>' . Upominka::$styles . '</style>']));
        $this->htmlBody = $this->htmlDocument->addItem(new BodyTag());
    }

    public function getCss()
    {
    }

    /**
     * Count current mail size
     *
     * @return int Size in bytes
     */
    public function getCurrentMailSize()
    {
        $this->finalize();
        $this->finalized = false;
        if (
            function_exists('mb_internal_encoding') &&
                (((int) ini_get('mbstring.func_overload')) & 2)
        ) {
            return mb_strlen($this->mailBody, '8bit');
        } else {
            return strlen($this->mailBody);
        }
    }

    public function getSignature()
    {
    }
}
