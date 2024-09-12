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
 * Description of SmsBySshGnokii.
 *
 * @author vitex
 */
class SmsBySshGnokii extends SmsToAddress
{
    /**
     * Send SMS using remote Gnokii via sms.
     */
    public function sendMessage(): bool
    {
        if (\Ease\Shared::cfg('GNOKII_HOST')) {
            $command = '../bin/sshgnokiisms '.$this->getNumber().' "'.\Ease\Functions::rip($this->getMessage()).'" '.\Ease\Shared::cfg('GNOKII_HOST');
            $this->addStatusMessage('SMS '.$this->getNumber().': '.$command, 'debug');

            return !empty(system($command));
        }

        $this->addStatusMessage(_('Please set GNOKII_HOST in gnokii file'));

        return false;
    }
}
