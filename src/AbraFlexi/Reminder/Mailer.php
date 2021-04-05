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
 * @copyright  (G) 2017-2020 Vitex Software
 */
class Mailer extends HtmlMailer {

    /**
     * Send Remind by mail
     * 
     * @param string $subject
     * @param Container   $moduleDir
     */
    public function __construct($sendTo, $subject) {
        $shared = Shared::instanced();
        $this->fromEmailAddress = Functions::cfg('REMIND_FROM');

        if (Functions::cfg('MUTE') === true) {
            $sendTo = Functions::cfg('EASE_MAILTO');
        }
        parent::__construct($sendTo, $subject);

        if (Functions::cfg('MAIL_CC')) {
            $this->setMailHeaders(['Cc' => Functions::cfg('MAIL_CC')]);
        }


        $this->htmlDocument = new HtmlTag(new SimpleHeadTag([
                    new TitleTag($this->emailSubject),
                    '<style>' . Upominka::$styles . '</style>']));
        $this->htmlBody = $this->htmlDocument->addItem(new BodyTag());
    }

    /**
     * Přidá položku do těla mailu.
     *
     * @param mixed $item EaseObjekt nebo cokoliv s metodou draw();
     *
     * @return Ease\pointer|null ukazatel na vložený obsah
     */
    public function &addItem($item, $pageItemName = null) {
        $mailBody = '';
        if (is_object($item)) {
            if (is_object($this->htmlDocument)) {
                if (is_null($this->htmlBody)) {
                    $this->htmlBody = new BodyTag();
                }
                $mailBody = $this->htmlBody->addItem($item, $pageItemName);
            } else {

                $mailBody = $this->htmlDocument;
            }
        } else {
            $this->textBody .= is_array($item) ? implode("\n", $item) : $item;
            $this->mimer->setTXTBody($this->textBody);
        }

        return $mailBody;
    }

    public function getCss() {
        
    }

    /**
     * Count current mail size
     *
     * @return int Size in bytes
     */
    public function getCurrentMailSize() {
        $this->finalize();
        $this->finalized = false;
        if (function_exists('mb_internal_encoding') &&
                (((int) ini_get('mbstring.func_overload')) & 2)) {
            return mb_strlen($this->mailBody, '8bit');
        } else {
            return strlen($this->mailBody);
        }
    }

}
