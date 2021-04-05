<?php

use AbraFlexi\Reminder\SmsToAddress;
/**
 * AbraFlexi Reminder remote SMS sender
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2020 Vitex Software
 */


namespace AbraFlexi\Reminder;

/**
 * Description of SmsBySshGnokii
 *
 * @author vitex
 */
class SmsBySshGnokii extends SmsToAddress
{

    /**
     * Send SMS using remote Gnokii via sms
     *
     * @return string Last row of command result stdout
     */
    public function sendMessage()
    {
        if(defined('GNOKII_HOST')){
        $command = '../bin/sshgnokiisms '.$this->getNumber().' "'.\Ease\Functions::rip($this->getMessage()).'" '.constant('GNOKII_HOST');
        $this->addStatusMessage('SMS '.$this->getNumber().': '.$command, 'debug');
        return system($command);
        } else {
            $this->addStatusMessage(_('Please set GNOKII_HOST in gnoki file'));
        }
    }
}
