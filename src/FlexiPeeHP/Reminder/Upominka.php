<?php
/**
 * FlexiPeeHP - Remind class Brick
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2018 Vitex Software
 */

namespace FlexiPeeHP\Reminder;

/**
 * Description of Upominka
 *
 * @author vitex
 */
class Upominka extends \FlexiPeeHP\FlexiBeeRW
{
    public $evidence = 'sablona-upominky';

    /**
     *
     * @var \Ease\Mailer 
     */
    public $mailer = null;

    /**
     *
     * @var \FlexiPeeHP\Adresar 
     */
    public $firmer = null;

    /**
     * Invoice
     * @var \FlexiPeeHP\FakturaVydana
     */
    public $invoicer = null;

    /**
     *
     * @var string 
     */
    static $styles = '
table.greyGridTable {
  border: 2px solid #FFFFFF;
  width: 100%;
  text-align: center;
  border-collapse: collapse;
}
table.greyGridTable td, table.greyGridTable th {
  border: 1px solid #FFFFFF;
  padding: 3px 4px;
}
table.greyGridTable tbody td {
  font-size: 13px;
}
table.greyGridTable td:nth-child(even) {
  background: #EBEBEB;
}
table.greyGridTable thead {
  background: #FFFFFF;
  border-bottom: 4px solid #333333;
}
table.greyGridTable thead th {
  font-size: 15px;
  font-weight: bold;
  color: #333333;
  text-align: center;
  border-left: 2px solid #333333;
}
table.greyGridTable thead th:first-child {
  border-left: none;
}

table.greyGridTable tfoot {
  font-size: 14px;
  font-weight: bold;
  color: #333333;
  border-top: 4px solid #333333;
}
table.greyGridTable tfoot td {
  font-size: 14px;
}';

    /**
     * 
     * @param type $init
     * @param type $options
     */
    public function __construct($init = null, $options = array())
    {
        parent::__construct($init, $options);

        $this->invoicer = new \FlexiPeeHP\FakturaVydana();
        $this->firmer   = new \FlexiPeeHP\Adresar();
    }

    /**
     * Load
     * @param string $template prvniUpominka|druhaUpominka|pokusOSmir|inventarizace
     */
    public function loadTemplate($template)
    {
        $this->takeData(current($this->getColumnsFromFlexibee('*',
                    ['typSablonyK' => 'typSablony.'.$template])));
    }

    /**
     * Compile Reminder message with its contents
     * 
     * @param \FlexiPeeHP\Bricks\Customer $customer
     * @param array                       $clientDebts
     * 
     * @return boolean
     */
    public function compile($customer, $clientDebts)
    {
        $result       = false;
        $email        = $customer->adresar->getNotificationEmailAddress();
        $nazev        = $customer->adresar->getDataValue('nazev');
        $this->firmer = &$customer->adresar;
        if ($email) {
            $sumsCelkem = [];
            $invoices   = [];
            foreach ($clientDebts as $debt) {
                $currency = \FlexiPeeHP\FlexiBeeRO::uncode($debt['mena']);
                if ($currency == 'CZK') {
                    $amount = $debt['zbyvaUhradit'];
                } else {
                    $amount = $debt['zbyvaUhraditMen'];
                }
                if (!array_key_exists($currency, $sumsCelkem)) {
                    $sumsCelkem[$currency] = $amount;
                } else {
                    $sumsCelkem[$currency] += $amount;
                }
            }

            $to = $email;

            $dnes    = new \DateTime();
            $subject = $this->getDataValue('hlavicka').' ke dni '.$dnes->format('d.m.Y');


            if (defined('MUTE') && constant('MUTE')) {
                $to = constant('EASE_MAILTO');
            }

            $this->mailer = new Mailer($to, $subject);

            $heading = new \Ease\Html\DivTag($this->getDataValue('uvod').' '.$nazev);
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
            $this->mailer->addItem(new \Ease\Html\DivTag(nl2br($this->getDataValue('textNad'))));
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
                    self::formatCurrency($amount),
                    $currency,
                    \FlexiPeeHP\FlexiBeeRO::flexiDateToDateTime($debt['datSplat'])->format('d.m.Y'),
                    \FlexiPeeHP\FakturaVydana::overdueDays($debt['datSplat'])
                ]);
            }

            $debtsTable->addRowFooterColumns(['', _('Total'), Upominac::formatTotals($sumsCelkem)]);
            $this->mailer->addItem(new \Ease\Html\PTag('<br clear="all"/>'));
            $this->mailer->addItem($debtsTable);
            $this->mailer->addItem(new \Ease\Html\PTag('<br clear="all"/>'));
            $this->mailer->addItem(new \Ease\Html\DivTag(nl2br($this->getDataValue('textPod'))));
            $this->mailer->addItem(new \Ease\Html\PTag('<br clear="all"/>'));
            $this->mailer->addItem(new \Ease\Html\HrTag());
            $this->mailer->addItem(new \Ease\Html\DivTag(nl2br($this->getDataValue('zapati'))));

            if (defined('QR_PAYMENTS') && constant('QR_PAYMENTS')) {
                $this->mailer->addItem($this->qrPayments($clientDebts));
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
     * Block of QR Payment
     * 
     * @param array  $debts
     * 
     * @return \Ease\Html\DivTag
     */
    public function qrPayments($debts)
    {
        $qrDiv = new \Ease\Html\DivTag();
        $qrDiv->addItem(new \Ease\Html\H3Tag(_('QR Invoices')));
        foreach ($debts as $invoiceId => $invoiceInfo) {
            $currency = \FlexiPeeHP\FlexiBeeRO::uncode($invoiceInfo['mena']);
            if ($currency == 'CZK') {
                $amount = $invoiceInfo['zbyvaUhradit'];
            } else {
                $amount = $invoiceInfo['zbyvaUhraditMen'];
            }
            $this->invoicer->setMyKey(intval($invoiceInfo['id']));
            $this->invoicer->setEvidence($invoiceInfo['evidence']);
            $qrDiv->addItem(new \Ease\Html\DivTag($invoiceId.' <strong>'.$amount.'</strong> '.$currency));
            $qrDiv->addItem(new \Ease\Html\ImgTag($this->invoicer->getQrCodeBase64(200),
                    _('QR Payment'),
                    ['width' => 200, 'height' => 200, 'title' => $invoiceId]));
        }
        return $qrDiv;
    }

    /**
     * 
     * @param \Ease\Mailer $mailer
     */
    public function getCurrentMailSize($mailer)
    {
        $mailer->finalize();
        $mailer->finalized = false;
        if (function_exists('mb_internal_encoding') &&
            (((int) ini_get('mbstring.func_overload')) & 2)) {
            return mb_strlen($mailer->mailBody, '8bit');
        } else {
            return strlen($mailer->mailBody);
        }
    }

    /**
     * Attach PDF and ISDOC invoices
     * @param array $clientDebts
     */
    public function addAttachments($clientDebts)
    {
        foreach ($clientDebts as $debtCode => $debt) {
            if (defined('MAX_MAIL_SIZE') && ($this->getCurrentMailSize($this->mailer)
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
     * Format Czech Currency
     * 
     * @param float $price
     * 
     * @return string
     */
    public static function formatCurrency($price)
    {
        return number_format($price, 2, ',', ' ');
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
