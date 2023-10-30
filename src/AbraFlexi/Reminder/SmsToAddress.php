<?php

/**
 * AbraFlexi Reminder obtauin SMS number for AbraFlexi Address
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2021 Vitex Software
 */

namespace AbraFlexi\Reminder;

use Ease\Functions;
use AbraFlexi\Adresar;

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
     * @param Adresar $address
     * @param string  $message
     */
    public function __construct($address, $message = '')
    {
        if (\Ease\Shared::cfg('MUTE') == 'true') {
            // Do not send message to Customers in MUTE mode
            $smsNo = \Ease\Shared::cfg('SMS_SENDER');
        } else {
            $smsNo = $address->getAnyPhoneNumber();
        }
        parent::__construct($smsNo, $message);
        if (empty($smsNo)) {
            $address->addStatusMessage(
                $address->getRecordIdent() . ' ' . $address->getApiURL() . ' ' . _('Address or primary contact without any phone number'),
                'warning'
            );
        }
    }
}
