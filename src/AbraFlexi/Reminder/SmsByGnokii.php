<?php

/**
 * AbraFlexi Reminder local SMS sender
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2021 Vitex Software
 */

namespace AbraFlexi\Reminder;

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
        $command = '../bin/gnokiisms ' . $this->getNumber() . ' "' . \Ease\Functions::rip($this->getMessage()) . '" ';
        $this->addStatusMessage('SMS ' . $this->getNumber() . ': ' . $command, 'debug');
        return system($command);
    }

}
