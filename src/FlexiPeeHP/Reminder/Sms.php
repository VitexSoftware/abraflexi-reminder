<?php

/**
 * FlexiBee Reminder SMS
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2020 Vitex Software
 */

namespace FlexiPeeHP\Reminder;

/**
 * SMS sender class
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Sms extends \Ease\Sand {

    use \Ease\RecordKey;

    /**
     * 
     * @var long 
     */
    protected $number;

    /**
     *
     * @var string 
     */
    protected $message;

    /**
     * Send SMS Remind
     * 
     * @param long $number
     * @param string $message
     */
    public function __construct($number = null, $message = null) {
        if (!empty($number)) {
            $this->setNumber($number);
        }
        if (!empty($message)) {
            $this->setMessage($message);
        }
        if (!empty($this->message) && !empty($this->number)) {
            $this->sendMessage();
        }
    }

    /**
     * Current message text
     * 
     * @return string
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * Current phone number
     * 
     * @return long
     */
    public function getNumber() {
        return $this->number;
    }

    /**
     * 
     * @param string $number
     */
    public function setNumber($number) {
        $number = str_replace([' ', '.', '+'], ['', '', ''], $number);
        $number = preg_replace('/(420|0420)/', '', $number);
        $this->setMyKey($number);
        $this->number = $number;
    }

    /**
     * 
     * @param string $message
     */
    public function setMessage($message) {
        if (strlen($message) > 130) {
            $this->addStatusMessage(sprintf(_('Message is %s chars long: %s'),
                            strlen($message), $message), 'warning');
        }
        $this->message = $message;
    }

    public static function unifyTelNo($number) {
        return preg_replace('/^(%2b420|420)/', '',
                trim(str_replace(' ', '', urldecode($number))));
        ;
    }

    /**
     * 
     */
    public function sendMessage() {
        $this->addStatusMessage(_('No SMS sending method specified'), 'error');
    }

}
