<?php

/**
 * AbraFlexi - Remind class Brick
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2018 Vitex Software
 */

namespace AbraFlexi\Reminder;

/**
 * Description of Upominka
 *
 * @author vitex
 */
class Upominka extends \AbraFlexi\RW {

    /**
     * Remind templates evidence name
     * @var string 
     */
    public $evidence = 'sablona-upominky';

    /**
     *
     * @var \AbraFlexi\Adresar 
     */
    public $firmer = null;

    /**
     * Invoice
     * @var \AbraFlexi\FakturaVydana
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
    public function __construct($init = null, $options = array()) {
        parent::__construct($init, $options);

        $this->invoicer = new \AbraFlexi\FakturaVydana();
        $this->firmer = new \AbraFlexi\Adresar();
    }

    /**
     * Load
     * @param string $template prvniUpominka|druhaUpominka|pokusOSmir|inventarizace
     */
    public function loadTemplate($template) {
        $this->takeData(current($this->getColumnsFromAbraFlexi('*',
                                ['typSablonyK' => 'typSablony.' . $template])));
    }

    /**
     * Obtain all debts sums indexed by currency
     * 
     * @param array $debts
     * 
     * @return array
     */
    public static function getSums($debts) {
        $sumsCelkem = [];
        foreach ($debts as $debt) {
            $currency = \AbraFlexi\RO::uncode($debt['mena']);
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
        return $sumsCelkem;
    }

    /**
     * Block of QR Payment
     * 
     * @param array  $debts
     * 
     * @return \Ease\Html\DivTag
     */
    public static function qrPayments($debts) {
        $invoicer = new \AbraFlexi\FakturaVydana();
        $qrDiv = new \Ease\Html\DivTag();
        $qrDiv->addItem(new \Ease\Html\H3Tag(_('QR Invoices')));
        foreach ($debts as $invoiceId => $invoiceInfo) {
            $currency = \AbraFlexi\RO::uncode($invoiceInfo['mena']);
            if ($currency == 'CZK') {
                $amount = $invoiceInfo['zbyvaUhradit'];
            } else {
                $amount = $invoiceInfo['zbyvaUhraditMen'];
            }
            $invoicer->setMyKey(intval($invoiceInfo['id']));
            $invoicer->setEvidence($invoiceInfo['evidence']);
            $qrDiv->addItem(new \Ease\Html\DivTag($invoiceId . ' <strong>' . $amount . '</strong> ' . $currency));
            $qrDiv->addItem(new \Ease\Html\ImgTag($invoicer->getQrCodeBase64(200),
                            _('QR Payment'),
                            ['width' => 200, 'height' => 200, 'title' => $invoiceId]));
        }
        return $qrDiv;
    }

    /**
     * Format Czech Currency
     * 
     * @param float $price
     * 
     * @return string
     */
    public static function formatCurrency($price) {
        return number_format($price, 2, ',', ' ');
    }

}
