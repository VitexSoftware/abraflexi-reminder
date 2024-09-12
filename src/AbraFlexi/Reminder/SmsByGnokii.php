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
class SmsByGnokii extends SmsToAddress
{
    /**
     * Send SMS using remote Gnokii via sms.
     */
    public function sendMessage(): bool
    {
        $command = '../bin/gnokiisms '.$this->getNumber().' "'.\Ease\Functions::rip($this->getMessage()).'" ';
        $this->addStatusMessage('SMS '.$this->getNumber().': '.$command, 'debug');

        return !empty(system($command));
    }
}
