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
use Ease\Shared;

\define('EASE_APPNAME', 'AbraFlexi reminder');

require_once '../vendor/autoload.php';

/**
 * Get today's Statements list.
 */
$options = getopt('o::e::', ['output::environment::']);
Shared::init(
    ['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'],
    \array_key_exists('environment', $options) ? $options['environment'] : (\array_key_exists('e', $options) ? $options['e'] : '../.env'),
);
$destination = \array_key_exists('o', $options) ? $options['o'] : (\array_key_exists('output', $options) ? $options['output'] : Shared::cfg('RESULT_FILE', 'php://stdout'));

$localer = new Locale(Shared::cfg('LANG', 'cs_CZ'), '../i18n', 'abraflexi-reminder');
$reminder = new Upominac();

if (strtolower(Shared::cfg('APP_DEBUG', 'false')) === 'true') {
    $reminder->logBanner();
}

$exitcode = 0;
$report = [];
$allDebts = $reminder->getAllDebts(['limit' => 0, "datSplat gte '".\AbraFlexi\Date::timestampToFlexiDate(mktime(0, 0, 0, (int) date('m'), (int) date('d') - (int) Shared::cfg('SURRENDER_DAYS', 365), (int) date('Y')))."' "]);
$allClients = $reminder->getCustomerList(['limit' => 0]);
$allClients[''] = ['kod' => '', 'nazev' => '('._('Company not assigned').')', 'stitky' => [
    Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT') => Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT')]];
$clientsToSkip = [];

$clientCodes = [];

foreach ($allClients as $clientCodeRaw => $clientInfo) {
    if (\array_key_exists(Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT'), $clientInfo['stitky'])) {
        $clientsToSkip[$clientCodeRaw] = $clientInfo;
    }

    $clientCodes[] = $clientCodeRaw;
}

$reminder->addStatusMessage(implode(', ', array_keys($clientsToSkip)).'  '.\count($clientsToSkip).' '._('clients will be skipped'), 'warning');
$report['skippedClients'] = array_keys($clientsToSkip);

$allDebtsByClient = [];
$counter = 0;
$total = [];
$totalsByClient = [];

foreach ($allDebts as $code => $debt) {
    $howmuchRaw = $howmuch = [];

    if (strstr($debt['stitky'], Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT'))) {
        $reminder->addStatusMessage(sprintf(_('I skip the %s because of the set label %s'), $code, Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT')), 'info');
        $report['skippedDocuments'][] = $code;

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
    $clientCodeShort = Functions::uncode((string) $clientCode);

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

        $report['reminded'][$clientCodeShort] = $reminder->processUserDebts($clientData, $clientDebts);
    }
}

$reminder->addStatusMessage(Upominac::formatTotals($total), 'success');

// Add required schema fields

$report['exitcode'] = $exitcode;
$report['status'] = $exitcode === 0 ? 'success' : 'error';
$report['timestamp'] = date('c');
$report['message'] = $exitcode === 0 ? _('Remind process finished successfully') : _('Remind process finished with errors');
if (!isset($report['artifacts'])) {
    $report['artifacts'] = new stdClass();
}
if (!isset($report['metrics'])) {
    $report['metrics'] = new stdClass();
}
$written = file_put_contents($destination, json_encode($report, Shared::cfg('DEBUG') ? \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE : 0));
$reminder->addStatusMessage(sprintf(_('Saving result to %s'), $destination), $written ? 'success' : 'error');

exit($exitcode);
