<?php

namespace FlexiPeeHP\Reminder;

/**
 * FlexiBee Reminder Mailer
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2020 Vitex Software
 */
class Mailer extends \Ease\HtmlMailer
{

    /**
     * Send Remind by mail
     * 
     * @param string $subject
     * @param \Ease\Container   $moduleDir
     */
    public function __construct($sendTo, $subject)
    {
        $shared                 = \Ease\Shared::instanced();
        $this->fromEmailAddress = $shared->getConfigValue('REMIND_FROM');

        if (defined('MUTE') && (constant('MUTE') == 'true')) {
            $sendTo = constant('EASE_MAILTO');
        }
        parent::__construct($sendTo, $subject);

        if (defined('MAIL_CC')) {
            $this->mailHeaders['Cc'] = constant('MAIL_CC');
        }
        
        $this->htmlDocument = new \Ease\Html\HtmlTag(new \Ease\Html\SimpleHeadTag([
            new \Ease\Html\TitleTag($this->emailSubject),
            '<style>'.Upominka::$styles.'</style>']));
        $this->htmlBody     = $this->htmlDocument->addItem(new \Ease\Html\BodyTag());
    }

    /**
     * Přidá položku do těla mailu.
     *
     * @param mixed $item EaseObjekt nebo cokoliv s metodou draw();
     *
     * @return Ease\pointer|null ukazatel na vložený obsah
     */
    public function &addItem($item, $pageItemName = null)
    {
        $mailBody = '';
        if (is_object($item)) {
            if (is_object($this->htmlDocument)) {
                if (is_null($this->htmlBody)) {
                    $this->htmlBody = new \Ease\Html\BodyTag();
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
        if (function_exists('mb_internal_encoding') &&
            (((int) ini_get('mbstring.func_overload')) & 2)) {
            return mb_strlen($this->mailBody, '8bit');
        } else {
            return strlen($this->mailBody);
        }
    }
}
