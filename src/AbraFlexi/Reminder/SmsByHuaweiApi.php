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
 * Description of SmsByGnokii.
 *
 * @author vitex
 */
class SmsByHuaweiApi extends SmsToAddress
{
    /**
     * Send SMS using remote Gnokii via sms.
     *
     * @return bool message sending result
     */
    public function sendMessage()
    {
        $router = new \HSPDev\HuaweiApi\Router();
        $router->setAddress(\Ease\Shared::cfg('MODEM_IP', '192.168.8.1'));

        try {
            $router->login('admin', \Ease\Shared::cfg('MODEM_PASSWORD'));
            $status = $router->sendSms($this->getNumber(), $this->getMessage());
        } catch (\Exception $exc) {
            $this->addStatusMessage($this->getMessage(), 'error');
            $this->addStatusMessage($exc->getMessage(), 'debug');
            $status = false;
        }

        $this->addStatusMessage('SMS '.$this->getNumber().': '.$this->getMessage(), $status ? 'success' : 'error');

        return $status;
    }
}
