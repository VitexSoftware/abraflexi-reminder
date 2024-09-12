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

use AbraFlexi\Functions;
use AbraFlexi\Reminder\Upominac;
use Ease\Locale;

\define('EASE_APPNAME', 'AbraFlexi reminder');

require_once '../vendor/autoload.php';
\Ease\Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'], $argv[1] ?? '../.env');
$localer = new Locale('cs_CZ', '../i18n', 'abraflexi-reminder');
$reminder = new Upominac();

if (strtolower(\Ease\Shared::cfg('APP_DEBUG', 'false')) === 'true') {
    $reminder->logBanner();
}

$allDebts = $reminder->getAllDebts(['limit' => 0, "datSplat gte '".\AbraFlexi\RW::timestampToFlexiDate(mktime(0, 0, 0, (int) date('m'), (int) date('d') - (int) \Ease\Shared::cfg('SURRENDER_DAYS', 365), (int) date('Y')))."' "]);
$allClients = $reminder->getCustomerList(['limit' => 0]);
$allClients[''] = ['kod' => '', 'nazev' => '('._('Company not assigned').')', 'stitky' => [
    \Ease\Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT') => \Ease\Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT')]];
$clientsToSkip = [];

foreach ($allClients as $clientCode => $clientInfo) {
    if (\array_key_exists(\Ease\Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT'), $clientInfo['stitky'])) {
        $clientsToSkip[$clientCode] = $clientInfo;
    } else {
        $reminder->addStatusMessage(sprintf(_('I skip the %s because of the set label %s'), $clientCode, \Ease\Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT')), 'info');
    }
}

$allDebtsByClient = [];
$counter = 0;
$total = [];
$totalsByClient = [];

foreach ($allDebts as $code => $debt) {
    $howmuchRaw = $howmuch = [];

    if (strstr($debt['stitky'], \Ease\Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT'))) {
        $reminder->addStatusMessage(sprintf(_('I skip the %s because of the set label %s'), $code, \Ease\Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT')), 'info');

        continue;
    }

    if (empty($debt['firma'])) {
        $clientCode = 'code:';
        $clientCodeShort = '';
    } else {
        $clientCode = $debt['firma'];
        $clientCodeShort = Functions::uncode((string) $clientCode);
    }

    if (\array_key_exists((string) $debt['firma'], $clientsToSkip)) {
        continue;
    }

    ++$counter;
    $curcode = Functions::uncode((string) $debt['mena']);

    if (!array_key_exists($curcode, $howmuchRaw) || empty($howmuchRaw[$curcode])) {
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

    if (!\array_key_exists('totals', $allClients[$clientCodeShort])) {
        $allClients[$clientCodeShort]['totals'] = [];
    }

    if (!\array_key_exists($curcode, $allClients[$clientCodeShort]['totals'])) {
        $allClients[$clientCodeShort]['totals'][$curcode] = $amount;
    } else {
        $allClients[$clientCodeShort]['totals'][$curcode] += $amount;
    }

    $total[$curcode] += $amount;

    foreach ($howmuchRaw as $cur => $price) {
        $howmuch[] = $price.' '.$cur;
    }

    $allDebtsByClient[Functions::uncode((string) $clientCode)][$code] = $debt;
}

$pointer = 0;

foreach ($allDebtsByClient as $clientCode => $clientDebts) {
    $clientCodeShort = Functions::uncode($clientCode);

    if (empty(trim($clientCodeShort))) {
        $reminder->addStatusMessage(sprintf(_('Invoices %s without Company assigned'), implode(',', array_keys($clientDebts))), 'error');
    } else {
        if (\array_key_exists($clientCode, $clientsToSkip)) {
            continue;
        }

        $clientData = $allClients[$clientCodeShort];

        if ($clientCode) {
            $reminder->addStatusMessage(
                $clientCodeShort.' '.
                    $clientData['nazev'].
                    ' ['.implode(',', $clientData['stitky']).'] '.
                    Upominac::formatTotals($clientData['totals']),
                'success',
            );
        } else {
            $reminder->addStatusMessage(_('Missing Client CODE'), 'warning');
        }

        $reminder->processUserDebts($clientData, $clientDebts);
    }
}

$reminder->addStatusMessage(Upominac::formatTotals($total), 'success');
