<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace FlexiPeeHP\Reminder;

/**
 * Description of SmsByGnokii
 *
 * @author vitex
 */
class SmsByGnokii extends SmsToAddress {
    /**
     * Send SMS using remote Gnokii via sms
     *
     * @return string Last row of command result stdout
     */
    public function sendMessage() {
        $command = '../bin/gnokiisms '.$this->getNumber().' "'.self::rip($this->getMessage()).'" ';
        $this->addStatusMessage('SMS '.$this->getNumber().': '.$command, 'debug');
        return system($command);
    }
}
