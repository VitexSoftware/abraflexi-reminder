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
    public $mailer   = null;
    public $firmer   = null;

    /**
     * Invoice
     * @var \FlexiPeeHP\FakturaVydana
     */
    public $invoicer = null;

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
        $result = false;
        $email  = $customer->adresar->getNotificationEmailAddress();
        $nazev  = $customer->adresar->getDataValue('nazev');

        if ($email) {
            $sumsCelkem = [];
            $invoices   = [];
            foreach ($clientDebts as $debt) {

                if ($debt['mena'] == 'code:CZK') {
                    $amount = $debt['zbyvaUhradit'];
                } else {
                    $amount = $debt['zbyvaUhraditMen'];
                }

                if (!array_key_exists($debt['mena'], $sumsCelkem)) {
                    $sumsCelkem[$debt['mena']] = 0;
                }

                $sumsCelkem[$debt['mena']] += $amount;

                $ddiff      = \FlexiPeeHP\FakturaVydana::overdueDays($debt['datSplat']);
                $invoices[] = $debt['kod'].' v.s.: '.$debt['varSym'].' '.$amount.' '.str_replace('code:',
                        '',
                        $debt['mena'].'  '.\FlexiPeeHP\FlexiBeeRO::flexiDateToDateTime($debt['datSplat'])->format('d.m.Y').' ('.$ddiff.' dní po splatnosti)');
            }

            $to = $email;

            $dnes    = new \DateTime();
            $subject = $this->getDataValue('hlavicka').' ke dni '.$dnes->format('d.m.Y');
            $body    = $this->getDataValue('uvod').
                "\n\n".$this->getDataValue('textNad')."\n\n".
                sprintf(_("%s \n\n-----------------------\n celkem za %s,-"),
                    implode("\n", $invoices), self::sums($sumsCelkem)).
                "\n\n".$this->getDataValue('textPod')."\n".
                "\n\n".$this->getDataValue('zapati')."\n";

            $this->mailer = new \Ease\Mailer($to, $subject, $body);
            $this->mailer->addItem(new \FlexiPeeHP\ui\CompanyLogo(['align' => 'right',
                    'id' => 'companylogo',
                    'height' => '50', 'title' => _('Company logo')]));

            $this->addAttachments($clientDebts);
            $result = true;
        } else {
            $this->addStatusMessage(sprintf(_('Klient %s nema email %s !!!'),
                    $nazev, $this->firmer->getApiURL()), 'error');
        }
        return $result;
    }

    /**
     * Format sums array
     * 
     * @param array $sumsRaw
     */
    public static function sums($sumsRaw)
    {
        $sums = [];
        foreach ($sumsRaw as $currency => $amount) {
            $sums[] = $amount.' '.\FlexiPeeHP\FlexiBeeRO::uncode($currency);
        }
        return implode(' ', $sums);
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
     *
     * @param array $clientDebts
     */
    public function addAttachments($clientDebts)
    {
        foreach ($clientDebts as $debtCode => $debt) {
            if (defined('MAX_MAIL_SIZE') && ($this->getCurrentMailSize($this->mailer)
                > constant('MAX_MAIL_SIZE'))) {
                $this->mailer->addItem(new \Ease\Html\DivTag(sprintf(_('Not enoug space in this mail for attaching %s '),
                            $debtCode)));
                continue;
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
