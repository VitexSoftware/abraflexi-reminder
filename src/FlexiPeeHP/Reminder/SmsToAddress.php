<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace FlexiPeeHP\Reminder;

/**
 * Description of SmsToCustomer
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class SmsToAddress extends Sms
{

    /**
     * Send SMS to default Phone number
     * 
     * @param \FlexiPeeHP\Adresar $address
     * @param string              $message
     */
    public function __construct($address, $message = '')
    {
        if (defined('MUTE') && (constant('MUTE') == 'true')) {
            $smsNo = constant('SMS_SENDER');
        } else {
            $smsNo = $address->getAnyPhoneNumber();
        }
        parent::__construct($smsNo, $message);
        if (empty($smsNo)) {
            $address->addStatusMessage($address->getRecordIdent().' '.$address->getApiURL().' '._('Address or primary contact without any phone number'),
                'warning');
        }
    }
}
