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
 * Description of PotvrzeniPrijetiFaktury.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class InvoiceRecievingConfirmation extends RemindMailer
{
    /**
     * Company signature.
     */
    public static string $signature = '';

    /**
     * Odešle potvrzení úhrady.
     *
     * @param \AbraFlexi\FakturaVydana $invoice
     */
    public function __construct($invoice = null)
    {
        parent::__construct(null, null);

        if (null !== $invoice) {
            $this->assignInvoice($invoice);
        }
    }

    /**
     * @param \AbraFlexi\FakturaPrijata $invoice
     */
    public function assignInvoice($invoice): void
    {
        $defaultLocale = 'cs_CZ';
        setlocale(\LC_ALL, $defaultLocale);
        putenv("LC_ALL={$defaultLocale}");
        $body = new \Ease\Container();
        $to = (new \AbraFlexi\Adresar($invoice->getDataValue('firma')))->getNotificationEmailAddress();
        $customerName = $invoice->getDataValue('firma')->showAs;

        if (empty($customerName)) {
            $customerName = \AbraFlexi\Functions::uncode($invoice->getDataValue('firma'));
        }

        $this->addItem(new \AbraFlexi\ui\CompanyLogo(['align' => 'right', 'id' => 'companylogo',
            'height' => '50', 'title' => _('Company logo')]));
        $prober = new \AbraFlexi\Company();
        $infoRaw = $prober->getFlexiData();

        if (\count($infoRaw) && !\array_key_exists('success', $infoRaw)) {
            $info = \Ease\Functions::reindexArrayBy($infoRaw, 'dbNazev');
            $myCompany = $prober->getCompany();

            if (\array_key_exists($myCompany, $info)) {
                $this->addItem(new \Ease\Html\H2Tag($info[$myCompany]['nazev']));
            }
        }

        $this->addItem(new \Ease\Html\DivTag(sprintf(
            _('Dear customer %s,'),
            $customerName,
        )));
        $this->addItem(new \Ease\Html\DivTag("\n<br>"));
        $this->addItem(new \Ease\Html\DivTag(sprintf(
            _('we confirm receipt of invoice %s as %s '),
            $invoice->getDataValue('cisDosle'),
            $invoice->getDataValue('kod'),
        )));
        $this->addItem(new \Ease\Html\DivTag("\n<br>"));
        $body->addItem(new \Ease\Html\DivTag(_('With greetings')));
        $this->addItem(new \Ease\Html\DivTag("\n<br>"));
        $body->addItem(nl2br($this->getSignature()));
        parent::__construct(
            $to,
            sprintf(_('Confirmation of receipt your invoice %s'), \AbraFlexi\Functions::uncode($invoice->getRecordIdent())),
        );
        $this->setMailHeaders(['Cc' => \Ease\Shared::cfg('SEND_INFO_TO')]);
        $this->addItem($body);
    }
}
