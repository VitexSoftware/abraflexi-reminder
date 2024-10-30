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

use AbraFlexi\Adresar;

/**
 * Description of SmsToCustomer.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class SmsToAddress extends Sms
{
    /**
     * Send SMS to default Phone number.
     *
     * @param Adresar $address
     * @param string  $message
     */
    public function __construct($address, $message = '')
    {
        if (\Ease\Shared::cfg('MUTE') === 'true') {
            // Do not send message to Customers in MUTE mode
            $smsNo = \Ease\Shared::cfg('SMS_SENDER');
            $this->result = false;
        } else {
            $smsNo = $address->getAnyPhoneNumber();
        }

        parent::__construct($smsNo, $message);

        if (\Ease\Shared::cfg('MUTE') !== 'true') {
            if (empty($smsNo)) {
                $address->addStatusMessage(
                    $address->getRecordIdent().' '.$address->getApiURL().' '._('Address or primary contact without any phone number'),
                    'warning',
                );
            }
        }
    }
}
