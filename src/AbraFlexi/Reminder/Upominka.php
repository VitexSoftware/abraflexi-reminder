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
 * Description of Remind.
 *
 * @author vitex
 */
class Upominka extends \AbraFlexi\RW
{
    public \AbraFlexi\Adresar $firmer;

    /**
     * Invoice.
     */
    public \AbraFlexi\FakturaVydana $invoicer;
    public static string $styles = <<<'EOD'

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
}
EOD;

    /**
     * AbraFlexi Remind tempalte helper.
     *
     * @param string $init
     * @param array  $options
     */
    public function __construct($init = null, $options = [])
    {
        $this->evidence = 'sablona-upominky';
        parent::__construct($init, $options);
        $this->invoicer = new \AbraFlexi\FakturaVydana();
        $this->firmer = new \AbraFlexi\Adresar();
    }

    /**
     * Load.
     *
     * @param string $template prvniUpominka|druhaUpominka|pokusOSmir|inventarizace
     */
    public function loadTemplate($template): void
    {
        $this->takeData(current($this->getColumnsFromAbraFlexi(
            '*',
            ['typSablonyK' => 'typSablony.'.$template],
        )));
    }

    /**
     * Obtain all debts sums indexed by currency.
     */
    public static function getSums(array $debts): array
    {
        $sumsCelkem = [];

        foreach ($debts as $debt) {
            $currency = \AbraFlexi\Functions::uncode((string) $debt['mena']);

            if ($currency === 'CZK') {
                $amount = $debt['zbyvaUhradit'];
            } else {
                $amount = $debt['zbyvaUhraditMen'];
            }

            if (!\array_key_exists($currency, $sumsCelkem)) {
                $sumsCelkem[$currency] = $amount;
            } else {
                $sumsCelkem[$currency] += $amount;
            }
        }

        return $sumsCelkem;
    }

    /**
     * Block of QR Payment.
     *
     * @param array $debts
     *
     * @return \Ease\Html\DivTag
     */
    public static function qrPayments($debts)
    {
        $invoicer = new \AbraFlexi\FakturaVydana();
        $qrDiv = new \Ease\Html\DivTag();
        $qrDiv->addItem(new \Ease\Html\H3Tag(_('QR Invoices')));

        foreach ($debts as $invoiceId => $invoiceInfo) {
            $currency = \AbraFlexi\Functions::uncode((string) $invoiceInfo['mena']);

            if ($currency === 'CZK') {
                $amount = $invoiceInfo['zbyvaUhradit'];
            } else {
                $amount = $invoiceInfo['zbyvaUhraditMen'];
            }

            $invoicer->setMyKey((int) $invoiceInfo['id']);
            $invoicer->setEvidence($invoiceInfo['evidence']);
            $qrDiv->addItem(new \Ease\Html\DivTag($invoiceId.' <strong>'.$amount.'</strong> '.$currency));

            try {
                $qrCode = $invoicer->getQrCodeBase64(200);
                $qrDiv->addItem(new \Ease\Html\ImgTag(
                    $qrCode,
                    _('QR Payment'),
                    ['width' => 200, 'height' => 200, 'title' => $invoiceId],
                ));
            } catch (\AbraFlexi\Exception $exc) {
            }
        }

        return $qrDiv;
    }

    /**
     * Format Czech Currency.
     *
     * @param float $price
     *
     * @return string
     */
    public static function formatCurrency($price)
    {
        return number_format($price, 2, ',', ' ');
    }
}
