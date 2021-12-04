<?php

use AbraFlexi\Reminder\SmsToAddress;

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
class SmsByHuaweiApi extends SmsToAddress {

    /**
     * Send SMS using remote Gnokii via sms
     *
     * @return string Last row of command result stdout
     */
    public function sendMessage() {

        $router = new \HSPDev\HuaweiApi\Router();


        $router->setAddress(\Ease\Functions::cfg('MODEM_IP') ? \Ease\Functions::cfg('MODEM_IP') : '192.168.8.1');

        $router->login('admin', \Ease\Functions::cfg('MODEM_PASSWORD'));

        $status = $router->sendSms($this->getNumber(),$this->getMessage());
        
        $this->addStatusMessage('SMS ' . $this->getNumber() . ': ' . $this->getMessage() , $status ? 'success' : 'error');
        return $status;
    }

}
