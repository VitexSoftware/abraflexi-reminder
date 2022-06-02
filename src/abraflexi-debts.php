#!/usr/bin/php -f
<?php

use AbraFlexi\Reminder\Upominac;

/**
 * AbraFlexi Reminder - Odeslání Upomínek
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2021 Vitex Software
 */
define('EASE_APPNAME', 'ShowDebts');
define('EASE_LOGGER', 'syslog|console|mail');

require_once '../vendor/autoload.php';
$shared = new \Ease\Shared();

try {
    if (file_exists('../.env')) {
        $shared->loadConfig('../.env', true);
    }

    $localer = new \Ease\Locale('cs_CZ', '../i18n', 'abraflexi-reminder');

    $reminder = new Upominac();
    $reminder->logBanner(\Ease\Functions::cfg('APP_NAME'));

    $allDebts = $reminder->getAllDebts(['limit' => 0,'filter[filterRok.datVyst]'=>date('Y')]);
    $allClients = $reminder->getCustomerList(['limit' => 0]);
    $clientsToSkip = [];
    if (empty($allClients)) {
        $reminder->addStatusMessage(_('No customers found'), 'warning');
    } else {
        foreach ($allClients as $clientCodeRaw => $clientInfo) {
            if (array_key_exists('NEUPOMINKOVAT', $clientInfo['stitky'])) {
                $clientsToSkip[$clientCodeRaw] = $clientInfo;
            }
        }
    }

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
        if (!isset($howmuchRaw[$curcode])) {
            $howmuchRaw[$curcode] = 0;
        }

        if ($curcode == 'CZK') {
            $amount = floatval($debt['zbyvaUhradit']);
        } else {
            $amount = floatval($debt['zbyvaUhraditMen']);
        }

        $howmuchRaw[$curcode] += $amount;
        if (!isset($total[$curcode])){
            $total[$curcode] = 0;
            
        }
        $total[$curcode] += $amount;
        foreach ($howmuchRaw as $cur => $price) {
            $howmuch[] = $price . ' ' . $cur;
        }
        $allDebtsByClient[strval($debt['firma'])][$code] = $debt;
    }

    $pointer = 0;
    foreach ($allDebtsByClient as $clientCodeRaw => $clientDebts) {
        $clientCode = \AbraFlexi\RO::uncode($clientCodeRaw);

        if (array_key_exists($clientCodeRaw, $clientsToSkip)) {
            continue;
        }

        if ($clientCode) {
            $reminder->addStatusMessage($clientCode . ' ' . $allClients[$clientCode]['nazev'] . ' [' . implode(',',
                            $allClients[$clientCode]['stitky']) . ']');
        }
        foreach ($clientDebts as $debtCode => $debtInfo) {

            $curcode = AbraFlexi\RO::uncode($debtInfo['mena']);
            if ($curcode == 'CZK') {
                $amount = floatval($debtInfo['zbyvaUhradit']);
            } else {
                $amount = floatval($debtInfo['zbyvaUhraditMen']);
            }

            $reminder->addStatusMessage(sprintf('%d/%d (%s) %s [%s] %s %s: %s',
                            $pointer++, $counter,
                            \AbraFlexi\RO::uncode($debtInfo['typDokl']),
                            \AbraFlexi\RO::uncode($clientCodeRaw),
                            \AbraFlexi\RO::uncode($debtCode), $amount,
                            $curcode, $debtInfo['popis']
                    ), 'debug');
        }
    }

    $reminder->addStatusMessage(Upominac::formatTotals($total), 'success');
} catch (Exception $exc) {
    echo $exc->getMessage() . "\n";
    echo $exc->getTraceAsString();
}


