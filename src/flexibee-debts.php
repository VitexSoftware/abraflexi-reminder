#!/usr/bin/php -f
<?php
/**
 * System.spoje.net - Odeslání Upomínek
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017 Vitex Software
 */
define('EASE_APPNAME', 'ShowDebts');
define('EASE_LOGGER', 'syslog|console|mail');

require_once '../vendor/autoload.php';
$shared = new Ease\Shared();
try {
    $shared->loadConfig('../client.json', true);
    $shared->loadConfig('../reminder.json', true);
    $localer = new \Ease\Locale('cs_CZ', '../i18n', 'flexibee-reminder');

    $reminder = new \FlexiPeeHP\Reminder\Upominac();
    $reminder->logBanner(constant('EASE_APPNAME'));

    $allDebts      = $reminder->getAllDebts();
    $allClients    = $reminder->getCustomerList();
    $clientsToSkip = [];
    foreach ($allClients as $clientCode => $clientInfo) {
        if (array_key_exists('NEUPOMINKOVAT', $clientInfo['stitky'])) {
            $clientsToSkip[$clientCode] = $clientInfo;
        }
    }

    $allDebtsByClient = [];
    $counter          = 0;
    $total            = [];
    foreach ($allDebts as $code => $debt) {
        $howmuchRaw = $howmuch    = [];

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
        $total[$curcode]      += $amount;

        foreach ($howmuchRaw as $cur => $price) {
            $howmuch[] = $price.' '.$cur;
        }
        $allDebtsByClient[$debt['firma']][$code] = $debt;
    }

    $pointer = 0;
    foreach ($allDebtsByClient as $clientCode => $clientDebts) {

        if (array_key_exists($clientCode, $clientsToSkip)) {
            continue;
        }

        if ($clientCode) {
            $reminder->addStatusMessage(\FlexiPeeHP\FlexiBeeRO::uncode($allClients[\FlexiPeeHP\FlexiBeeRO::uncode($clientCode)]['kod']).' '.$allClients[\FlexiPeeHP\FlexiBeeRO::uncode($clientCode)]['nazev'].' ['.implode(',',
                    $allClients[\FlexiPeeHP\FlexiBeeRO::uncode($clientCode)]['stitky']).']');
        }
        foreach ($clientDebts as $debtCode => $debtInfo) {

            $curcode = FlexiPeeHP\FlexiBeeRO::uncode($debtInfo['mena']);
            if ($curcode == 'CZK') {
                $amount = floatval($debtInfo['zbyvaUhradit']);
            } else {
                $amount = floatval($debtInfo['zbyvaUhraditMen']);
            }

            $reminder->addStatusMessage(sprintf('%d/%d (%s) [%s] %s %s: %s',
                    $pointer++, $counter,
                    \FlexiPeeHP\FlexiBeeRO::uncode($debtInfo['typDokl']),
                    \FlexiPeeHP\FlexiBeeRO::uncode($debtCode), $amount,
                    $curcode, $debtInfo['popis']
                ), 'debug');
        }
    }

    $reminder->addStatusMessage(FlexiPeeHP\Reminder\Upominac::formatTotals($total), 'success');
} catch (Exception $exc) {
    echo $exc->getMessage()."\n";
    echo $exc->getTraceAsString();
}


