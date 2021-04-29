<?php

/**
 * AbraFlexi Reminder - Odeslání Upomínek
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2020 Vitex Software
 */
use Ease\Locale;
use Ease\Shared;
use AbraFlexi\RO;
use AbraFlexi\Reminder\Upominac;

define('EASE_APPNAME', 'Reminder');
define('MODULES', './AbraFlexi/Reminder/Notifier');

require_once '../vendor/autoload.php';
$shared = new Shared();
if (file_exists('../.env')) {
    $shared->loadConfig('../.env', true);
}
$localer = new Locale('cs_CZ', '../i18n', 'abraflexi-reminder');

$reminder = new Upominac();
$reminder->logBanner(constant('EASE_APPNAME'));

$allDebts = $reminder->getAllDebts(['limit' => 0]);
$allClients = $reminder->getCustomerList(['limit' => 0]);
$allClients[''] = ['kod' => '', 'nazev' => '(' . _('Company not assigned') . ')', 'stitky' => [
        'NEUPOMINKOVAT' => 'NEUPOMINKOVAT']];
$clientsToSkip = [];
foreach ($allClients as $clientCode => $clientInfo) {
    if (array_key_exists('NEUPOMINKOVAT', $clientInfo['stitky'])) {
        $clientsToSkip[$clientCode] = $clientInfo;
    }
}

$allDebtsByClient = [];
$counter = 0;
$total = [];
$totalsByClient = [];
foreach ($allDebts as $code => $debt) {
    $howmuchRaw = $howmuch = [];

    if (empty($debt['firma'])) {
        $clientCode = 'code:';
        $clientCodeShort = '';
    } else {
        $clientCode = $debt['firma'];
        $clientCodeShort = RO::uncode($clientCode);
    }

    if (array_key_exists($debt['firma'], $clientsToSkip)) {
        continue;
    }

    $counter++;

    $curcode = RO::uncode($debt['mena']);
    if (!isset($howmuchRaw[$curcode])) {
        $howmuchRaw[$curcode] = 0;
    }

    if ($curcode == 'CZK') {
        $amount = floatval($debt['zbyvaUhradit']);
    } else {
        $amount = floatval($debt['zbyvaUhraditMen']);
    }

    $howmuchRaw[$curcode] += $amount;
    if (!isset($total[$curcode]))
        $total[$curcode] = 0;

    if (!array_key_exists('totals', $allClients[$clientCodeShort])) {
        $allClients[$clientCodeShort]['totals'] = [];
    }
    if (!array_key_exists($curcode, $allClients[$clientCodeShort]['totals'])) {
        $allClients[$clientCodeShort]['totals'][$curcode] = $amount;
    } else {
        $allClients[$clientCodeShort]['totals'][$curcode] += $amount;
    }

    $total[$curcode] += $amount;

    foreach ($howmuchRaw as $cur => $price) {
        $howmuch[] = $price . ' ' . $cur;
    }
    $allDebtsByClient[$clientCode][$code] = $debt;
}

$pointer = 0;
foreach ($allDebtsByClient as $clientCode => $clientDebts) {

    $clientCodeShort = RO::uncode($clientCode);

    if (array_key_exists($clientCode, $clientsToSkip)) {
        continue;
    }

    $clientData = $allClients[$clientCodeShort];

    if ($clientCode) {
        $reminder->addStatusMessage(
                $clientCodeShort . ' ' .
                $clientData['nazev'] .
                ' [' . implode(',', $clientData['stitky']) . '] ' .
                Upominac::formatTotals($clientData['totals']),
                'success');
    } else {
        $reminder->addStatusMessage(__('Missing Client CODE'), 'warning');
    }

    $reminder->processUserDebts($clientData, $clientDebts);
}

$reminder->addStatusMessage(Upominac::formatTotals($total), 'success');

