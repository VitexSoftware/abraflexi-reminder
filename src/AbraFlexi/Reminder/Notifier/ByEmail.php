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

use AbraFlexi\Bricks\Customer;
use AbraFlexi\FakturaVydana;
use AbraFlexi\Formats;
use AbraFlexi\Functions;
use AbraFlexi\Reminder\RemindMailer;
use AbraFlexi\Reminder\Upominac;
use AbraFlexi\Reminder\Upominka;
use AbraFlexi\RO;
use AbraFlexi\ui\CompanyLogo;
use Ease\Html\DivTag;
use Ease\Html\HrTag;
use Ease\Html\PTag;
use Ease\Html\TableTag;
use Ease\Html\TdTag;
use Ease\Html\TrTag;
use Ease\Sand;

/**
 * AbraFlexi - Remind by eMail class.
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2018-2025 Spoje.Net
 *
 * @no-named-arguments
 */
class ByEmail extends Sand implements \AbraFlexi\Reminder\notifier
{
    public array $result = [];
    public RemindMailer $mailer;
    public FakturaVydana $invoicer;
    private \AbraFlexi\Adresar $firmer;

    /**
     * eMail notification.
     */
    public function __construct(\AbraFlexi\Reminder\Upominac $reminder, int $score, array $debts)
    {
        $result = false;
        $this->setObjectName();

        if ($this->compile($score, $reminder->customer, $debts)) {
            $result = $this->send();

            if ($score && $result) {
                $reminder->customer->adresar->setData([
                    'id' => $reminder->customer->adresar->getRecordIdent(),
                    'stitky' => 'UPOMINKA'.$score,
                ], true);
                $labelUpdated = $reminder->customer->adresar->sync();

                if ($labelUpdated) {
                    $message = sprintf(_('Set Label %s '), 'UPOMINKA'.$score);
                } else {
                    $message = sprintf(_('Set Label %s failed'), 'UPOMINKA'.$score);
                }

                $reminder->addStatusMessage($message, $labelUpdated ? 'success' : 'error');
            } else {
                $message = _('Sent');
            }
        } else {
            $this->addStatusMessage(_('Remind was not sent'), 'warning');
            $message = _('Remind was not sent');
            $result = false;
        }

        $this->result = ['sent' => $result, 'message' => $message];
    }

    /**
     * Compile Reminder message with its contents.
     *
     * @param int      $score    Weeks after due date
     * @param Customer $customer
     */
    public function compile(int $score, $customer, array $clientDebts): bool
    {
        $result = false;
        $email = $customer->adresar->getNotificationEmailAddress();
        $nazev = $customer->adresar->getDataValue('nazev');
        $this->invoicer = new FakturaVydana();

        $this->firmer = &$customer->adresar;

        if ($email) {
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
                    $lastInvDays = Upominac::getDaysToLastInventarization($clientDebts);

                    if ($lastInvDays < 14) {
                        $this->addStatusMessage(
                            sprintf(
                                _('Last  remind / inventarization for %s send before %d days; skipping'),
                                Functions::uncode((string) $customer),
                                $lastInvDays,
                            ),
                            'debug',
                        );

                        return false;
                    }

                    $upominka->loadTemplate('inventarizace');
            }

            $invoices = [];

            $to = $email;

            $dnes = new \DateTime();
            $subject = $upominka->getDataValue('hlavicka').' ke dni '.$dnes->format('d.m.Y');

            $this->mailer = new RemindMailer($to, $subject);

            $heading = new DivTag($upominka->getDataValue('uvod').' '.$nazev);

            if (strtolower(\Ease\Shared::cfg('ADD_LOGO', '')) === 'true') {
                $headingTableRow = new TrTag();
                $headingTableRow->addItem(new TdTag($heading));
                $logo = new CompanyLogo([
                    'align' => 'right',
                    'id' => 'companylogo',
                    'height' => '50',
                    'title' => _('Company logo'),
                ]);
                $headingTableRow->addItem(
                    new TdTag(
                        $logo,
                        ['width' => '200px'],
                    ),
                );
                $headingTable = new TableTag(
                    $headingTableRow,
                    ['width' => '100%'],
                );
                $this->mailer->addItem($headingTable);
            } else {
                $this->mailer->addItem($heading);
            }

            $this->mailer->addItem(new PTag());
            $this->mailer->addItem(new DivTag(nl2br($upominka->getDataValue('textNad'))));
            $debtsTable = new TableTag(
                null,
                ['class' => 'greyGridTable'],
            );
            $debtsTable->addRowHeaderColumns([
                _('Code'),
                _('var. sym.'),
                _('Amount'),
                _('Currency'),
                _('Due Date'),
                _('overdue days'),
            ]);

            foreach ($clientDebts as $debt) {
                $currency = Functions::uncode((string) $debt['mena']);

                if ($currency === 'CZK') {
                    $amount = $debt['zbyvaUhradit'];
                } else {
                    $amount = $debt['zbyvaUhraditMen'];
                }

                $debtsTable->addRowColumns([
                    $debt['kod'],
                    $debt['varSym'],
                    Upominka::formatCurrency($amount),
                    $currency,
                    $debt['datSplat']->format('d.m.Y'),
                    FakturaVydana::overdueDays($debt['datSplat']),
                ]);
            }

            $debtsTable->addRowFooterColumns(['', _('Total'), Upominac::formatTotals(Upominka::getSums($clientDebts))]);
            $this->mailer->addItem(new PTag('<br clear="all"/>'));
            $this->mailer->addItem($debtsTable);
            $this->mailer->addItem(new PTag('<br clear="all"/>'));
            $this->mailer->addItem(new DivTag(nl2br($upominka->getDataValue('textPod'))));
            $this->mailer->addItem(new PTag('<br clear="all"/>'));
            $this->mailer->addItem(new HrTag());
            $this->mailer->addItem(new DivTag(nl2br($upominka->getDataValue('zapati'))));

            if (\Ease\Shared::cfg('QR_PAYMENTS')) {
                $this->mailer->addItem(Upominka::qrPayments($clientDebts));
            }

            $this->addAttachments($clientDebts);
            $this->mailer->addItem(new \Ease\Html\PTag(new \Ease\Html\SmallTag(new \Ease\Html\ATag('https://github.com/VitexSoftware/abraflexi-reminder', \Ease\Shared::appName()).' v'.\Ease\Shared::appVersion())));
            $result = true;
        } else {
            $this->addStatusMessage(sprintf(_('Client %s without email %s !!!'), $nazev, $this->firmer->getApiURL()), 'error');
        }

        return $result;
    }

    /**
     * Attach PDF and ISDOC invoices.
     *
     * @param array $clientDebts
     */
    public function addAttachments($clientDebts): void
    {
        foreach ($clientDebts as $debtCode => $debt) {
            if (\Ease\Shared::cfg('MAX_MAIL_SIZE') && ($this->mailer->getCurrentMailSize() > \Ease\Shared::cfg('MAX_MAIL_SIZE', 30000000))) {
                $this->mailer->addStatusMessage(sprintf(_('Not enough space in this mail for attaching %s '), $debtCode), 'warning');

                continue;
            }

            if (\array_key_exists('evidence', $debt)) {
                $this->invoicer->setEvidence($debt['evidence']);
            }

            $this->invoicer->setMyKey(RO::code($debt['kod']));
            $this->mailer->addFile(
                $this->invoicer->downloadInFormat('pdf', '/tmp/'),
                Formats::$formats['PDF']['content-type'],
            );
            $this->mailer->addFile(
                $this->invoicer->downloadInFormat('isdocx', '/tmp/'),
                Formats::$formats['ISDOCx']['content-type'],
            );
        }
    }

    /**
     * Send Email Remind.
     *
     * @return bool
     */
    public function send()
    {
        return $this->mailer->send();
    }
}
