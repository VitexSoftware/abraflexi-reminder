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

namespace AbraFlexi\Reminder\Notifier;

use AbraFlexi\Reminder\SmsByGnokii;
use AbraFlexi\Reminder\SmsByHuaweiApi;
use AbraFlexi\Reminder\SmsBySshGnokii;
use AbraFlexi\Reminder\Upominac;
use AbraFlexi\Reminder\Upominka;
use Ease\Sand;

/**
 * AbraFlexi - Remind by SMS class.
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2018-2020 Spoje.Net, Vitex Software
 *
 * @no-named-arguments
 */
class BySms extends Sand
{
    public array $result = [];
    private string $message = '';

    /**
     * eMail notification.
     *
     * @param Upominac $reminder
     * @param int      $score    weeks of due
     * @param array    $debts    array of debts by current customer
     */
    public function __construct($reminder, $score, $debts)
    {
        $this->setObjectName();
        $result = false;

        if (\Ease\Shared::cfg('SMS_ENGINE', false)) {
            if ($reminder->customer->adresar->getAnyPhoneNumber()) {
                $this->compile($score, $reminder->customer, $debts);

                $message = $this->message;

                switch (\Ease\Shared::cfg('SMS_ENGINE')) {
                    case 'gnokii':
                        $smsEngine = new SmsByGnokii($reminder->customer->adresar, $message);

                        break;
                    case 'huaweiapi':
                        $smsEngine = new SmsByHuaweiApi($reminder->customer->adresar, $message);

                        break;
                    case 'sshgnokii':
                        $smsEngine = new SmsBySshGnokii($reminder->customer->adresar, $message);

                        break;

                    default:
                        $smsEngine = null;

                        break;
                }

                if (null !== $smsEngine) {
                    $result = $smsEngine->result;

                    //            file_put_contents('/var/tmp/upominka.txt',$message);
                    if (($score > 0) && ($score < 4) && $result) {
                        $this->setData(['id' => $reminder->customer->adresar->getRecordIdent(), 'stitky' => 'UPOMINKA'.$score], true);
                        $reminder->addStatusMessage(sprintf(_('Set Label %s '), 'UPOMINKA'.$score), $reminder->customer->adresar->sync() ? 'success' : 'error');
                    }
                }
            } else {
                $message = sprintf(_('Client %s without phone neumber %s !!!'), $reminder->customer->adresar->getDataValue('nazev'), $reminder->customer->adresar->getApiURL());
                $this->addStatusMessage($message, 'warning');
            }

            $this->result = ['sent' => $result, 'message' => $message];
        }
    }

    /**
     * Compile SMS reminder.
     *
     * @param int                        $score
     * @param \AbraFlexi\Bricks\Customer $customer
     * @param array                      $clientDebts
     *
     * @return string
     */
    public function compile($score, $customer, $clientDebts): bool
    {
        $result = true;
        $nazev = $customer->adresar->getDataValue('nazev');
        $upominka = new Upominka();

        switch ($score) {
            case 1:
                $upominka->loadTemplate('prvniUpominka');

                break;
            case 2:
                $upominka->loadTemplate('druhaUpominka');

                break;
            case 3:
                $upominka->loadTemplate('pokusOSmir');

                break;

            default:
                $upominka->loadTemplate('inventarizace');
        }

        $dnes = new \DateTime();
        $subject = $upominka->getDataValue('hlavicka').' ke dni '.$dnes->format('d.m.Y');
        $heading = $upominka->getDataValue('uvod').' '.$nazev."\n".$upominka->getDataValue('textNad')."\n".Upominac::formatTotals(Upominka::getSums($clientDebts));
        $this->message = $subject.':'.$heading;

        return $result;
    }
}
