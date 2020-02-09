#!/usr/bin/php -f
<?php
/**
 * System.spoje.net - Odeslání Upomínek
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2020 Vitex Software
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

    $allDebts = $reminder->getAllDebts(['limit'=>0]);
    $allClients = $reminder->getCustomerList(['limit'=>0]);
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
        if (!isset($total[$curcode]))
            $total[$curcode] = 0;
        $total[$curcode] += $amount;

        foreach ($howmuchRaw as $cur => $price) {
            $howmuch[] = $price . ' ' . $cur;
        }
        $allDebtsByClient[$debt['firma']][$code] = $debt;
    }

    $pointer = 0;
    foreach ($allDebtsByClient as $clientCodeRaw => $clientDebts) {
        $clientCode = \FlexiPeeHP\FlexiBeeRO::uncode($clientCodeRaw);

        if (array_key_exists($clientCodeRaw, $clientsToSkip)) {
            continue;
        }

        if ($clientCode) {
            $reminder->addStatusMessage($clientCode . ' ' . $allClients[$clientCode]['nazev'] . ' [' . implode(',',
                            $allClients[$clientCode]['stitky']) . ']');
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
    echo $exc->getMessage() . "\n";
    echo $exc->getTraceAsString();
}


