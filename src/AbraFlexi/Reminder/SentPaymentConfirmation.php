<?php

/**
 * AbraFlexi Sent Payment Confirmation
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2021 Vitex Software
 */

namespace AbraFlexi\Reminder;

/**
 * Description of PotvrzeniUhrady
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class SentPaymentConfirmation extends Mailer {

    /**
     * Signature
     * @var string 
     */
    static $signature = '';

    /**
     * Odešle potvrzení úhrady
     * @param \AbraFlexi\FakturaVydana $invoice
     */
    public function __construct($invoice = null) {
        if (!is_null($invoice)) {
            $this->assignInvoice($invoice);
        }
    }

    /**
     * 
     * @param FakturaVydana $invoice
     */
    public function assignInvoice($invoice) {
        $defaultLocale = 'cs_CZ';
        setlocale(LC_ALL, $defaultLocale);
        putenv("LC_ALL=$defaultLocale");

        $body = new \Ease\Container();
        $to = (new \AbraFlexi\Adresar($invoice->getDataValue('firma')))->getNotificationEmailAddres();

        $customerName = $invoice->getDataValue('firma@showAs');
        if (empty($customerName)) {
            $customerName = \AbraFlexi\RO::uncode($invoice->getDataValue('firma'));
        }

        $this->addItem(new \AbraFlexi\ui\CompanyLogo(['align' => 'right', 'id' => 'companylogo',
                    'height' => '50', 'title' => _('Company logo')]));

        $prober = new \AbraFlexi\Company();
        $infoRaw = $prober->getFlexiData();
        if (count($infoRaw) && !array_key_exists('success', $infoRaw)) {
            $info = \Ease\Functions::reindexArrayBy($infoRaw, 'dbNazev');
            $myCompany = $prober->getCompany();
            if (array_key_exists($myCompany, $info)) {
                $this->addItem(new \Ease\Html\H2Tag($info[$myCompany]['nazev']));
            }
        }


        $this->addItem(new \Ease\Html\DivTag(sprintf(_('Dear partner %s,'),
                                $customerName)));
        $this->addItem(new \Ease\Html\DivTag("\n<br>"));

        $this->addItem(new \Ease\Html\DivTag(sprintf(_('we confirm sending bank order to pay your invoice %s'),
                                $invoice->getDataValue('cisDosle'))));
        $this->addItem(new \Ease\Html\DivTag("\n<br>"));

        $body->addItem(new \Ease\Html\DivTag(_('With greetings')));

        $this->addItem(new \Ease\Html\DivTag("\n<br>"));

//        $body->addItem(nl2br(self::$signature));

        parent::__construct($to,
                sprintf(_('Confirmation of sending of invoice %s payment'), $invoice->getDataValue('cisDosle')));
        $this->setMailHeaders(['Cc' => constant('SEND_INFO_TO')]);
        $this->addItem($body);
    }

}
