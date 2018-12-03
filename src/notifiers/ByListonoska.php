<?php

/**
 * FlexiPeeHP - Remind by paper Mail class 
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2018 Spoje.Net
 */
class ByListonoska extends \Ease\Sand
{
    /**
     *
     * @var boolean status 
     */
    public $result = null;

    /**
     *
     * @var \FlexiPeeHP\Reminder\PDFPage 
     */
    public $pdfer = null;

    /**
     *
     * @var \FlexiPeeHP\FakturaVydana
     */
    public $invoicer;

    /**
     * Current customer
     * @var \FlexiPeeHP\Adresar 
     */
    public $address = null;

    /**
     * eMail notification
     * 
     * @param FlexiPeeHP\Reminder\Upominac $reminder
     * @param int                          $score     weeks of due
     * @param array                        $debts     array of debts by current customer
     */
    public function __construct($reminder, $score, $debts)
    {
        $result        = false;
        parent::__construct();
        $this->address = $reminder->customer->adresar;

        if ($this->checkReqiments()) {
            if ($this->compile($score, $reminder->customer, $debts)) {
                $result = $this->send();
//            file_put_contents('/var/tmp/upominka.html',$this->pdfer->htmlDocument);
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
     * @param \FlexiPeeHP\Bricks\Customer $customer
     * @param array                       $clientDebts
     * 
     * @return boolean
     */
    public function compile($score, $customer, $clientDebts)
    {
        $result         = false;
        $email          = $customer->adresar->getNotificationEmailAddress();
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

            $this->pdfer = new \FlexiPeeHP\Reminder\PDFPage($subject);

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
                $this->pdfer->addItem($headingTable);
            } else {
                $this->pdfer->addItem($heading);
            }

            $this->pdfer->addItem(new \Ease\Html\PTag());
            $this->pdfer->addItem(new \Ease\Html\DivTag(nl2br($upominka->getDataValue('textNad'))));
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
            $this->pdfer->addItem(new \Ease\Html\PTag('<br clear="all"/>'));
            $this->pdfer->addItem($debtsTable);
            $this->pdfer->addItem(new \Ease\Html\PTag('<br clear="all"/>'));
            $this->pdfer->addItem(new \Ease\Html\DivTag(nl2br($upominka->getDataValue('textPod'))));
            $this->pdfer->addItem(new \Ease\Html\PTag('<br clear="all"/>'));
            $this->pdfer->addItem(new \Ease\Html\HrTag());
            $this->pdfer->addItem(new \Ease\Html\DivTag(nl2br($upominka->getDataValue('zapati'))));

            if (defined('QR_PAYMENTS') && constant('QR_PAYMENTS')) {
                $this->pdfer->addItem(FlexiPeeHP\Reminder\Upominka::qrPayments($clientDebts));
            }

            $result = true;
        } else {
            $this->addStatusMessage(sprintf(_('Client %s without email %s !!!'),
                    $nazev, $this->firmer->getApiURL()), 'error');
        }
        return $result;
    }

    public function checkReqiments()
    {
        return (!empty($this->address->getDataValue('nazev')) || !empty($this->address->getDataValue('popis')) )
            && !empty($this->address->getDataValue('ulice')) && !empty($this->address->getDataValue('mesto'))
            && !empty($this->address->getDataValue('psc'));
    }

    /**
     * Send Remind
     *
     * @return boolean
     */
    public function send()
    {

        $pdfName = '/var/tmp/remind.pdf';
        file_put_contents($pdfName, $this->pdfer->getPdf());
        $token   = new \Listonoska\API\Token(constant('LISTONOSKA_ID'),
            constant('LISTONOSKA_KEY'));

        $token->getToken(); // vrátí token

        $listOfValues = \Listonoska\API\ListsOfValues($token);
        $listOfValues->getDeliveryTypes(); // číselník typů dodání
        $listOfValues->getPrintTypes(); // číselník typů tisku
        $listOfValues->getIsoCodes(); // číselník iso kódů

        $myCompanySettings = new \FlexiPeeHP\Nastaveni(1);

        $data = array(
            'letterName' => $this->pdfer->pageTitle,
            'deliveryType' => 169,
            'printType' => 0,
            'senderCompany' => $myCompanySettings->getDataValue('nazFirmy'),
            'senderPerson' => $myCompanySettings->getDataValue('oprJmeno').' '.$myCompanySettings->getDataValue('oprPrijmeni'),
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

        $letter   = new \Listonoska\API\Letter($token);
        $response = $letter->sendLetter($data); // odešleme dopis, vrátí se nám info o odeslaném dopisu

        return $response;
    }
}
