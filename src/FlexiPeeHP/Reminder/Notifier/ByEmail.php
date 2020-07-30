<?php

namespace FlexiPeeHP\Reminder\Notifier;

use DateTime;
use Ease\Functions;
use Ease\Html\DivTag;
use Ease\Html\HrTag;
use Ease\Html\PTag;
use Ease\Html\TableTag;
use Ease\Html\TdTag;
use Ease\Html\TrTag;
use Ease\Mailer as Mailer2;
use Ease\Sand;
use FlexiPeeHP\Bricks\Customer;
use FlexiPeeHP\FakturaVydana;
use FlexiPeeHP\FlexiBeeRO;
use FlexiPeeHP\Formats;
use FlexiPeeHP\Reminder\Mailer;
use FlexiPeeHP\Reminder\Upominac;
use FlexiPeeHP\Reminder\Upominka;
use FlexiPeeHP\ui\CompanyLogo;
use function __;


/**
 * FlexiPeeHP - Remind by eMail class 
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2018-2020 Spoje.Net
 */
class ByEmail extends Sand {

    /**
     *
     * @var boolean status 
     */
    public $result = null;

    /**
     *
     * @var Mailer2 
     */
    public $mailer = null;

    /**
     *
     * @var FakturaVydana
     */
    public $invoicer;

    /**
     * eMail notification
     * 
     * @param Upominac $reminder
     * @param int      $score     weeks of due
     * @param array    $debts     array of debts by current customer
     */
    public function __construct($reminder, $score, $debts) {
        $result = false;
        if ($this->compile($score, $reminder->customer, $debts)) {
            $result = $this->send();
//            file_put_contents('/var/tmp/upominka.html',$this->mailer->htmlDocument);
            if ($score && $result) {
                $reminder->customer->adresar->setData(['id' => $reminder->customer->adresar->getRecordID(),
                    'stitky' => 'UPOMINKA' . $score], true);
                $reminder->addStatusMessage(sprintf(_('Set Label %s '),
                                'UPOMINKA' . $score),
                        $reminder->customer->adresar->sync() ? 'success' : 'error' );
            }
        } else {
            $this->addStatusMessage(_('Remind was not sent'), 'warning');
        }
        $this->result = $result;
    }

    /**
     * Compile Reminder message with its contents
     *
     * @param int                         $score        Weeks after due date
     * @param Customer $customer
     * @param array                       $clientDebts
     * 
     * @return boolean
     */
    public function compile($score, $customer, $clientDebts) {
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
                default :
                    $lastInvDays = Upominac::getDaysToLastInventarization($clientDebts);
                    if ($lastInvDays < 14) {
                        $this->addStatusMessage(sprintf(_('Last  remind / inventarization for %s send before %d days; skipping'),
                                        FlexiBeeRO::uncode($customer), $lastInvDays),
                                'debug');
                        return false;
                    }

                    $upominka->loadTemplate('inventarizace');
            }


            $invoices = [];

            $to = $email;

            $dnes = new DateTime();
            $subject = $upominka->getDataValue('hlavicka') . ' ke dni ' . $dnes->format('d.m.Y');

            $this->mailer = new Mailer($to, $subject);

            $heading = new DivTag($upominka->getDataValue('uvod') . ' ' . $nazev);
            if (Functions::cfg('ADD_LOGO')) {
                $headingTableRow = new TrTag();
                $headingTableRow->addItem(new TdTag($heading));
                $logo = new CompanyLogo(['align' => 'right',
                    'id' => 'companylogo',
                    'height' => '50', 'title' => _('Company logo')]);
                $headingTableRow->addItem(new TdTag($logo,
                                ['width' => '200px']));
                $headingTable = new TableTag($headingTableRow,
                        ['width' => '100%']);
                $this->mailer->addItem($headingTable);
            } else {
                $this->mailer->addItem($heading);
            }

            $this->mailer->addItem(new PTag());
            $this->mailer->addItem(new DivTag(nl2br($upominka->getDataValue('textNad'))));
            $debtsTable = new TableTag(null,
                    ['class' => 'greyGridTable']);
            $debtsTable->addRowHeaderColumns([_('Code'), _('var. sym.'), _('Amount'),
                _('Currency'), _('Due Date'), _('overdue days')]);

            foreach ($clientDebts as $debt) {
                $currency = FlexiBeeRO::uncode($debt['mena']);
                if ($currency == 'CZK') {
                    $amount = $debt['zbyvaUhradit'];
                } else {
                    $amount = $debt['zbyvaUhraditMen'];
                }
                $debtsTable->addRowColumns([
                    $debt['kod'],
                    $debt['varSym'],
                    Upominka::formatCurrency($amount),
                    $currency,
                    FlexiBeeRO::flexiDateToDateTime($debt['datSplat'])->format('d.m.Y'),
                    FakturaVydana::overdueDays($debt['datSplat'])
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

            if (Functions::cfg('QR_PAYMENTS')) {
                $this->mailer->addItem(Upominka::qrPayments($clientDebts));
            }
            $this->addAttachments($clientDebts);
            $result = true;
        } else {
            $this->addStatusMessage(sprintf(_('Client %s without email %s !!!'),
                            $nazev, $this->firmer->getApiURL()), 'error');
        }
        return $result;
    }

    /**
     * Attach PDF and ISDOC invoices
     * 
     * @param array $clientDebts
     */
    public function addAttachments($clientDebts) {
        foreach ($clientDebts as $debtCode => $debt) {
            if (Functions::cfg('MAX_MAIL_SIZE') && ($this->mailer->getCurrentMailSize() > Functions::cfg('MAX_MAIL_SIZE'))) {
                $this->mailer->addItem(new DivTag(sprintf(_('Not enough space in this mail for attaching %s '),
                                        $debtCode)));
                continue;
            }
            if (array_key_exists('evidence', $debt)) {
                $this->invoicer->setEvidence($debt['evidence']);
            }
            $this->invoicer->setMyKey($debt['id']);
            $this->mailer->addFile($this->invoicer->downloadInFormat('pdf',
                            '/tmp/'),
                    Formats::$formats['PDF']['content-type']);
            $this->mailer->addFile($this->invoicer->downloadInFormat('isdocx',
                            '/tmp/'),
                    Formats::$formats['ISDOCx']['content-type']);
        }
    }

    /**
     * Send Email Remind
     *
     * @return boolean
     */
    public function send() {
        return $this->mailer->send();
    }

}
