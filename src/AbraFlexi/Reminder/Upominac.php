<?php

/**
 * AbraFlexi - Reminder class
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2018 Spoje.Net 2019-2023 VitexSoftware
 */

namespace AbraFlexi\Reminder;

use DateTime;

/**
 * Description of Upominka
 *
 * @author vitex
 */
class Upominac extends \AbraFlexi\RW
{
    /**
     *
     * @var \AbraFlexi\Bricks\Customer
     */
    public $customer = null;

    /**
     * Invoice
     *
     * @var \AbraFlexi\FakturaVydana
     */
    public $invoicer = null;

    /**
     * Reminder
     *
     * @param array $init
     * @param array $options
     */
    public function __construct($init = null, $options = array())
    {
        parent::__construct($init, $options);
        $this->customer = new \AbraFlexi\Bricks\Customer();
        $this->invoicer = new \AbraFlexi\FakturaVydana(null, ['defaultUrlParams' => ['limit' => 0]]);
    }

    /**
     * Obtain customer debths Array
     *
     * @param array    $skipLabels labels of Customer (Addressbook) to skip
     * @param boolean  $cleanLabels clean debtor labels when all is paid
     *
     * @return array of all customer's documents after due date
     */
    public function getCustomersDebts($skipLabels = [], $cleanLabels = false)
    {
        $allDebts = [];
        $this->addStatusMessage(_('Getting clients'), 'debug');
        $clients = $this->customer->getCustomerList();
        $debtCount = 0;
        $this->addStatusMessage(sprintf(_('%s Clients Found'), count($clients)));
        $this->addStatusMessage(_('Getting debts'), 'debug');
        $pos = 0;
        foreach ($clients as $cid => $clientIDs) {
            $pos++;
            $stitky = \AbraFlexi\Stitek::listToArray($clientIDs['stitky']);
            if (count($skipLabels) && array_intersect($skipLabels, $stitky)) {
                continue;
            }

            $debts = $this->customer->getCustomerDebts((int) $clientIDs['id']);
            $this->customer->invoicer->setEvidence('pohledavka');
            $debts2 = $this->customer->getCustomerDebts((int) $clientIDs['id']);
            $this->customer->invoicer->setEvidence('faktura-vydana');
            $debts3 = array_merge(empty($debts) ? [] : $debts, empty($debts2) ? [] : $debts2);
            if (!empty($debts3) && count($debts3)) {
                foreach ($debts3 as $did => $debtInfo) {
                    $allDebts[$cid][$did] = $debtInfo;
                    $debtCount++;
                }
            } else { //All OK
                if ($cleanLabels) {
                    $this->everythingPaidOff($cid, $stitky);
                }
            }
            //$this->addStatusMessage($pos.'/'.count($clients).' '.$cid, 'debug');
        }
        $this->addStatusMessage(sprintf(_('%s Debts Found'), $debtCount));
        return $allDebts;
    }

    /**
     * What to do when no debts found for customer
     *
     * @param int $clientID AddressBook ID
     * @param array $stitky Customer's labels
     *
     * @return boolean customer well processed
     */
    public function everythingPaidOff($clientID, $stitky)
    {
        return $this->enableCustomer(implode(',', $stitky), $clientID);
    }

    /**
     * Enable customer
     *
     * @param string $stitky Labels
     * @param int    $cid    AbraFlexi AddressID
     *
     * @return boolean Customer connect status
     */
    function enableCustomer($stitky, $cid)
    {
        $result = true;
        if (strstr($stitky, 'UPOMINKA') || strstr($stitky, 'NEPLATIC')) {
            $newStitky = array_combine(explode(
                ',',
                str_replace(' ', '', $stitky)
            ), explode(',', $stitky));
            unset($newStitky['UPOMINKA1']);
            unset($newStitky['UPOMINKA2']);
            unset($newStitky['UPOMINKA3']);
            unset($newStitky['NEPLATIC']);
            if (
                $this->customer->adresar->insertToAbraFlexi(['id' => $cid, 'stitky@removeAll' => 'true',
                        'stitky' => $newStitky])
            ) {
                $this->addStatusMessage(sprintf(
                    _('No debts. Clear %s Remind labels'),
                    $cid
                ), 'success');
            } else {
                $this->addStatusMessage(sprintf(
                    _('No debts. Clear %s Remind labels'),
                    $cid
                ), 'error');
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Process All Debts of All Customers
     *
     * @param array $skipLabels Skip Customers (AddressBook) with any of given labels
     *
     * @return int All Debts count
     */
    public function processAllDebts($skipLabels = [])
    {
        $allDebths = $this->getCustomersDebts($skipLabels, true);
        $this->addStatusMessage(sprintf(
            _('%d clients to remind process'),
            count($allDebths)
        ));
        $counter = 0;
        foreach ($allDebths as $cid => $debts) {
            $counter++;
            $this->customer->adresar->loadFromAbraFlexi($cid);
            $this->addStatusMessage(sprintf(
                _('(%d / %d) #%s code: %s %s '),
                $counter,
                count($allDebths),
                $this->customer->adresar->getDataValue('id'),
                $this->customer->adresar->getDataValue('kod'),
                $this->customer->adresar->getDataValue('nazev')
            ), 'debug');
            $this->processUserDebts($cid, $debts);
        }
        return $counter;
    }

    /**
     * Process Customer debts
     *
     * @param array $clientInfo  AbraFlexi Address (Customer)
     * @param array $clientDebts Array provided by customer::getCustomerDebts()
     *
     * @return int max debt score 1: 0-7 days 1: 8-14 days 3: 15 days and more
     */
    public function processUserDebts($clientInfo, $clientDebts)
    {
        $this->customer->adresar->dataReset();
        $this->customer->adresar->setData($clientInfo);
        $this->customer->adresar->updateApiURL();
        $zewlScore = 0;
        $stitky = $clientInfo['stitky'];
        $ddifs = [];
        $invoicesToSave = [];
        $invoicesToLock = [];
        foreach ($clientDebts as $did => $debt) {
            switch ($debt['zamekK']) {
                case 'zamek.zamceno':
                    $this->invoicer->dataReset();
                    $this->invoicer->setMyKey(\AbraFlexi\RO::code($did));
                    $unlock = $this->invoicer->performAction('unlock', 'int');
                    if ($unlock['success'] == 'false') {
                        $this->addStatusMessage(
                            _('Invoice locked: skipping process'),
                            'warning'
                        );
                        break;
                    }
                    $invoicesToLock[$debt['id']] = ['id' => $did];
                case 'zamek.otevreno':
                default:
                    $invoicesToSave[$debt['id']] = ['id' => \AbraFlexi\RO::code($did),
                        'evidence' => $debt['evidence']];
                    $ddiff = \AbraFlexi\FakturaVydana::overdueDays($debt['datSplat']);
                    $ddifs[$debt['id']] = $ddiff;
                    if (($ddiff <= 7) && ($ddiff >= 1)) {
                        $zewlScore = self::maxScore($zewlScore, 1);
                    } else {
                        if (($ddiff > 7 ) && ($ddiff <= 14)) {
                            $zewlScore = self::maxScore($zewlScore, 2);
                        } else {
                            if ($ddiff > 14) {
                                $zewlScore = self::maxScore($zewlScore, 3);
                            }
                        }
                    }

                    break;
            }
        }

        if ($zewlScore == 3 && !array_key_exists('UPOMINKA2', $stitky)) {
            $zewlScore = 2;
        }

        if (!array_key_exists('UPOMINKA1', $stitky)) {
            $zewlScore = 1;
        }
        if ($zewlScore > 0 && (array_sum($ddifs) > 0) && count($invoicesToSave)) {
            if (!array_key_exists('UPOMINKA' . $zewlScore, $stitky)) {
                if (!array_key_exists('NEUPOMINAT', $stitky)) {
                    if ($this->posliUpominku($zewlScore, $clientDebts)) {
                        foreach ($invoicesToSave as $invoiceCode => $invoiceData) {
                            switch ($zewlScore) {
                                case 1:
                                    $colname = 'datUp1';
                                    break;
                                case 2:
                                    $colname = 'datUp2';
                                    break;
                                case 3:
                                    $colname = 'datSmir';
                                    break;
                                default:
                                    $colname = 'poznam';
                                    break;
                            }
                            $invoiceData[$colname] = self::timestampToFlexiDate(time());
                            if ($colname == 'poznam') {
                                $invoiceData[$colname] = 'Inventarizace:' . $invoiceData[$colname];
                            }
                            $this->invoicer->setEvidence($invoiceData['evidence']);
                            if ($this->invoicer->insertToAbraFlexi($invoiceData)) {
                                $this->addStatusMessage(sprintf(
                                    _('%s %s remind %s date saved'),
                                    $invoiceData['evidence'],
                                    $invoiceCode,
                                    $colname
                                ), 'info');
                            } else {
                                $this->addStatusMessage(sprintf(
                                    _('%s %s remind %s date save failed'),
                                    $invoiceData['evidence'],
                                    $invoiceCode,
                                    $colname
                                ), 'error');
                            }
                        }
                    }
                } else {
                    $this->addStatusMessage(_('Remind send disbled'));
                }
            } else {
                $this->addStatusMessage(sprintf(
                    _('Remind %d already sent'),
                    $zewlScore
                ));
            }
        } else {
            $this->addStatusMessage(_('No debts to remind'), 'debug');
        }

        if (count($invoicesToLock)) {
            foreach ($invoicesToLock as $invoiceCode => $invoiceData) {
                $this->invoicer->dataReset();
                $this->invoicer->setMyKey(\AbraFlexi\RO::code($did));
                $lock = $this->invoicer->performAction('lock', 'int');
                if ($lock['success'] == 'true') {
                    $this->addStatusMessage(sprintf(
                        _('Invoice %s locked again'),
                        $invoiceCode
                    ), 'info');
                } else {
                    $this->addStatusMessage(sprintf(
                        _('Invoice %s locking failed'),
                        $invoiceCode
                    ), 'error');
                }
            }
        }

        return $zewlScore;
    }

    /**
     * Obtain Customer "Score"
     *
     * @param int $addressID AbraFlexi user ID
     *
     * @return int ZewlScore
     */
    public function getCustomerScore($addressID)
    {
        $score = 0;
        $debts = $this->customer->getCustomerDebts($addressID);
        $stitkyRaw = $this->customer->adresar->getColumnsFromAbraFlexi(
            ['stitky'],
            ['id' => $addressID]
        );
        $stitky = $stitkyRaw[0]['stitky'];
        if (!empty($debts)) {
            foreach ($debts as $did => $debt) {
                $ddiff = \AbraFlexi\FakturaVydana::overdueDays($debt['datSplat']);
                if (($ddiff <= 7) && ($ddiff >= 1)) {
                    $score = self::maxScore($score, 1);
                } else {
                    if (($ddiff > 7 ) && ($ddiff <= 14)) {
                        $score = self::maxScore($score, 2);
                    } else {
                        if ($ddiff > 14) {
                            $score = self::maxScore($score, 3);
                        }
                    }
                }
            }
        }
        if ($score == 3 && !strstr($stitky, 'UPOMINKA2')) {
            $score = 2;
        }

        if (!strstr($stitky, 'UPOMINKA1') && !empty($debts)) {
            $score = 1;
        }

        return $score;
    }

    /**
     * Get Last Sent Inventarization days Day
     *
     * @param array $clientDebts
     *
     * @return int
     */
    public static function getDaysToLastInventarization($clientDebts)
    {
        $days = 0;
        $daysToLastInvent = [];
        foreach ($clientDebts as $did => $debt) {
            if (strstr($debt['poznam'], 'Inventarizace:')) {
                foreach (explode("\n", $debt['poznam']) as $invRow) {
                    if (strstr($invRow, 'Inventarizace:')) {
                        $daysToLastInvent[] = \AbraFlexi\FakturaVydana::overdueDays(new \DateTime(str_replace(
                            'Inventarizace:',
                            '',
                            $invRow
                        )));
                    }
                }
            } else {
                $daysToLastInvent[] = \AbraFlexi\FakturaVydana::overdueDays(empty($debt['datSmir']) ? ( empty($debt['datUp2']) ? ( empty($debt['datUp1']) ? $debt['datVyst'] : $debt['datUp1'] ) : $debt['datUp2'] ) : $debt['datSmir']);
            }
        }
        $days = min($daysToLastInvent);
        return $days;
    }

    /**
     * Send remind
     *
     * @param int   $score       ZewlScore
     * @param array $clientDebts Array provided by customer::getCustomerDebts()
     *
     * @return boolean
     */
    public function posliUpominku($score, $clientDebts)
    {
        $result = false;
        foreach ($this->processNotifyModules($score, $clientDebts) as $modResult) {
            if ($modResult) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * Overdue group
     *
     * @param int $score current score value
     * @param int $level current level
     *
     * @return int max of all levels processed
     */
    private static function maxScore($score, $level)
    {
        if ($level > $score) {
            $score = $level;
        }
        return $score;
    }

    /**
     * Include all classes in modules directory
     *
     * @param int          $score     weeks of due
     * @param array        $debts     array of debts by current customer
     *
     * @return array Sent results
     */
    public function processNotifyModules($score, $debts)
    {
        $result = [];
        $notifiersNamespace = 'AbraFlexi\\Reminder\\Notifier';
        \Ease\Functions::loadClassesInNamespace($notifiersNamespace);
        foreach (\Ease\Functions::classesInNamespace($notifiersNamespace) as $notifierClass) {
            $class = $notifiersNamespace . '\\' . $notifierClass;
            $result[$notifierClass] = new $class($this, $score, $debts);
        }
        return $result;
    }

    /**
     * Return all unsettled documents in evidence
     *
     * @param string $evidence   override default evidence
     * @param array  $conditions for debts obtaining
     *
     * @return array
     */
    public function getEvidenceDebts($evidence = null, $conditions = [])
    {
        if ($evidence) {
            $evBackup = $this->invoicer->getEvidence();
            $this->invoicer->setEvidence($evidence);
        } else {
            $evBackup = false;
        }

        $what = array_merge(["datSplat lte '" . \AbraFlexi\RW::dateToFlexiDate(new DateTime()) . "' AND (stavUhrK is null OR stavUhrK eq 'stavUhr.castUhr') AND storno eq false"], $conditions);
        $result = [];
        $colsToGet = [
            'id',
            'kod',
            'stavUhrK',
            'firma',
            'buc',
            'popis',
            'varSym',
            'specSym',
            'sumCelkem',
            'sumCelkemMen',
            'duzpPuv',
            'typDokl(typDoklK,kod)',
            'datSplat',
            'zbyvaUhradit',
            'zbyvaUhraditMen',
            'mena',
            'poznam',
            'zamekK',
            'datVyst'];
        if ($this->invoicer->getColumnInfo('stavMailK', $evidence)) {
            $colsToGet[] = 'stavMailK';
        }

        $this->invoicer->defaultUrlParams['order'] = 'datVyst@A';
        $invoices = $this->invoicer->getColumnsFromAbraFlexi(
            $colsToGet,
            $what,
            'kod'
        );
        if ($this->invoicer->lastResponseCode == 200) {
            $docTypeSkipList = [];
            if (\Ease\Shared::cfg('REMINDER_SKIPDOCTYPE')) {
                $docTypeSkipList = \AbraFlexi\Stitek::listToArray(\Ease\Shared::cfg('REMINDER_SKIPDOCTYPE'));
            }

            $evidenceUsed = $this->invoicer->getEvidence();
            foreach ($invoices as $invoiceId => $invoiceData) {
                $invoiceData['evidence'] = $evidenceUsed;
                if (
                    array_key_exists(
                        \AbraFlexi\RO::uncode($invoiceData['typDokl']),
                        $docTypeSkipList
                    )
                ) {
                    continue;
                }
                $result[$invoiceId] = $invoiceData;
            }
        }

        if ($evBackup) {
            $this->invoicer->setEvidence($evBackup);
        }

        return $result;
    }

    /**
     * Get All debts
     *
     * @param array $conditions for debts obtaining
     *
     * @return array
     */
    public function getAllDebts($conditions = [])
    {
        $debts = $this->getEvidenceDebts('faktura-vydana', $conditions);
        $debts2 = $this->getEvidenceDebts('pohledavka', $conditions);
        return \Ease\Functions::reindexArrayBy(array_merge(
            empty($debts) ? [] : $debts,
            empty($debts2) ? [] : $debts2
        ), 'kod');
    }

    /**
     * Get Customer listing
     *
     * @return array clients indexed by code
     */
    public function getCustomerList($conditions = [])
    {
        //[/* 'typVztahuK'=>'typVztahu.odberDodav' */]
        $allClients = $this->customer->adresar->getColumnsFromAbraFlexi(['id', 'nazev',
            'stitky'], $conditions, 'kod');
        if (!empty($allClients)) {
            foreach ($allClients as $clientCode => $clientInfo) {
                $allClients[$clientCode]['stitky'] = \AbraFlexi\Stitek::listToArray($clientInfo['stitky']);
            }
        }
        return $allClients;
    }

    /**
     * Totals array as string
     *
     * @param array $totals
     *
     * @return string
     */
    public static function formatTotals($totals)
    {
        $tmp = [];
        foreach ($totals as $currency => $value) {
            $tmp[] = \AbraFlexi\Reminder\Upominka::formatCurrency($value) . ' ' . $currency;
        }
        return implode(',', $tmp);
    }
}
