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

/**
 * SMS sender class.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Sms extends \Ease\Sand
{
    use \Ease\RecordKey;

    /**
     * Message sent status.
     */
    public ?bool $result = null;
    protected string $number;
    protected string $message;

    /**
     * Send SMS Remind.
     */
    public function __construct(?string $number = null, ?string $message = null)
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
     * Current message text.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Current phone number.
     */
    public function getNumber(): string
    {
        return \Ease\Shared::cfg('DEBUG') ? \Ease\Shared::cfg('SMS_SENDER') : $this->number;
    }

    /**
     * Use Telephone number for SMS.
     *
     * @param string $number
     */
    public function setNumber($number): void
    {
        $number = str_replace([' ', '.', '+'], ['', '', ''], $number);
        $number = preg_replace('/(420|0420)/', '', $number);
        $this->setMyKey($number);
        $this->number = $number;
        $this->setObjectName($number.'@'.\get_class($this));
    }

    /**
     * Set SMS message text.
     *
     * @param string $message
     */
    public function setMessage($message): void
    {
        if (\strlen($message) > 130) {
            $this->addStatusMessage(sprintf(
                _('Message is %s chars long: %s'),
                \strlen($message),
                $message,
            ), 'warning');
        }

        $this->message = trim($message.' '.\Ease\Shared::cfg('SMS_SIGNATURE'));
    }

    /**
     * Unify Telephone number format.
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
            trim(str_replace(' ', '', urldecode($number))),
        );
    }

    /**
     * Send message now placeholder.
     *
     * @return bool message sent ?
     */
    public function sendMessage()
    {
        $this->addStatusMessage(_('No SMS sending method specified'), 'error');

        return false;
    }
}
