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

/**
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2023 Vitex Software
 */

namespace AbraFlexi\Reminder\Notifier;

/**
 * Description of ByDatovka.
 *
 * @author vitex
 */
class ByDatovka extends \Defr\CzechDataBox\DataBox implements \AbraFlexi\Reminder\notifier
{
    public FakturaVydana $invoicer;
    private $pdf;
    private $subject;
    private $pdfFiles = [];
    private $dataBoxId;

    //    /**
    //     *
    //     * @var \Defr\CzechDataBox\DataBoxSimpleApi
    //     */
    //    protected $simpleApi;

    public function __construct(&$reminder, $score, $debts)
    {
        parent::__construct(null);

        if (file_exists($this->directory) === false) {
            mkdir($this->directory);
        }

        $this->loginWithUsernameAndPassword(\Ease\Functions::cfg('DATOVKA_LOGIN'), \Ease\Functions::cfg('DATOVKA_PASSWORD'), true); // Pro ostrou verzi
        $result = false;
        $ic = $reminder->customer->adresar->getDataValue('ic');

        if ($ic) {
            $this->dataBoxId = $this->ico2databoxid($ic);

            if ($this->dataBoxId) {
                $this->pdf = $reminder->savePdfRemind($this->directory.'/upominka.pdf');

                if ($this->compile($score, $reminder->customer, $debts)) {
                    $result = $this->send();
                } else {
                    $this->reminder->addStatusMessage(_('Remind was not sent'), 'warning');
                }
            } else {
                $this->reminder->addStatusMessage();
            }
        }

        $this->result = $result;
    }

    /**
     * Compile Reminder message with its contents.
     *
     * @param int      $score       Weeks after due date
     * @param Customer $customer
     * @param array    $clientDebts
     *
     * @return bool
     */
    public function compile($score, $customer, $clientDebts)
    {
        $upominka = new \AbraFlexi\Reminder\Upominka();

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

            default:
                $lastInvDays = Upominac::getDaysToLastInventarization($clientDebts);

                if ($lastInvDays < 14) {
                    $this->addStatusMessage(
                        sprintf(
                            _('Last  remind / inventarization for %s send before %d days; skipping'),
                            RO::uncode($customer),
                            $lastInvDays,
                        ),
                        'debug',
                    );

                    return false;
                }

                $upominka->loadTemplate('inventarizace');
        }

        $invoices = [];
        $dnes = new \DateTime();
        $this->subject = $upominka->getDataValue('hlavicka').' ke dni '.$dnes->format('d.m.Y');
        $this->invoicer = new \AbraFlexi\FakturaVydana();
        $this->addAttachments($clientDebts);

        return \count($this->pdfFiles);
    }

    /**
     * Attach PDF and ISDOC invoices.
     *
     * @param array $clientDebts
     */
    public function addAttachments($clientDebts): void
    {
        foreach ($clientDebts as $debtCode => $debt) {
            if (\array_key_exists('evidence', $debt)) {
                $this->invoicer->setEvidence($debt['evidence']);
            }

            $this->invoicer->setMyKey(\AbraFlexi\RO::code($debt['kod']));
            $this->pdfFiles[] = $this->invoicer->downloadInFormat('pdf', '/tmp/');
        }
    }

    public function ico2databoxid($ico)
    {
        $boxId = '';
        $requestRaw = '<GetInfoRequest xmlns="http://seznam.gov.cz/ovm/ws/v1"><Ico>'.$ico.'</Ico></GetInfoRequest>';
        $url = 'https://www.mojedatovaschranka.cz/sds/ws/call';
        $curl = curl_init();
        curl_setopt($curl, \CURLOPT_URL, $url);
        curl_setopt($curl, \CURLOPT_POST, true);
        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, true);
        $headers = [
            'Accept: application/xml',
            'Content-Type: application/xml',
        ];
        curl_setopt($curl, \CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, \CURLOPT_POSTFIELDS, $requestRaw);
        $resp = curl_exec($curl);
        curl_close($curl);

        $sds = new \SimpleXMLElement($resp);

        if (property_exists($sds, 'Osoba') && property_exists($sds->Osoba, 'ISDS')) {
            $boxId = current($sds->Osoba->ISDS);
        }

        return $boxId;
    }

    public function send(): void
    {
        $message = $this->simpleApi->createBasicDataMessage($this->dataBoxId, $this->subject, $this->pdfFiles);
        $sentMessage = $this->simpleApi->sendDataMessage($message);

        if ($sentMessage->getDmStatus()->getDmStatusCode() !== '0000') {
            // Handle errors
        }
    }
}
