#!/usr/bin/php -f
<?php

use AbraFlexi\Reminder\Upominac;

/**
 * AbraFlexi Reminder - Odeslání Upomínek
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2024 Vitex Software
 */

define('EASE_APPNAME', 'ShowDebts');
require_once '../vendor/autoload.php';

\Ease\Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'], isset($argv[1]) ? $argv[1] : '../.env');
$localer = new \Ease\Locale('cs_CZ', '../i18n', 'abraflexi-reminder');
$reminder = new Upominac();
if (\Ease\Shared::cfg('APP_DEBUG') == 'True') {
    $reminder->logBanner(\Ease\Shared::appName() . ' v' . \Ease\Shared::appVersion());
}

$allDebts = $reminder->getAllDebts(['limit' => 0, "datSplat gte '" . \AbraFlexi\RW::timestampToFlexiDate(mktime(0, 0, 0, date("m"), date("d") - intval(\Ease\Shared::cfg('SURRENDER_DAYS', 365)), date("Y"))) . "' "]);
$allClients = $reminder->getCustomerList(['limit' => 0]);
$clientsToSkip = [];
if (empty($allClients)) {
    $reminder->addStatusMessage(_('No customers found'), 'warning');
} else {
    foreach ($allClients as $clientCodeRaw => $clientInfo) {
        if (array_key_exists(\Ease\Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT'), $clientInfo['stitky'])) {
            $clientsToSkip[$clientCodeRaw] = $clientInfo;
        }
    }
}

$jsonOutput = ['skippedClients' => $clientsToSkip];

$allDebtsByClient = [];
$counter = 0;
$total = [];
foreach ($allDebts as $code => $debt) {
    $howmuchRaw = $howmuch = [];
    if (array_key_exists(strval($debt['firma']), $clientsToSkip)) {
        continue;
    }

    $counter++;
    $curcode = AbraFlexi\RO::uncode(strval($debt['mena']));
    if (!array_key_exists($curcode, $howmuchRaw)) {
        $howmuchRaw[$curcode] = 0;
    }

    if ($curcode == 'CZK') {
        $amount = floatval($debt['zbyvaUhradit']);
    } else {
        $amount = floatval($debt['zbyvaUhraditMen']);
    }

    $howmuchRaw[$curcode] += $amount;
    if (!isset($total[$curcode])) {
        $total[$curcode] = 0;
    }
    $total[$curcode] += $amount;
    foreach ($howmuchRaw as $cur => $price) {
        $howmuch[] = $price . ' ' . $cur;
    }
    $allDebtsByClient[strval($debt['firma'])][$code] = $debt;
    $jsonOutput['clients'][strval($debt['firma'])][$code] = $amount . ' ' . $curcode;
}

$pointer = 0;
foreach ($allDebtsByClient as $clientCodeRaw => $clientDebts) {
    $clientCode = \AbraFlexi\RO::uncode($clientCodeRaw);
    if (array_key_exists($clientCodeRaw, $clientsToSkip)) {
        continue;
    }

    if ($clientCode) {
        $reminder->addStatusMessage($clientCode . ' ' . $allClients[$clientCode]['nazev'] . ' [' . implode(
            ',',
            $allClients[$clientCode]['stitky']
        ) . ']');
    }
    foreach ($clientDebts as $debtCode => $debtInfo) {
        $curcode = AbraFlexi\RO::uncode($debtInfo['mena']);
        if ($curcode == 'CZK') {
            $amount = floatval($debtInfo['zbyvaUhradit']);
        } else {
            $amount = floatval($debtInfo['zbyvaUhraditMen']);
        }

        $reminder->addStatusMessage(sprintf(
            '%d/%d (%s) %s [%s] %s %s: %s',
            $pointer++,
            $counter,
            \AbraFlexi\RO::uncode($debtInfo['typDokl']),
            \AbraFlexi\RO::uncode($clientCodeRaw),
            \AbraFlexi\RO::uncode($debtCode),
            $amount,
            $curcode,
            $debtInfo['popis']
        ), 'debug');
    }
}



$reminder->addStatusMessage(Upominac::formatTotals($total), 'warning');

$jsonOutput['total'] = $total;

$jsonReport = json_encode($jsonOutput, JSON_PRETTY_PRINT);

if (\Ease\Shared::cfg('JSON_REPORT_FILE', false)) {
    $reminder->addStatusMessage(sprintf(_('Saving json report to %s'), \Ease\Shared::cfg('JSON_REPORT_FILE')), file_put_contents(\Ease\Shared::cfg('JSON_REPORT_FILE'), $jsonReport) ? 'success' : 'error');
} else {
    echo $jsonReport;
}
