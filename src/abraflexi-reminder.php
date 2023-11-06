<?php

/**
 * AbraFlexi Reminder - Remind sender
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2023 Vitex Software
 */

use Ease\Locale;
use AbraFlexi\RO;
use AbraFlexi\Reminder\Upominac;

define('EASE_APPNAME', 'AbraFlexi reminder');
define('MODULES', './AbraFlexi/Reminder/Notifier');
require_once '../vendor/autoload.php';
\Ease\Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'], isset($argv[1]) ? $argv[1] : '../.env');
$localer = new Locale('cs_CZ', '../i18n', 'abraflexi-reminder');
$reminder = new Upominac();
if (strtolower(\Ease\Shared::cfg('APP_DEBUG')) == 'true') {
    $reminder->logBanner(\Ease\Shared::appName() . ' v' . \Ease\Shared::appVersion());
}

$allDebts = $reminder->getAllDebts(['limit' => 0, 'storno eq false', "datSplat gte '" . \AbraFlexi\RW::timestampToFlexiDate(mktime(0, 0, 0, date("m"), date("d") - intval(\Ease\Shared::cfg('SURRENDER_DAYS', 365)), date("Y"))) . "' "]);
$allClients = $reminder->getCustomerList(['limit' => 0]);
$allClients[''] = ['kod' => '', 'nazev' => '(' . _('Company not assigned') . ')', 'stitky' => [
        \Ease\Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT') => \Ease\Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT')]];
$clientsToSkip = [];
foreach ($allClients as $clientCode => $clientInfo) {
    if (array_key_exists(\Ease\Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT'), $clientInfo['stitky'])) {
        $clientsToSkip[$clientCode] = $clientInfo;
    }
}

$allDebtsByClient = [];
$counter = 0;
$total = [];
$totalsByClient = [];
foreach ($allDebts as $code => $debt) {
    $howmuchRaw = $howmuch = [];

    if (strstr($debt['stitky'], \Ease\Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT'))) {
        $reminder->addStatusMessage(sprintf(_('I skip the %s because of the set label'), $code), 'info');
        continue;
    }

    if (empty($debt['firma'])) {
        $clientCode = 'code:';
        $clientCodeShort = '';
    } else {
        $clientCode = $debt['firma'];
        $clientCodeShort = RO::uncode($clientCode);
    }

    if (array_key_exists(strval($debt['firma']), $clientsToSkip)) {
        continue;
    }

    $counter++;
    $curcode = RO::uncode($debt['mena']->value);
    if (!isset($howmuchRaw[$curcode])) {
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
    $allDebtsByClient[RO::uncode(strval($clientCode))][$code] = $debt;
}

$pointer = 0;
foreach ($allDebtsByClient as $clientCode => $clientDebts) {
    $clientCodeShort = RO::uncode($clientCode);
    if (empty(trim($clientCodeShort))) {
        $reminder->addStatusMessage(sprintf(_('Invoices %s without Company assigned'), implode(',', array_keys($clientDebts))), 'error');
    } else {
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
                'success'
            );
        } else {
            $reminder->addStatusMessage(_('Missing Client CODE'), 'warning');
        }

        $reminder->processUserDebts($clientData, $clientDebts);
    }
}

$reminder->addStatusMessage(Upominac::formatTotals($total), 'success');
