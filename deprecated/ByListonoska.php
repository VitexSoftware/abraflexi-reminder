<?php

namespace AbraFlexi\Reminder\Notifier;

use DateTime;
use Ease\Html\DivTag;
use Ease\Html\HrTag;
use Ease\Html\PTag;
use Ease\Html\TableTag;
use Ease\Html\TdTag;
use Ease\Html\TrTag;
use Ease\Sand;
use AbraFlexi\Adresar;
use AbraFlexi\Bricks\Customer;
use AbraFlexi\FakturaVydana;
use AbraFlexi\RO;
use AbraFlexi\Nastaveni;
use AbraFlexi\Reminder\PDFPage;
use AbraFlexi\Reminder\Upominac;
use AbraFlexi\Reminder\Upominka;
use AbraFlexi\ui\CompanyLogo;

/**
 * AbraFlexi - Remind by paper Mail class 
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2018 Spoje.Net
 */
class ByListonoska extends Sand {

    /**
     *
     * @var boolean status 
     */
    public $result = null;

    /**
     *
     * @var PDFPage 
     */
    public $pdfer = null;

    /**
     *
     * @var FakturaVydana
     */
    public $invoicer;

    /**
     * Current customer
     * @var Adresar 
     */
    public $address = null;

    /**
     * @var \AbraFlexi\Adresar
     */
    private $firmer;

    /**
     * eMail notification
     * 
     * @param Upominac $reminder
     * @param int                          $score     weeks of due
     * @param array                        $debts     array of debts by current customer
     */
    public function __construct($reminder, $score, $debts) {
        $result = false;
        $this->address = $reminder->customer->adresar;

        if ($this->checkReqiments()) {
            if ($this->compile($score, $reminder->customer, $debts)) {
                $result = $this->send();
//            file_put_contents('/var/tmp/upominka.html',$this->pdfer->htmlDocument);
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
        } else {
            $this->addStatusMessage(sprintf(_('Incomplete post address for %s'),
                            $this->address->getRecordCode()), 'warning');
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
                    $upominka->loadTemplate('inventarizace');
            }


            $invoices = [];

            $to = $email;

            $dnes = new DateTime();
            $subject = $upominka->getDataValue('hlavicka') . ' ke dni ' . $dnes->format('d.m.Y');

            if (defined('MUTE') && constant('MUTE')) {
                $to = constant('EASE_MAILTO');
            }

            $this->pdfer = new PDFPage($subject);

            $heading = new DivTag($upominka->getDataValue('uvod') . ' ' . $nazev);
            if (defined('ADD_LOGO') && constant('ADD_LOGO')) {
                $headingTableRow = new TrTag();
                $headingTableRow->addItem(new TdTag($heading));
                $logo = new CompanyLogo(['align' => 'right',
                    'id' => 'companylogo',
                    'height' => '50', 'title' => _('Company logo')]);
                $headingTableRow->addItem(new TdTag($logo,
                                ['width' => '200px']));
                $headingTable = new TableTag($headingTableRow,
                        ['width' => '100%']);

                $this->pdfer->addItem($headingTable);
            } else {
                $this->pdfer->addItem($heading);
            }

            $this->pdfer->addItem(new PTag());
            $this->pdfer->addItem(new DivTag(nl2br($upominka->getDataValue('textNad'))));
            $debtsTable = new TableTag(null,
                    ['class' => 'greyGridTable']);
            $debtsTable->addRowHeaderColumns([_('Code'), _('var. sym.'), _('Amount'),
                _('Currency'), _('Due Date'), _('overdue days')]);

            foreach ($clientDebts as $debt) {
                $currency = RO::uncode($debt['mena']);
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
                    $debt['datSplat']->format('d.m.Y'),
                    FakturaVydana::overdueDays($debt['datSplat'])
                ]);
            }

            $debtsTable->addRowFooterColumns(['', _('Total'), Upominac::formatTotals(Upominka::getSums($clientDebts))]);
            $this->pdfer->addItem(new PTag('<br clear="all"/>'));
            $this->pdfer->addItem($debtsTable);
            $this->pdfer->addItem(new PTag('<br clear="all"/>'));
            $this->pdfer->addItem(new DivTag(nl2br($upominka->getDataValue('textPod'))));
            $this->pdfer->addItem(new PTag('<br clear="all"/>'));
            $this->pdfer->addItem(new HrTag());
            $this->pdfer->addItem(new DivTag(nl2br($upominka->getDataValue('zapati'))));

            if (defined('QR_PAYMENTS') && constant('QR_PAYMENTS')) {
                $this->pdfer->addItem(Upominka::qrPayments($clientDebts));
            }

            $result = true;
        } else {
            $this->addStatusMessage(sprintf(_('Client %s without email %s !!!'),
                            $nazev, $this->firmer->getApiURL()), 'error');
        }
        return $result;
    }

    public function checkReqiments() {
        return (!empty($this->address->getDataValue('nazev')) || !empty($this->address->getDataValue('popis')) ) && !empty($this->address->getDataValue('ulice')) && !empty($this->address->getDataValue('mesto')) && !empty($this->address->getDataValue('psc'));
    }

    /**
     * Send Remind
     *
     * @return boolean
     */
    public function send() {
        if (\Ease\Functions::cfg('LISTONOSKA_ID') && \Ease\Functions::cfg('LISTONOSKA_KEY')) {
            $pdfName = '/var/tmp/remind.pdf';
            file_put_contents($pdfName, $this->pdfer->getPdf());
            $token = new \Listonoska\API\Token(\Ease\Functions::cfg('LISTONOSKA_ID'),
                    \Ease\Functions::cfg('LISTONOSKA_KEY'));

            $token->getToken(); // vrátí token

            $listOfValues = \Listonoska\API\ListsOfValues($token);
            $listOfValues->getDeliveryTypes(); // číselník typů dodání
            $listOfValues->getPrintTypes(); // číselník typů tisku
            $listOfValues->getIsoCodes(); // číselník iso kódů

            $myCompanySettings = new Nastaveni(1);

            $data = array(
                'letterName' => $this->pdfer->pageTitle,
                'deliveryType' => 169,
                'printType' => 0,
                'senderCompany' => $myCompanySettings->getDataValue('nazFirmy'),
                'senderPerson' => $myCompanySettings->getDataValue('oprJmeno') . ' ' . $myCompanySettings->getDataValue('oprPrijmeni'),
                'senderStreet' => $myCompanySettings->getDataValue('postUliceNazev'),
                'senderHouseNumber' => $myCompanySettings->getDataValue('postCisPop'),
                'senderOrientationNumber' => $myCompanySettings->getDataValue('postCisOr'),
                'senderCity' => $myCompanySettings->getDataValue('postMesto'),
                'senderZip' => $myCompanySettings->getDataValue('postPsc'),
                'addresse' => array(
                    array(// první adresát
                        'company' => $this->address->getDataValue('nazev'),
                        'person' => $this->address->getDataValue('popis'),
                        'street' => $this->address->getDataValue('ulice'),
                        'city' => $this->address->getDataValue('mesto'),
                        'zip' => $this->address->getDataValue('psc')
                    ),
                ),
                'pdf1' => new CurlFile(realpath($pdfName)) // pdf soubor
            );

            $letter = new \Listonoska\API\Letter($token);
            $response = $letter->sendLetter($data); // odešleme dopis, vrátí se nám info o odeslaném dopisu
        } else {
            $response = null;
            $this->addStatusMessage(_('Please set LISTONOSKA_ID and LISTONOSKA_KEY in configuration'));
        }
        return $response;
    }

}
