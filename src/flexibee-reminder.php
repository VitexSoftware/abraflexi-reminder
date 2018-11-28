#!/usr/bin/php -f
<?php
/**
 * System.spoje.net - Odeslání Upomínek
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017 Vitex Software
 */
define('EASE_APPNAME', 'Reminder');
define('MODULES', './notifiers');

require_once '../vendor/autoload.php';
$shared = new Ease\Shared();
$shared->loadConfig('../client.json', true);
$shared->loadConfig('../reminder.json', true);

$reminder = new \FlexiPeeHP\Reminder\Upominac();
$reminder->logBanner(constant('EASE_APPNAME'));

$allDebts       = $reminder->getAllDebts();
$allClients     = $reminder->getCustomerList();
$allClients[''] = ['kod' => '', 'nazev' => _('(Company not assigned)'), 'stitky' => [
        'NEUPOMINKOVAT' => 'NEUPOMINKOVAT']];
$clientsToSkip  = [];
foreach ($allClients as $clientCode => $clientInfo) {
    if (array_key_exists('NEUPOMINKOVAT', $clientInfo['stitky'])) {
        $clientsToSkip[$clientCode] = $clientInfo;
    }
}

$allDebtsByClient = [];
$counter          = 0;
$total            = [];
$totalsByClient   = [];
foreach ($allDebts as $code => $debt) {
    $howmuchRaw = $howmuch    = [];

    if (empty($debt['firma'])) {
        $clientCode      = 'code:';
        $clientCodeShort = '';
    } else {
        $clientCode      = $debt['firma'];
        $clientCodeShort = \FlexiPeeHP\FlexiBeeRO::uncode($clientCode);
    }

    if (array_key_exists($debt['firma'], $clientsToSkip)) {
        continue;
    }

    $counter++;

    $curcode = FlexiPeeHP\FlexiBeeRO::uncode($debt['mena']);
    if (!isset($howmuchRaw[$curcode])) {
        $howmuchRaw[$curcode] = 0;
    }

    if ($curcode == 'CZK') {
        $amount = floatval($debt['zbyvaUhradit']);
    } else {
        $amount = floatval($debt['zbyvaUhraditMen']);
    }

    $howmuchRaw[$curcode] += $amount;
    if (!isset($total[$curcode])) $total[$curcode]      = 0;

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
        $howmuch[] = $price.' '.$cur;
    }
    $allDebtsByClient[$clientCode][$code] = $debt;
}

$pointer = 0;
foreach ($allDebtsByClient as $clientCode => $clientDebts) {

    $clientCodeShort = \FlexiPeeHP\FlexiBeeRO::uncode($clientCode);

    if (array_key_exists($clientCode, $clientsToSkip)) {
        continue;
    }

    $clientData = $allClients[$clientCodeShort];

    if ($clientCode) {
        $reminder->addStatusMessage(
            $clientCodeShort.' '.
            $clientData['nazev'].
            ' ['.implode(',', $clientData['stitky']).'] '.
            FlexiPeeHP\Reminder\Upominac::formatTotals($clientData['totals']),
            'success');
    }

    $reminder->processUserDebts($clientData, $clientDebts);

//        foreach ($clientDebts as $debtCode => $debtInfo) {
//
//            $curcode = FlexiPeeHP\FlexiBeeRO::uncode($debtInfo['mena']);
//            if ($curcode == 'CZK') {
//                $amount = floatval($debtInfo['zbyvaUhradit']);
//            } else {
//                $amount = floatval($debtInfo['zbyvaUhraditMen']);
//            }
//
//            $reminder->addStatusMessage(sprintf('%d/%d (%s) [%s] %s %s: %s',
//                    $pointer++, $counter,
//                    \FlexiPeeHP\FlexiBeeRO::uncode($debtInfo['typDokl']),
//                    \FlexiPeeHP\FlexiBeeRO::uncode($debtCode), $amount,
//                    $curcode, $debtInfo['popis']
//                ), 'debug');
//        }
}

$reminder->addStatusMessage(FlexiPeeHP\Reminder\Upominac::formatTotals($total),
    'success');


