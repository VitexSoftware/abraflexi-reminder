<?php

/**
 * FlexiPeeHP - Remind by eMail class 
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2018 Spoje.Net
 */
class ByEmail extends \Ease\Sand
{
    /**
     *
     * @var boolean status 
     */
    public $result = null;

    /**
     *
     * @var \Ease\Mailer 
     */
    public $mailer = null;

    /**
     *
     * @var \FlexiPeeHP\FakturaVydana
     */
    public $invoicer;

    /**
     * eMail notification
     * 
     * @param FlexiPeeHP\Reminder\Upominac $reminder
     * @param int                          $score     weeks of due
     * @param array                        $debts     array of debts by current customer
     */
    public function __construct($reminder, $score, $debts)
    {
        $result = false;
        if ($this->compile($score, $reminder->customer, $debts)) {
            $result = $this->send();
//            file_put_contents('/var/tmp/upominka.html',$this->mailer->htmlDocument);
            if ($score && $result) {
                $reminder->customer->adresar->setData(['id' => $reminder->customer->adresar->getRecordID(),
                    'stitky' => 'UPOMINKA'.$score], true);
                $reminder->addStatusMessage(sprintf(_('Set Label %s '),
                        'UPOMINKA'.$score),
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
     * @param \FlexiPeeHP\Bricks\Customer $customer
     * @param array                       $clientDebts
     * 
     * @return boolean
     */
    public function compile($score, $customer, $clientDebts)
    {
        $result       = false;
        $email        = $customer->adresar->getNotificationEmailAddress();
        $nazev          = $customer->adresar->getDataValue('nazev');
        $this->invoicer = new \FlexiPeeHP\FakturaVydana();

        $this->firmer = &$customer->adresar;
        if ($email) {

            $upominka = new \FlexiPeeHP\Reminder\Upominka();
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
                    $upominka->loadTemplate('inventarizace');
            }


            $invoices = [];

            $to = $email;

            $dnes    = new \DateTime();
            $subject = $upominka->getDataValue('hlavicka').' ke dni '.$dnes->format('d.m.Y');


            if (defined('MUTE') && constant('MUTE')) {
                $to = constant('EASE_MAILTO');
            }

            $this->mailer = new \FlexiPeeHP\Reminder\Mailer($to, $subject);

            $heading = new \Ease\Html\DivTag($upominka->getDataValue('uvod').' '.$nazev);
            if (defined('ADD_LOGO') && constant('ADD_LOGO')) {
                $headingTableRow = new \Ease\Html\TrTag();
                $headingTableRow->addItem(new \Ease\Html\TdTag($heading));
                $logo            = new \FlexiPeeHP\ui\CompanyLogo(['align' => 'right',
                    'id' => 'companylogo',
                    'height' => '50', 'title' => _('Company logo')]);
                $headingTableRow->addItem(new \Ease\Html\TdTag($logo,
                        ['width' => '200px']));
                $headingTable    = new \Ease\Html\TableTag($headingTableRow,
                    ['width' => '100%']);
                $this->mailer->addItem($headingTable);
            } else {
                $this->mailer->addItem($heading);
            }

            $this->mailer->addItem(new \Ease\Html\PTag());
            $this->mailer->addItem(new \Ease\Html\DivTag(nl2br($upominka->getDataValue('textNad'))));
            $debtsTable = new \Ease\Html\TableTag(null,
                ['class' => 'greyGridTable']);
            $debtsTable->addRowHeaderColumns([_('Code'), _('var. sym.'), _('Amount'),
                _('Currency'), _('Due Date'), _('overdue days')]);

            foreach ($clientDebts as $debt) {
                $currency = \FlexiPeeHP\FlexiBeeRO::uncode($debt['mena']);
                if ($currency == 'CZK') {
                    $amount = $debt['zbyvaUhradit'];
                } else {
                    $amount = $debt['zbyvaUhraditMen'];
                }
                $debtsTable->addRowColumns([
                    $debt['kod'],
                    $debt['varSym'],
                    \FlexiPeeHP\Reminder\Upominka::formatCurrency($amount),
                    $currency,
                    \FlexiPeeHP\FlexiBeeRO::flexiDateToDateTime($debt['datSplat'])->format('d.m.Y'),
                    \FlexiPeeHP\FakturaVydana::overdueDays($debt['datSplat'])
                ]);
            }

            $debtsTable->addRowFooterColumns(['', _('Total'), \FlexiPeeHP\Reminder\Upominac::formatTotals(\FlexiPeeHP\Reminder\Upominka::getSums($clientDebts))]);
            $this->mailer->addItem(new \Ease\Html\PTag('<br clear="all"/>'));
            $this->mailer->addItem($debtsTable);
            $this->mailer->addItem(new \Ease\Html\PTag('<br clear="all"/>'));
            $this->mailer->addItem(new \Ease\Html\DivTag(nl2br($upominka->getDataValue('textPod'))));
            $this->mailer->addItem(new \Ease\Html\PTag('<br clear="all"/>'));
            $this->mailer->addItem(new \Ease\Html\HrTag());
            $this->mailer->addItem(new \Ease\Html\DivTag(nl2br($upominka->getDataValue('zapati'))));

            if (defined('QR_PAYMENTS') && constant('QR_PAYMENTS')) {
                $this->mailer->addItem(FlexiPeeHP\Reminder\Upominka::qrPayments($clientDebts));
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
    public function addAttachments($clientDebts)
    {
        foreach ($clientDebts as $debtCode => $debt) {
            if (defined('MAX_MAIL_SIZE') && ($this->mailer->getCurrentMailSize()
                > constant('MAX_MAIL_SIZE'))) {
                $this->mailer->addItem(new \Ease\Html\DivTag(sprintf(_('Not enough space in this mail for attaching %s '),
                            $debtCode)));
                continue;
            }
            if (array_key_exists('evidence', $debt)) {
                $this->invoicer->setEvidence($debt['evidence']);
            }
            $this->invoicer->setMyKey($debt['id']);
            $this->mailer->addFile($this->invoicer->downloadInFormat('pdf',
                    '/tmp/'),
                \FlexiPeeHP\Formats::$formats['PDF']['content-type']);
            $this->mailer->addFile($this->invoicer->downloadInFormat('isdocx',
                    '/tmp/'),
                \FlexiPeeHP\Formats::$formats['ISDOCx']['content-type']);
        }
    }

    /**
     * Send Remind
     *
     * @return boolean
     */
    public function send()
    {
        return $this->mailer->send();
    }
}
