<?php

/**
 * AbraFlexi Reminder SMS
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2023 Vitex Software
 */

namespace AbraFlexi\Reminder;

/**
 * SMS sender class
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Sms extends \Ease\Sand
{
    use \Ease\RecordKey;

    /**
     *
     * @var string
     */
    protected $number;

    /**
     *
     * @var string
     */
    protected $message;

    /**
     * Message sent status
     * @var boolean|null
     */
    public $result = null;

    /**
     * Send SMS Remind
     *
     * @param integer $number
     * @param string $message
     */
    public function __construct($number = null, $message = null)
    {
        if (empty($number)) {
            $this->setObjectName();
        } else {
            $this->setNumber($number);
        }
        if (!empty($message)) {
            $this->setMessage($message);
        }
        if (!empty($this->message) && !empty($this->number)) {
            $this->result = $this->sendMessage();
        }
    }

    /**
     * Current message text
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Current phone number
     *
     * @return string
     */
    public function getNumber()
    {
        return \Ease\Shared::cfg('DEBUG') ? \Ease\Shared::cfg('SMS_SENDER') : $this->number;
    }

    /**
     * Use Telephone number for SMS
     *
     * @param string $number
     */
    public function setNumber($number)
    {
        $number = str_replace([' ', '.', '+'], ['', '', ''], $number);
        $number = preg_replace('/(420|0420)/', '', $number);
        $this->setMyKey($number);
        $this->number = $number;
        $this->setObjectName($number . '@' . get_class($this));
    }

    /**
     * Set SMS message text
     *
     * @param string $message
     */
    public function setMessage($message)
    {
        if (strlen($message) > 130) {
            $this->addStatusMessage(sprintf(
                _('Message is %s chars long: %s'),
                strlen($message),
                $message
            ), 'warning');
        }
        $this->message = trim($message . ' ' . \Ease\Shared::cfg('SMS_SIGNATURE'));
    }

    /**
     * Unify Telephone number format
     *
     * @param string $number
     *
     * @return string
     */
    public static function unifyTelNo($number)
    {
        return preg_replace(
            '/^(%2b420|420)/',
            '',
            trim(str_replace(' ', '', urldecode($number)))
        );
    }

    /**
     * Send message now placeholder
     *
     * @return boolean message sent ?
     */
    public function sendMessage()
    {
        $this->addStatusMessage(_('No SMS sending method specified'), 'error');
        return false;
    }
}
