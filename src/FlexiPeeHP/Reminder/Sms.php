<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace FlexiPeeHP\Reminder;

/**
 * SMS sender class
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Sms extends \Ease\Sand
{
    /**
     * 
     * @var long 
     */
    private $number;

    /**
     *
     * @var string 
     */
    private $message;

    /**
     * 
     * @param long $number
     * @param string $message
     */
    public function __construct($number = null, $message = null)
    {
        parent::__construct();
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

    public function getMessage()
    {
        return $this->message;
    }

    public function getNumber()
    {
        return $this->number;
    }

    /**
     * 
     * @param string $number
     */
    public function setNumber($number)
    {
        $number       = str_replace([' ', '.', '+'], ['', '', ''], $number);
        $number       = preg_replace('/(420|0420)/', '', $number);
        $this->number = $number;
    }

    /**
     * 
     * @param type $message
     */
    public function setMessage($message)
    {
        if (strlen($message) > 130) {
            $this->addStatusMessage(sprintf(_('Message %s chars long: %s'),
                    strlen($message), $message), 'warning');
        }
        $this->message = $message;
    }

    public static function unifyTelNo($number)
    {
        return preg_replace('/^(%2b420|420)/', '',
            trim(str_replace(' ', '', urldecode($number))));
        ;
    }

    /**
     * 
     */
    public function sendMessage()
    {
        $this->addStatusMessage(_('No SMS sending method specified'), 'error');
    }
}
