#!/usr/bin/php -f
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

use AbraFlexi\Reminder\Upominac;

/**
 * AbraFlexi Reminder - lists debtors and defaulters.
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2024 VitexSoftware
 */
\define('EASE_APPNAME', 'ShowDebts');

require_once '../vendor/autoload.php';

$options = getopt('o::e::', ['output::', 'environment::']);

\Ease\Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'], \array_key_exists('environment', $options) ? $options['environment'] : '../.env');
$localer = new \Ease\Locale('cs_CZ', '../i18n', 'abraflexi-reminder');
$reminder = new Upominac();
$destination = \array_key_exists('output', $options) ? $options['output'] : \Ease\Shared::cfg('RESULT_FILE', 'php://stdout');

if (strtolower(\Ease\Shared::cfg('APP_DEBUG')) === 'true') {
    $reminder->logBanner(\Ease\Shared::appName().' v'.\Ease\Shared::appVersion());
}

$allDebts = $reminder->getAllDebts(['limit' => 0, "datSplat gte '".\AbraFlexi\Functions::timestampToFlexiDate(mktime(0, 0, 0, (int) date('m'), (int) date('d') - (int) \Ease\Shared::cfg('SURRENDER_DAYS', 365), (int) date('Y')))."' "]);
$allClients = $reminder->getCustomerList(['limit' => 0]);
$clientsToSkip = [];

if (empty($allClients)) {
    $reminder->addStatusMessage(_('No customers found'), 'warning');
} else {
    $clientCodes = [];

    foreach ($allClients as $clientCodeRaw => $clientInfo) {
        if (\array_key_exists(\Ease\Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT'), $clientInfo['stitky'])) {
            $clientsToSkip[$clientCodeRaw] = $clientInfo;
        }

        $clientCodes[] = $clientCodeRaw;
    }

    $reminder->addStatusMessage(implode(', ', array_keys($clientsToSkip)).'  '.\count($clientsToSkip).' '._('clients will be skipped'), 'warning');
}

$jsonOutput = ['skippedClients' => $clientsToSkip];

$allDebtsByClient = [];
$counter = 0;
$total = [];

foreach ($allDebts as $code => $debt) {
    $howmuchRaw = $howmuch = [];

    if (\array_key_exists((string) $debt['firma'], $clientsToSkip)) {
        continue;
    }

    ++$counter;
    $curcode = (string) AbraFlexi\Functions::uncode((string) $debt['mena']);

    if (!\array_key_exists($curcode, $howmuchRaw) || empty($howmuchRaw[$curcode])) {
        $howmuchRaw[$curcode] = 0;
    }

    if ($curcode === 'CZK') {
        $amount = (float) $debt['zbyvaUhradit'];
    } else {
        $amount = (float) $debt['zbyvaUhraditMen'];
    }

    $howmuchRaw[$curcode] += $amount;

    if (!isset($total[$curcode])) {
        $total[$curcode] = 0;
    }

    $total[$curcode] += $amount;

    foreach ($howmuchRaw as $cur => $price) {
        $howmuch[] = $price.' '.$cur;
    }

    $allDebtsByClient[(string) $debt['firma']][$code] = $debt;
    $jsonOutput['clients'][(string) $debt['firma']][$code] = $amount.' '.$curcode;
}

$pointer = 0;

foreach ($allDebtsByClient as $clientCodeRaw => $clientDebts) {
    $clientCode = \AbraFlexi\Functions::uncode($clientCodeRaw);

    if (\array_key_exists($clientCodeRaw, $clientsToSkip)) {
        continue;
    }

    if ($clientCode) {
        $reminder->addStatusMessage($clientCode.' '.$allClients[$clientCode]['nazev'].' ['.implode(
            ',',
            $allClients[$clientCode]['stitky'],
        ).']');
    }

    foreach ($clientDebts as $debtCode => $debtInfo) {
        $curcode = AbraFlexi\Functions::uncode((string) $debtInfo['mena']);

        if ($curcode === 'CZK') {
            $amount = (float) $debtInfo['zbyvaUhradit'];
        } else {
            $amount = (float) $debtInfo['zbyvaUhraditMen'];
        }

        $reminder->addStatusMessage(sprintf(
            '%d/%d (%s) %s [%s] %s %s: %s',
            $pointer++,
            $counter,
            \AbraFlexi\Functions::uncode((string) $debtInfo['typDokl']),
            \AbraFlexi\Functions::uncode($clientCodeRaw),
            \AbraFlexi\Functions::uncode($debtCode),
            $amount,
            $curcode,
            $debtInfo['popis'],
        ), 'debug');
    }
}

$reminder->addStatusMessage(Upominac::formatTotals($total), 'warning');

$jsonOutput['total'] = $total;

$written = file_put_contents($destination, json_encode($jsonOutput, \Ease\Shared::cfg('DEBUG') ? \JSON_PRETTY_PRINT : 0));
$reminder->addStatusMessage(sprintf(_('Saving result to %s'), $destination), $written ? 'success' : 'error');

exit($written ? 0 : 1);
